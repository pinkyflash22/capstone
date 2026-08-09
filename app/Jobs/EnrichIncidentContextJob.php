<?php

namespace App\Jobs;

use App\Models\Incident;
use App\Models\IncidentContext;
use App\Services\BoundaryMatcherService;
use App\Support\BarangayNotificationService;
use App\Support\HistoricalFireIncidentSynchronizer;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class EnrichIncidentContextJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $incidentId)
    {
    }

    public function handle(BoundaryMatcherService $boundaryMatcherService): void
    {
        $incident = Incident::with(['marker', 'reporter.address', 'barangay'])->find($this->incidentId);

        if (! $incident || ! $incident->marker) {
            return;
        }

        $timestamp = $incident->created_at ? Carbon::parse($incident->created_at) : now();
        $marker = $incident->marker;
        $this->resolveIncidentBarangay($incident, $boundaryMatcherService);
        $weather = $this->fetchWeatherContext($marker->lat, $marker->lng);
        $eventPeriod = $this->resolveEventPeriod($timestamp);

        IncidentContext::updateOrCreate(
            ['incident_id' => $incident->incident_id],
            [
                'incident_month' => (int) $timestamp->month,
                'season' => $this->resolveSeason($timestamp),
                'day_of_week' => $timestamp->format('l'),
                'time_of_day_bucket' => $this->resolveTimeOfDayBucket($timestamp),
                'is_holiday_or_event_period' => $eventPeriod['is_holiday_or_event_period'],
                'event_period_label' => $eventPeriod['event_period_label'],
                'temperature' => $weather['temperature'],
                'temperature_2m' => $weather['temperature_2m'],
                'humidity' => $weather['humidity'],
                'relative_humidity_2m' => $weather['relative_humidity_2m'],
                'precipitation' => $weather['precipitation'],
                'rain' => $weather['rain'],
                'rainfall' => $weather['rainfall'],
                'wind_speed' => $weather['wind_speed'],
                'wind_speed_10m' => $weather['wind_speed_10m'],
                'wind_gusts' => $weather['wind_gusts'],
                'wind_gusts_10m' => $weather['wind_gusts_10m'],
                'wind_direction' => $weather['wind_direction'],
                'wind_direction_10m' => $weather['wind_direction_10m'],
                'weather_code' => $weather['weather_code'],
                'weather_condition' => $weather['weather_condition'],
                'is_dry_season' => $this->isDrySeason($timestamp),
                'context_source' => $weather['context_source'],
                'captured_at' => now(),
            ]
        );

        HistoricalFireIncidentSynchronizer::syncForIncidentId((int) $incident->incident_id);
        BarangayNotificationService::notifyNearbyFireIncident($incident);
    }

    private function resolveIncidentBarangay(Incident $incident, BoundaryMatcherService $boundaryMatcherService): void
    {
        if (! $this->supportsIncidentBarangayResolution()) {
            return;
        }

        $marker = $incident->marker;

        if (! $marker || $marker->lat === null || $marker->lng === null) {
            $incident->forceFill([
                'barangay_id' => null,
                'barangay_resolution_status' => 'failed',
                'barangay_resolution_source' => 'missing-marker-coordinates',
                'barangay_resolved_at' => now(),
            ])->save();

            return;
        }

        try {
            $boundary = $boundaryMatcherService->locateBarangayBoundary(
                (float) $marker->lat,
                (float) $marker->lng
            );

            if ($boundary?->barangay_id) {
                $incident->forceFill([
                    'barangay_id' => (int) $boundary->barangay_id,
                    'barangay_resolution_status' => 'matched',
                    'barangay_resolution_source' => $boundary->source ?: 'boundary-location',
                    'barangay_resolved_at' => now(),
                ])->save();

                return;
            }

            $incident->forceFill([
                'barangay_id' => null,
                'barangay_resolution_status' => 'unmatched',
                'barangay_resolution_source' => 'boundary-location',
                'barangay_resolved_at' => now(),
            ])->save();
        } catch (\Throwable $exception) {
            Log::warning('Incident barangay enrichment failed: ' . $exception->getMessage(), [
                'incident_id' => $this->incidentId,
            ]);

            $incident->forceFill([
                'barangay_id' => null,
                'barangay_resolution_status' => 'failed',
                'barangay_resolution_source' => 'boundary-location',
                'barangay_resolved_at' => now(),
            ])->save();
        }
    }

    private function supportsIncidentBarangayResolution(): bool
    {
        return Schema::hasColumn('incidents', 'barangay_id')
            && Schema::hasColumn('incidents', 'barangay_resolution_status')
            && Schema::hasColumn('incidents', 'barangay_resolution_source')
            && Schema::hasColumn('incidents', 'barangay_resolved_at');
    }

    private function fetchWeatherContext($lat, $lng): array
    {
        if ($lat === null || $lng === null) {
            return $this->emptyWeatherContext('derived');
        }

        try {
            $response = Http::timeout(15)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
                'current' => 'temperature_2m,relative_humidity_2m,precipitation,rain,weather_code,wind_speed_10m,wind_direction_10m,wind_gusts_10m',
                'timezone' => 'Asia/Manila',
            ]);

            if (! $response->ok()) {
                throw new \RuntimeException('Open-Meteo API returned status ' . $response->status());
            }

            $payload = $response->json();
            $current = is_array($payload['current'] ?? null) ? $payload['current'] : [];
            $weatherCode = isset($current['weather_code']) ? (int) $current['weather_code'] : null;
            $temperature2m = isset($current['temperature_2m']) ? (float) $current['temperature_2m'] : null;
            $relativeHumidity2m = isset($current['relative_humidity_2m']) ? (float) $current['relative_humidity_2m'] : null;
            $precipitation = isset($current['precipitation']) ? (float) $current['precipitation'] : null;
            $rain = isset($current['rain']) ? (float) $current['rain'] : null;
            $windSpeed10m = isset($current['wind_speed_10m']) ? (float) $current['wind_speed_10m'] : null;
            $windDirection10m = isset($current['wind_direction_10m']) ? (float) $current['wind_direction_10m'] : null;
            $windGusts10m = isset($current['wind_gusts_10m']) ? (float) $current['wind_gusts_10m'] : null;

            return [
                'temperature' => $temperature2m,
                'temperature_2m' => $temperature2m,
                'humidity' => $relativeHumidity2m,
                'relative_humidity_2m' => $relativeHumidity2m,
                'precipitation' => $precipitation,
                'rain' => $rain,
                'rainfall' => $precipitation,
                'wind_speed' => $windSpeed10m,
                'wind_speed_10m' => $windSpeed10m,
                'wind_gusts' => $windGusts10m,
                'wind_gusts_10m' => $windGusts10m,
                'wind_direction' => $windDirection10m !== null ? (string) $windDirection10m : null,
                'wind_direction_10m' => $windDirection10m,
                'weather_code' => $weatherCode,
                'weather_condition' => $this->describeOpenMeteoWeatherCode($weatherCode),
                'context_source' => 'open-meteo',
            ];
        } catch (\Throwable $exception) {
            Log::warning('Incident context weather enrichment failed: ' . $exception->getMessage(), [
                'incident_id' => $this->incidentId,
            ]);

            return $this->emptyWeatherContext('derived');
        }
    }

    private function emptyWeatherContext(string $contextSource): array
    {
        return [
            'temperature' => null,
            'temperature_2m' => null,
            'humidity' => null,
            'relative_humidity_2m' => null,
            'precipitation' => null,
            'rain' => null,
            'rainfall' => null,
            'wind_speed' => null,
            'wind_speed_10m' => null,
            'wind_gusts' => null,
            'wind_gusts_10m' => null,
            'wind_direction' => null,
            'wind_direction_10m' => null,
            'weather_code' => null,
            'weather_condition' => null,
            'context_source' => $contextSource,
        ];
    }

    private function describeOpenMeteoWeatherCode(?int $code): ?string
    {
        return match ($code) {
            0 => 'Clear sky',
            1 => 'Mainly clear',
            2 => 'Partly cloudy',
            3 => 'Overcast',
            45, 48 => 'Fog',
            51, 53, 55 => 'Drizzle',
            56, 57 => 'Freezing drizzle',
            61, 63, 65 => 'Rain',
            66, 67 => 'Freezing rain',
            71, 73, 75 => 'Snow fall',
            77 => 'Snow grains',
            80, 81, 82 => 'Rain showers',
            85, 86 => 'Snow showers',
            95 => 'Thunderstorm',
            96, 99 => 'Thunderstorm with hail',
            default => null,
        };
    }

    private function resolveSeason(Carbon $timestamp): string
    {
        return match (true) {
            in_array($timestamp->month, [12, 1, 2], true) => 'cool_dry',
            in_array($timestamp->month, [3, 4, 5], true) => 'hot_dry',
            default => 'rainy',
        };
    }

    private function isDrySeason(Carbon $timestamp): bool
    {
        return in_array($timestamp->month, [11, 12, 1, 2, 3, 4, 5], true);
    }

    private function resolveTimeOfDayBucket(Carbon $timestamp): string
    {
        $hour = (int) $timestamp->hour;

        return match (true) {
            $hour >= 5 && $hour < 12 => 'morning',
            $hour >= 12 && $hour < 17 => 'afternoon',
            $hour >= 17 && $hour < 21 => 'evening',
            default => 'night',
        };
    }

    private function resolveEventPeriod(Carbon $timestamp): array
    {
        $monthDay = $timestamp->format('m-d');
        $label = null;

        if (in_array($monthDay, ['12-24', '12-25', '12-26', '12-31', '01-01'], true)) {
            $label = 'Christmas / New Year period';
        } elseif (in_array($monthDay, ['11-01', '11-02'], true)) {
            $label = 'Undas period';
        } elseif (in_array($monthDay, ['06-12'], true)) {
            $label = 'Independence Day';
        } elseif (in_array($monthDay, ['04-09'], true)) {
            $label = 'Araw ng Kagitingan';
        }

        return [
            'is_holiday_or_event_period' => $label !== null,
            'event_period_label' => $label,
        ];
    }
}
