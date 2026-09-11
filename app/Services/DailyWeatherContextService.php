<?php

namespace App\Services;

use App\Models\DailyWeatherContext;
use App\Support\OperationalFailureMonitor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DailyWeatherContextService
{
    public const SOURCE = 'open-meteo-recent-history';
    private const FORECAST_CACHE_HOURS = 12;
    private const FORECAST_STALE_HOURS = 168;
    private const FORECAST_RATE_LIMIT_COOLDOWN_HOURS = 6;
    private const FORECAST_RATE_LIMIT_KEY = 'weather:forecast:open-meteo:rate-limited-until';

    /**
     * Collect one completed calendar day for every actively covered barangay.
     * Existing rows are updated, making retries safe and non-duplicating.
     */
    public function collectDate(Carbon $date, ?int $stationId = null, bool $missingOnly = false): array
    {
        $observationDate = $date->copy()->timezone('Asia/Manila')->startOfDay();
        $latestAllowed = Carbon::now('Asia/Manila')->subDay()->startOfDay();

        if ($observationDate->greaterThan($latestAllowed)) {
            throw new \InvalidArgumentException('Daily weather collection only accepts completed days up to yesterday.');
        }

        $locations = DB::table('station_barangay_coverage')
            ->join('barangay_halls', 'station_barangay_coverage.barangay_id', '=', 'barangay_halls.barangay_id')
            ->join('markers', 'barangay_halls.marker_id', '=', 'markers.marker_id')
            ->where('station_barangay_coverage.is_active', true)
            ->whereNotNull('markers.lat')
            ->whereNotNull('markers.lng')
            ->when($stationId, fn ($query) => $query->where('station_barangay_coverage.station_id', $stationId))
            ->orderByRaw("CASE WHEN station_barangay_coverage.coverage_type = 'primary' THEN 0 ELSE 1 END")
            ->orderBy('station_barangay_coverage.station_id')
            ->get([
                'station_barangay_coverage.station_id',
                'station_barangay_coverage.barangay_id',
                'markers.lat',
                'markers.lng',
            ])
            ->unique('barangay_id')
            ->values();

        if ($missingOnly && $locations->isNotEmpty()) {
            $existingBarangayIds = DailyWeatherContext::query()
                ->whereDate('observation_date', $observationDate->toDateString())
                ->where('source', self::SOURCE)
                ->whereIn('barangay_id', $locations->pluck('barangay_id')->all())
                ->pluck('barangay_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $locations = $locations
                ->reject(fn ($location) => in_array((int) $location->barangay_id, $existingBarangayIds, true))
                ->values();
        }

        $summary = ['checked' => $locations->count(), 'saved' => 0, 'failed' => 0, 'failures' => []];

        foreach ($locations as $location) {
            try {
                $payload = $this->fetchCompletedDay(
                    (float) $location->lat,
                    (float) $location->lng,
                    $observationDate
                );
                $daily = $this->summarizeHourlyPayload($payload);

                DailyWeatherContext::query()->updateOrCreate(
                    [
                        'barangay_id' => (int) $location->barangay_id,
                        'observation_date' => $observationDate->toDateString(),
                        'source' => self::SOURCE,
                    ],
                    array_merge($daily, [
                        'station_id' => (int) $location->station_id,
                        'latitude' => (float) $location->lat,
                        'longitude' => (float) $location->lng,
                        'raw_payload' => $payload,
                        'fetched_at' => now(),
                    ])
                );
                $summary['saved']++;
            } catch (\Throwable $exception) {
                $summary['failed']++;
                $summary['failures'][] = [
                    'barangay_id' => (int) $location->barangay_id,
                    'message' => $exception->getMessage(),
                ];
                Log::warning('Daily barangay weather collection failed.', [
                    'station_id' => (int) $location->station_id,
                    'barangay_id' => (int) $location->barangay_id,
                    'observation_date' => $observationDate->toDateString(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $summary;
    }

    public function collectMissingRecentDays(int $days = 7, ?int $stationId = null): array
    {
        $days = max(1, min($days, 31));
        $totals = ['checked' => 0, 'saved' => 0, 'failed' => 0, 'failures' => []];

        for ($offset = $days; $offset >= 1; $offset--) {
            $daily = $this->collectDate(Carbon::now('Asia/Manila')->subDays($offset), $stationId, true);
            $totals['checked'] += $daily['checked'];
            $totals['saved'] += $daily['saved'];
            $totals['failed'] += $daily['failed'];
            $totals['failures'] = array_merge($totals['failures'], $daily['failures']);
        }

        return $totals;
    }

    public function fetchForecast(float $latitude, float $longitude, int $days = 5): array
    {
        $days = max(1, min($days, 7));
        $coordinateKey = number_format($latitude, 3, '.', '').':'.number_format($longitude, 3, '.', '');
        $freshKey = "weather:forecast:v3:{$coordinateKey}:{$days}:fresh";
        $staleKey = "weather:forecast:v3:{$coordinateKey}:{$days}:last-success";
        $cached = $this->readForecastCache($freshKey);

        if ($this->isUsableForecast($cached)) {
            return array_merge($cached, [
                'cache_status' => 'fresh',
                'is_stale' => false,
                'warning' => null,
            ]);
        }

        $rateLimitUntil = $this->openMeteoRateLimitUntil();
        if ($rateLimitUntil?->isFuture()) {
            $backup = $this->fetchBackupForecast($latitude, $longitude, $days);
            if ($backup !== null) return $backup;
            $lastSuccessful = $this->readForecastCache($staleKey);
            if ($this->isUsableForecast($lastSuccessful)) {
                return array_merge($lastSuccessful, [
                    'cache_status' => 'stale-rate-limit-cooldown',
                    'is_stale' => true,
                    'current' => [],
                    'warning' => 'Open-Meteo is rate-limited. Showing the last successful forecast while requests are paused.',
                ]);
            }

            throw new \RuntimeException('Open-Meteo requests are temporarily paused after HTTP 429 until '.$rateLimitUntil->toIso8601String().'.');
        }

        try {
            // One provider request only. In particular, HTTP 429 must never be
            // retried immediately because every retry consumes more quota.
            $response = Http::timeout(10)
                ->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'current' => implode(',', [
                        'temperature_2m',
                        'relative_humidity_2m',
                        'weather_code',
                        'wind_speed_10m',
                    ]),
                    'daily' => implode(',', [
                        'weather_code',
                        'temperature_2m_max',
                        'temperature_2m_min',
                        'precipitation_probability_max',
                        'precipitation_sum',
                        'wind_speed_10m_max',
                    ]),
                    'hourly' => implode(',', [
                        'temperature_2m',
                        'relative_humidity_2m',
                        'precipitation_probability',
                        'wind_speed_10m',
                    ]),
                    'forecast_days' => $days,
                    'timezone' => 'Asia/Manila',
                ]);

            if (! $response->successful()) {
                if ($response->status() === 429) {
                    $this->startOpenMeteoRateLimitCooldown();
                }
                $message = $response->status() === 429
                    ? 'Open-Meteo daily request limit was reached (HTTP 429).'
                    : "Open-Meteo returned HTTP {$response->status()}.";

                throw new \RuntimeException($message);
            }

            $payload = $response->json();
            $daily = is_array($payload['daily'] ?? null) ? $payload['daily'] : [];
            $dates = is_array($daily['time'] ?? null) ? $daily['time'] : [];
            if ($dates === []) {
                throw new \RuntimeException('Open-Meteo returned no daily forecast data.');
            }

            $hourly = is_array($payload['hourly'] ?? null) ? $payload['hourly'] : [];
            $hourlyTimes = is_array($hourly['time'] ?? null) ? $hourly['time'] : [];
            $humidityByDate = [];
            foreach ($hourlyTimes as $hourlyIndex => $time) {
                $humidity = $this->numericOrNull($hourly['relative_humidity_2m'][$hourlyIndex] ?? null);
                if ($humidity === null) {
                    continue;
                }

                $dateKey = substr((string) $time, 0, 10);
                $humidityByDate[$dateKey][] = $humidity;
            }

            $cards = collect($dates)->take($days)->map(function ($date, int $index) use ($daily, $humidityByDate) {
                $code = is_numeric($daily['weather_code'][$index] ?? null)
                    ? (int) $daily['weather_code'][$index]
                    : null;
                $dateKey = Carbon::parse($date)->toDateString();
                $dailyHumidity = collect($humidityByDate[$dateKey] ?? []);

                return [
                    'date' => $dateKey,
                    'day' => Carbon::parse($date)->format('D'),
                    'date_label' => Carbon::parse($date)->format('M j'),
                    'condition' => $this->describeWeatherCode($code),
                    'weather_code' => $code,
                    'min_temp_c' => $this->numericOrNull($daily['temperature_2m_min'][$index] ?? null),
                    'max_temp_c' => $this->numericOrNull($daily['temperature_2m_max'][$index] ?? null),
                    'precipitation_probability_percent' => $this->numericOrNull($daily['precipitation_probability_max'][$index] ?? null),
                    'precipitation_mm' => $this->numericOrNull($daily['precipitation_sum'][$index] ?? null),
                    'wind_kph' => $this->numericOrNull($daily['wind_speed_10m_max'][$index] ?? null),
                    'humidity_percent' => $dailyHumidity->isEmpty() ? null : round((float) $dailyHumidity->avg(), 1),
                ];
            })->values()->all();

            $forecast = [
                'provider' => 'Open-Meteo',
                'provider_url' => 'https://open-meteo.com/',
                'cards' => $cards,
                'current' => is_array($payload['current'] ?? null) ? $payload['current'] : [],
                'fetched_at' => now('Asia/Manila')->toIso8601String(),
            ];

            if (!$this->isUsableForecast($forecast)) {
                throw new \RuntimeException('Open-Meteo returned an unusable forecast.');
            }

            $this->writeForecastCache($freshKey, $forecast, now()->addHours(self::FORECAST_CACHE_HOURS));
            $this->writeForecastCache($staleKey, $forecast, now()->addHours(self::FORECAST_STALE_HOURS));

            return array_merge($forecast, [
                'cache_status' => 'refreshed',
                'is_stale' => false,
                'warning' => null,
            ]);
        } catch (\Throwable $exception) {
            OperationalFailureMonitor::record('open_meteo', $exception->getMessage());
            $backup = $this->fetchBackupForecast($latitude, $longitude, $days);
            if ($backup !== null) return $backup;
            $lastSuccessful = $this->readForecastCache($staleKey);
            if ($this->isUsableForecast($lastSuccessful)) {
                Log::warning('Using the last successful Open-Meteo forecast.', [
                    'coordinates' => $coordinateKey,
                    'fetched_at' => $lastSuccessful['fetched_at'] ?? null,
                    'error' => $exception->getMessage(),
                ]);

                return array_merge($lastSuccessful, [
                    'cache_status' => 'stale',
                    'is_stale' => true,
                    'current' => [],
                    'warning' => 'Open-Meteo is temporarily unavailable or rate-limited. Showing the last successful forecast.',
                ]);
            }

            throw $exception;
        }
    }

    private function fetchBackupForecast(float $latitude, float $longitude, int $days): ?array
    {
        $key = 'weather:forecast:met-norway:v1:'.number_format($latitude, 3, '.', '').':'.number_format($longitude, 3, '.', '').':'.$days;
        $cached = $this->readForecastCache($key.':fresh');
        if ($this->isUsableForecast($cached)) {
            return array_merge($cached, ['cache_status' => 'backup-fresh', 'is_stale' => false]);
        }
        try {
            if ($this->readForecastCache($key.':paused') !== null) throw new \RuntimeException('Backup requests temporarily paused.');
            $forecast = app(MetNorwayForecastService::class)->fetch($latitude, $longitude, $days);
            $this->writeForecastCache($key.':fresh', $forecast, now()->addHour());
            $this->writeForecastCache($key.':last-success', $forecast, now()->addDay());
            return array_merge($forecast, ['cache_status' => 'backup-refreshed', 'is_stale' => false]);
        } catch (\Throwable $exception) {
            // Do not repeatedly hit a failing backup provider on page refreshes.
            if ($this->readForecastCache($key.':paused') === null) {
                $this->writeForecastCache($key.':paused', ['paused' => true], now()->addMinutes(5));
                Log::warning('Backup weather forecast unavailable.', ['error' => $exception->getMessage()]);
            }
            $stale = $this->readForecastCache($key.':last-success');
            if ($this->isUsableForecast($stale)) {
                return array_merge($stale, [
                    'cache_status' => 'backup-stale', 'is_stale' => true, 'current' => [],
                    'warning' => 'Both weather providers are unavailable. Showing the last saved MET Norway forecast, not live conditions. Daily rain totals and probabilities are unavailable.',
                ]);
            }
            return null;
        }
    }

    private function readForecastCache(string $key): ?array
    {
        try {
            $value = Cache::get($key);

            if (is_array($value['cards'] ?? null)) {
                $today = now('Asia/Manila')->toDateString();
                $value['cards'] = array_values(array_filter($value['cards'], fn ($card) => is_array($card) && ($card['date'] ?? '') >= $today));
                if (substr((string) ($value['fetched_at'] ?? ''), 0, 10) !== $today) $value['current'] = [];
            }

            return is_array($value) ? $value : null;
        } catch (\Throwable $exception) {
            Log::warning('Weather forecast cache read failed.', [
                'key' => $key,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function writeForecastCache(string $key, array $forecast, Carbon $expiresAt): void
    {
        try {
            Cache::put($key, $forecast, $expiresAt);
        } catch (\Throwable $exception) {
            // A cache filesystem/database issue must not discard a forecast
            // that was fetched successfully from the provider.
            Log::warning('Weather forecast cache write failed.', [
                'key' => $key,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function isUsableForecast(?array $forecast): bool
    {
        return is_array($forecast)
            && is_array($forecast['cards'] ?? null)
            && $forecast['cards'] !== []
            && ($forecast['cards'][0]['date'] ?? '') === now('Asia/Manila')->toDateString()
            && is_numeric($forecast['cards'][0]['max_temp_c'] ?? null);
    }

    private function openMeteoRateLimitUntil(): ?Carbon
    {
        try {
            $value = Cache::get(self::FORECAST_RATE_LIMIT_KEY);

            return filled($value) ? Carbon::parse((string) $value) : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function startOpenMeteoRateLimitCooldown(): void
    {
        try {
            $until = now()->addHours(self::FORECAST_RATE_LIMIT_COOLDOWN_HOURS);
            Cache::put(self::FORECAST_RATE_LIMIT_KEY, $until->toIso8601String(), $until);
        } catch (\Throwable $exception) {
            Log::warning('Unable to store the Open-Meteo rate-limit cooldown.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function fetchCompletedDay(float $latitude, float $longitude, Carbon $date): array
    {
        $rateLimitUntil = $this->openMeteoRateLimitUntil();
        if ($rateLimitUntil?->isFuture()) {
            throw new \RuntimeException('Open-Meteo requests are temporarily paused after HTTP 429 until '.$rateLimitUntil->toIso8601String().'.');
        }

        // Do not automatically retry quota failures. The next scheduled run
        // can recover missing rows without multiplying provider requests.
        try {
            $response = Http::timeout(25)
                ->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'start_date' => $date->toDateString(),
                    'end_date' => $date->toDateString(),
                    'hourly' => implode(',', [
                        'temperature_2m',
                        'relative_humidity_2m',
                        'precipitation',
                        'rain',
                        'weather_code',
                        'wind_speed_10m',
                        'wind_gusts_10m',
                    ]),
                    'timezone' => 'Asia/Manila',
                ]);
        } catch (\Throwable $exception) {
            OperationalFailureMonitor::record('open_meteo', $exception->getMessage());
            throw $exception;
        }

        if (! $response->successful()) {
            if ($response->status() === 429) {
                $this->startOpenMeteoRateLimitCooldown();
            }
            OperationalFailureMonitor::record('open_meteo', "Historical collection returned HTTP {$response->status()}.");
            throw new \RuntimeException("Open-Meteo returned HTTP {$response->status()}.");
        }

        $payload = $response->json();
        if (! is_array($payload) || ! is_array($payload['hourly'] ?? null)) {
            throw new \RuntimeException('Open-Meteo returned no hourly weather data.');
        }

        return $payload;
    }

    public function summarizeHourlyPayload(array $payload): array
    {
        $hourly = is_array($payload['hourly'] ?? null) ? $payload['hourly'] : [];
        $temperatures = $this->numericValues($hourly['temperature_2m'] ?? []);
        $humidities = $this->numericValues($hourly['relative_humidity_2m'] ?? []);
        $winds = $this->numericValues($hourly['wind_speed_10m'] ?? []);
        $gusts = $this->numericValues($hourly['wind_gusts_10m'] ?? []);
        $precipitation = $this->numericValues($hourly['precipitation'] ?? []);
        $rain = $this->numericValues($hourly['rain'] ?? []);
        $weatherCodes = collect($hourly['weather_code'] ?? [])
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value);

        if ($temperatures->isEmpty() && $humidities->isEmpty() && $weatherCodes->isEmpty()) {
            throw new \RuntimeException('The completed day contains no usable hourly observations.');
        }

        $dominantWeatherCode = $weatherCodes->countBy()->sortDesc()->keys()->first();

        return [
            'temperature_avg_c' => $this->roundedAverage($temperatures),
            'temperature_min_c' => $this->roundedMinimum($temperatures),
            'temperature_max_c' => $this->roundedMaximum($temperatures),
            'humidity_avg_percent' => $this->roundedAverage($humidities),
            'wind_avg_kph' => $this->roundedAverage($winds),
            'wind_max_kph' => $this->roundedMaximum($winds),
            'wind_gust_max_kph' => $this->roundedMaximum($gusts),
            'precipitation_total_mm' => round((float) $precipitation->sum(), 2),
            'rain_total_mm' => round((float) $rain->sum(), 2),
            'dominant_weather_code' => $dominantWeatherCode,
            'dominant_condition' => $this->describeWeatherCode($dominantWeatherCode),
            'hourly_observation_count' => max(
                $temperatures->count(),
                $humidities->count(),
                $weatherCodes->count()
            ),
        ];
    }

    public function summarizeStoredRows(Collection $rows): ?array
    {
        if ($rows->isEmpty()) {
            return null;
        }

        $conditions = $rows->pluck('dominant_condition')->filter();
        $calendarDays = $rows->groupBy(fn ($row) => Carbon::parse($row->observation_date)->toDateString());

        return [
            'coverage' => 'Observed daily context from '.$rows->min('observation_date').' through '.$rows->max('observation_date'),
            'latest_observation_date' => Carbon::parse($rows->max('observation_date'))->toDateString(),
            'fetched_at' => $rows->max('fetched_at')
                ? Carbon::parse($rows->max('fetched_at'), 'Asia/Manila')->toIso8601String()
                : null,
            'cache_status' => 'stored-history',
            'is_stale' => false,
            'avg_temp_c' => round((float) $rows->avg('temperature_avg_c'), 1),
            'min_temp_c' => round((float) $rows->min('temperature_min_c'), 1),
            'max_temp_c' => round((float) $rows->max('temperature_max_c'), 1),
            'avg_humidity' => round((float) $rows->avg('humidity_avg_percent'), 1),
            'avg_wind_kph' => round((float) $rows->avg('wind_avg_kph'), 1),
            'max_wind_kph' => round((float) $rows->max('wind_max_kph'), 1),
            'rainy_days' => $calendarDays->filter(
                fn (Collection $dayRows) => $dayRows->contains(fn ($row) => (float) ($row->precipitation_total_mm ?? 0) > 0)
            )->count(),
            'hot_days' => $calendarDays->filter(
                fn (Collection $dayRows) => (float) $dayRows->avg('temperature_max_c') >= 32
            )->count(),
            'total_precipitation_mm' => round((float) $rows->sum('precipitation_total_mm'), 1),
            'dominant_condition' => $conditions->countBy()->sortDesc()->keys()->first() ?? 'Unknown',
            'daily_observation_count' => $rows->count(),
            'observed_calendar_days' => $calendarDays->count(),
            'source' => self::SOURCE,
        ];
    }

    private function numericValues(array $values): Collection
    {
        return collect($values)->filter(fn ($value) => is_numeric($value))->map(fn ($value) => (float) $value)->values();
    }

    private function roundedAverage(Collection $values): ?float
    {
        return $values->isEmpty() ? null : round((float) $values->avg(), 2);
    }

    private function roundedMinimum(Collection $values): ?float
    {
        return $values->isEmpty() ? null : round((float) $values->min(), 2);
    }

    private function roundedMaximum(Collection $values): ?float
    {
        return $values->isEmpty() ? null : round((float) $values->max(), 2);
    }

    private function numericOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? round((float) $value, 1) : null;
    }

    private function describeWeatherCode(?int $code): string
    {
        return match (true) {
            $code === null => 'Unknown',
            $code === 0 => 'Clear sky',
            in_array($code, [1, 2, 3], true) => 'Partly cloudy',
            in_array($code, [45, 48], true) => 'Fog',
            in_array($code, [51, 53, 55, 56, 57], true) => 'Drizzle',
            in_array($code, [61, 63, 65, 66, 67, 80, 81, 82], true) => 'Rain',
            in_array($code, [71, 73, 75, 77, 85, 86], true) => 'Snow',
            in_array($code, [95, 96, 99], true) => 'Thunderstorm',
            default => 'Unknown',
        };
    }
}
