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
                'humidity' => $weather['humidity'],
                'rainfall' => $weather['rainfall'],
                'wind_speed' => $weather['wind_speed'],
                'wind_direction' => $weather['wind_direction'],
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

        $apiKey = trim((string) env('WEATHER_API_KEY', '1432615e15a14140963113518261203'));

        if ($apiKey === '') {
            return $this->emptyWeatherContext('derived');
        }

        try {
            $response = Http::timeout(15)->get('http://api.weatherapi.com/v1/current.json', [
                'key' => $apiKey,
                'q' => $lat . ',' . $lng,
                'aqi' => 'no',
            ]);

            if (! $response->ok()) {
                throw new \RuntimeException('Weather API returned status ' . $response->status());
            }

            $payload = $response->json();
            $current = $payload['current'] ?? [];

            return [
                'temperature' => isset($current['temp_c']) ? (float) $current['temp_c'] : null,
                'humidity' => isset($current['humidity']) ? (float) $current['humidity'] : null,
                'rainfall' => isset($current['precip_mm']) ? (float) $current['precip_mm'] : null,
                'wind_speed' => isset($current['wind_kph']) ? (float) $current['wind_kph'] : null,
                'wind_direction' => $current['wind_dir'] ?? null,
                'weather_condition' => $current['condition']['text'] ?? null,
                'context_source' => 'weatherapi',
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
            'humidity' => null,
            'rainfall' => null,
            'wind_speed' => null,
            'wind_direction' => null,
            'weather_condition' => null,
            'context_source' => $contextSource,
        ];
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
