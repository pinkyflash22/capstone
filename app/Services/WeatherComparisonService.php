<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

/** Isolated weather demonstration: no database access or production weather-cache changes. */
class WeatherComparisonService
{
    public const LATITUDE = 15.8222;
    public const LONGITUDE = 120.3297;

    public function fetch(string $provider, string $mode, string $historyDate): array
    {
        $today = CarbonImmutable::now('Asia/Manila')->startOfDay();
        $start = $mode === 'history' ? CarbonImmutable::parse($historyDate, 'Asia/Manila') : $today;
        $end = $mode === 'history' ? $start : $start->addDays(2);
        $result = [
            'provider' => $provider, 'mode' => $mode, 'success' => false,
            'start_date' => $start->toDateString(), 'end_date' => $end->toDateString(),
            'requested_location' => ['latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE],
            'timezone' => 'Asia/Manila', 'fetched_at' => CarbonImmutable::now('UTC')->toIso8601String(),
            'aggregation' => 'Daily summaries calculated from 24 hourly samples; incomplete variables stay null.',
            'days' => [],
        ];
        $key = match ($provider) {
            'weatherapi' => trim((string) config('gis_demo.weatherapi_key')),
            'visualcrossing' => trim((string) config('gis_demo.visual_crossing_key')),
            default => null,
        };
        if ($key === '') {
            return array_merge($result, ['status' => 'missing_key', 'message' => $provider === 'weatherapi'
                ? 'Add WEATHERAPI_KEY locally. Historical access and forecast length depend on your plan.'
                : 'Add VISUAL_CROSSING_API_KEY locally. Access depends on your account quota and plan.']);
        }

        $started = microtime(true);
        try {
            $client = Http::acceptJson()->connectTimeout(5)->timeout(25);
            if ($provider === 'openmeteo') {
                $url = $mode === 'history' ? 'https://archive-api.open-meteo.com/v1/archive' : 'https://api.open-meteo.com/v1/forecast';
                $response = $client->get($url, [
                    'latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE,
                    'start_date' => $start->toDateString(), 'end_date' => $end->toDateString(),
                    'timezone' => 'Asia/Manila', 'temperature_unit' => 'celsius', 'wind_speed_unit' => 'kmh',
                    'precipitation_unit' => 'mm',
                    'hourly' => 'temperature_2m,relative_humidity_2m,precipitation,wind_speed_10m',
                ]);
            } elseif ($provider === 'weatherapi') {
                $response = $client->get('https://api.weatherapi.com/v1/'.($mode === 'history' ? 'history.json' : 'forecast.json'), array_merge([
                    'key' => $key, 'q' => self::LATITUDE.','.self::LONGITUDE,
                ], $mode === 'history' ? ['dt' => $start->toDateString()] : ['days' => 3, 'aqi' => 'no', 'alerts' => 'no']));
            } elseif ($provider === 'visualcrossing') {
                $response = $client->get('https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/'.self::LATITUDE.','.self::LONGITUDE.'/'.$start->toDateString().'/'.$end->toDateString(), [
                    'key' => $key, 'unitGroup' => 'metric', 'contentType' => 'json', 'include' => 'days,hours',
                    'elements' => 'datetime,temp,humidity,precip,windspeed',
                ]);
            } else {
                throw new \InvalidArgumentException('Unknown weather provider.');
            }
            $result['request_ms'] = round((microtime(true) - $started) * 1000);
            $result['http_status'] = $response->status();
            if (! $response->successful()) {
                return array_merge($result, ['status' => 'unavailable', 'message' => match ($response->status()) {
                    401, 403 => 'Request denied. Check the API key and plan access for this date and endpoint.',
                    429 => 'Provider rate limit or quota reached. Try again later.',
                    400 => 'Provider rejected this request or date. Check endpoint coverage and account access.',
                    default => 'The provider could not complete this request.',
                }]);
            }
            $payload = $response->json();
            $samples = [];
            if ($provider === 'openmeteo') {
                foreach (data_get($payload, 'hourly.time', []) as $i => $time) {
                    $samples[] = ['time' => $time,
                        'temp' => data_get($payload, "hourly.temperature_2m.{$i}"),
                        'humidity' => data_get($payload, "hourly.relative_humidity_2m.{$i}"),
                        'precip' => data_get($payload, "hourly.precipitation.{$i}"),
                        'wind' => data_get($payload, "hourly.wind_speed_10m.{$i}")];
                }
                $result['resolved_location'] = ['latitude' => $payload['latitude'] ?? null, 'longitude' => $payload['longitude'] ?? null];
                $result['source_note'] = $mode === 'history' ? 'Open-Meteo archive/reanalysis; not a historical forecast issued on that date.' : 'Open-Meteo forecast model output.';
            } elseif ($provider === 'weatherapi') {
                foreach (data_get($payload, 'forecast.forecastday', []) as $day) {
                    foreach ($day['hour'] ?? [] as $hour) {
                        $samples[] = ['time' => $hour['time'] ?? '', 'temp' => $hour['temp_c'] ?? null,
                            'humidity' => $hour['humidity'] ?? null, 'precip' => $hour['precip_mm'] ?? null, 'wind' => $hour['wind_kph'] ?? null];
                    }
                }
                $result['timezone'] = data_get($payload, 'location.tz_id', 'Asia/Manila');
                $result['resolved_location'] = ['latitude' => data_get($payload, 'location.lat'), 'longitude' => data_get($payload, 'location.lon')];
                $result['source_note'] = 'WeatherAPI provider hourly data; date access and forecast horizon depend on plan.';
            } else {
                foreach ($payload['days'] ?? [] as $day) {
                    foreach ($day['hours'] ?? [] as $hour) {
                        $samples[] = ['time' => ($day['datetime'] ?? '').'T'.($hour['datetime'] ?? ''),
                            'temp' => $hour['temp'] ?? null, 'humidity' => $hour['humidity'] ?? null,
                            'precip' => $hour['precip'] ?? null, 'wind' => $hour['windspeed'] ?? null];
                    }
                }
                $result['timezone'] = $payload['timezone'] ?? 'Asia/Manila';
                $result['resolved_location'] = ['latitude' => $payload['latitude'] ?? null, 'longitude' => $payload['longitude'] ?? null];
                $result['source_note'] = 'Visual Crossing hourly Timeline data; historical and forecast products can use different underlying sources.';
            }
            if (! $samples) {
                return array_merge($result, ['status' => 'no_data', 'message' => 'No hourly weather data was returned. Check endpoint coverage and plan access.']);
            }
            $dates = [];
            for ($date = $start; $date->lte($end); $date = $date->addDay()) {
                $dates[] = $date->toDateString();
            }
            $result['days'] = $this->aggregate($samples, $dates);
            $result['success'] = true;
            $result['status'] = 'received';
            $result['message'] = 'Live provider data received. Null values indicate missing or incomplete hourly coverage.';
            return $result;
        } catch (\Throwable $exception) {
            // Authenticated URLs may contain keys. Never log/return the exception or raw payload.
            return array_merge($result, ['status' => 'unavailable', 'message' => 'Provider connection failed or the weather response was unusable.']);
        }
    }

    private function aggregate(array $samples, array $dates): array
    {
        $rows = [];
        foreach ($dates as $date) {
            // Unique local hours prevent duplicated provider samples inflating totals.
            $hours = [];
            foreach ($samples as $sample) {
                $time = str_replace(' ', 'T', (string) $sample['time']);
                if (substr($time, 0, 10) === $date) {
                    $hours[substr($time, 0, 13)] = $sample;
                }
            }
            $values = [];
            foreach (['temp', 'humidity', 'precip', 'wind'] as $field) {
                $values[$field] = array_values(array_filter(array_map(fn ($hour) => is_numeric($hour[$field] ?? null) ? (float) $hour[$field] : null, $hours), fn ($value) => $value !== null && is_finite($value)));
            }
            $complete = fn ($field) => count($values[$field]) === 24;
            $rows[] = [
                'date' => $date, 'hour_counts' => array_map('count', $values),
                'temperature_mean' => $complete('temp') ? round(array_sum($values['temp']) / 24, 2) : null,
                'temperature_max' => $complete('temp') ? max($values['temp']) : null,
                'temperature_min' => $complete('temp') ? min($values['temp']) : null,
                'humidity_mean' => $complete('humidity') ? round(array_sum($values['humidity']) / 24, 2) : null,
                'precipitation_total' => $complete('precip') ? round(array_sum($values['precip']), 2) : null,
                'wind_max' => $complete('wind') ? max($values['wind']) : null,
            ];
        }
        return $rows;
    }
}
