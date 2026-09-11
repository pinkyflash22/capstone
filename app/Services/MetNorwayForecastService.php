<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class MetNorwayForecastService
{
    public function fetch(float $latitude, float $longitude, int $days): array
    {
        $response = Http::connectTimeout(3)->timeout(10)
            ->withUserAgent(config('services.met_norway.user_agent'))
            ->acceptJson()->get('https://api.met.no/weatherapi/locationforecast/2.0/compact', [
                'lat' => number_format($latitude, 3, '.', ''),
                'lon' => number_format($longitude, 3, '.', ''),
            ]);
        if (!$response->successful()) throw new \RuntimeException('Backup weather provider returned HTTP '.$response->status().'.');
        $series = $response->json('properties.timeseries');
        if (!is_array($series) || !$series) throw new \RuntimeException('Backup weather provider returned no forecast.');

        $today = now('Asia/Manila')->startOfDay();
        $end = $today->copy()->addDays($days);
        $groups = [];
        foreach ($series as $row) {
            if (empty($row['time'])) continue;
            $time = Carbon::parse($row['time'])->timezone('Asia/Manila');
            if ($time->lt($today) || $time->gte($end)) continue;
            $instant = data_get($row, 'data.instant.details', []);
            if (!is_numeric($instant['air_temperature'] ?? null)) continue;
            $groups[$time->toDateString()][] = [
                'temperature' => (float) $instant['air_temperature'],
                'humidity' => is_numeric($instant['relative_humidity'] ?? null) ? (float) $instant['relative_humidity'] : null,
                'wind' => is_numeric($instant['wind_speed'] ?? null) ? round((float) $instant['wind_speed'] * 3.6, 1) : null,
                'symbol' => data_get($row, 'data.next_1_hours.summary.symbol_code')
                    ?? data_get($row, 'data.next_6_hours.summary.symbol_code')
                    ?? data_get($row, 'data.next_12_hours.summary.symbol_code', ''),
            ];
        }
        ksort($groups);
        $cards = [];
        foreach ($groups as $date => $points) {
            $points = collect($points);
            $symbols = strtolower($points->pluck('symbol')->implode(' '));
            [$condition, $code] = match (true) {
                str_contains($symbols, 'thunder') => ['Thunderstorm', 95],
                str_contains($symbols, 'snow'), str_contains($symbols, 'sleet') => ['Snow or sleet', 73],
                str_contains($symbols, 'rain') => ['Rain', 63],
                str_contains($symbols, 'fog') => ['Fog', 45],
                str_contains($symbols, 'cloudy') => ['Cloudy', 3],
                str_contains($symbols, 'fair') => ['Mainly clear', 1],
                str_contains($symbols, 'clearsky') => ['Clear sky', 0],
                default => ['Unavailable', null],
            };
            $cards[] = [
                'date' => $date, 'day' => Carbon::parse($date)->format('D'), 'date_label' => Carbon::parse($date)->format('M j'),
                'condition' => $condition, 'weather_code' => $code,
                'min_temp_c' => $points->min('temperature'), 'max_temp_c' => $points->max('temperature'),
                'humidity_percent' => $points->whereNotNull('humidity')->isEmpty() ? null : round($points->avg('humidity'), 1),
                'wind_kph' => $points->whereNotNull('wind')->isEmpty() ? null : $points->max('wind'),
                // Do not treat overlapping interval totals as daily precipitation,
                // or invent a rain probability that this compact feed omits.
                'precipitation_probability_percent' => null, 'precipitation_mm' => null,
            ];
        }
        if (!$cards || $cards[0]['date'] !== $today->toDateString()) throw new \RuntimeException('Backup forecast has no usable data for today.');

        return [
            'cards' => $cards, 'current' => [], 'provider' => 'MET Norway',
            'provider_url' => 'https://www.met.no/en',
            'fetched_at' => now('Asia/Manila')->toIso8601String(),
            'warning' => 'Open-Meteo is unavailable. Showing MET Norway forecast samples; temperature ranges and humidity are calculated from available time points. Daily rain totals and probabilities are unavailable.',
        ];
    }
}
