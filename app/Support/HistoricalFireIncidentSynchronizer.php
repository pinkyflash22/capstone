<?php

namespace App\Support;

use App\Models\BarangayFireRecord;
use App\Models\HistoricalFireIncident;
use App\Models\Incident;

class HistoricalFireIncidentSynchronizer
{
    private const LOCATION_REVIEW_NOTE = '[Location review required] Confirm the fire incident barangay from its actual location. The reporter home address is not used.';

    public static function trySyncForIncidentId(?int $incidentId): bool
    {
        try {
            self::syncForIncidentId($incidentId);

            return true;
        } catch (\Throwable $exception) {
            // Investigation reports are the primary record. Derived historical
            // analytics can be rebuilt later and must not invalidate that save.
            report($exception);

            return false;
        }
    }

    public static function syncForIncidentId(?int $incidentId): void
    {
        if (! $incidentId) {
            return;
        }

        $incident = Incident::with([
            'marker',
            'context',
            'spotInvestigationReport.progressInvestigation.finalInvestigation',
        ])->find($incidentId);

        if (! $incident || strtolower(trim((string) ($incident->marker?->category ?? ''))) !== 'fire') {
            return;
        }

        $barangayFireRecord = BarangayFireRecord::with('barangayHall')
            ->where('incident_id', $incident->incident_id)
            ->latest('barangay_fire_record_id')
            ->first();

        $spotReport = $incident->spotInvestigationReport;
        $progressReport = $spotReport?->progressInvestigation;
        $finalReport = $progressReport?->finalInvestigation;
        $context = $incident->context;
        $occurredAt = $incident->occurred_at ?? $incident->created_at ?? now();

        $historical = HistoricalFireIncident::firstOrNew([
            'incident_id' => $incident->incident_id,
        ]);

        // The incident jurisdiction describes the fire location, not where the
        // reporter lives. A linked barangay fire record is the only fallback
        // source for a newly synchronized assignment.
        $incidentBarangayId = (int) ($incident->barangay_id ?? 0);
        $recordBarangayId = (int) ($barangayFireRecord?->barangayHall?->barangay_id ?? 0);
        $resolvedBarangayId = $incidentBarangayId > 0
            ? $incidentBarangayId
            : ($recordBarangayId > 0 ? $recordBarangayId : null);
        $needsLocationReview = $resolvedBarangayId === null;
        $existingNotes = str_replace(self::LOCATION_REVIEW_NOTE, '', (string) $historical->notes);
        $notes = static::buildNotes($barangayFireRecord?->notes, $spotReport, $progressReport, trim($existingNotes) ?: null);
        if ($needsLocationReview) {
            $notes = trim(($notes ? $notes . "\n" : '') . self::LOCATION_REVIEW_NOTE);
        }

        $historical->fill([
            // Preserve a previous assignment for review rather than erasing it.
            // An unresolved record is explicitly unverified below.
            'barangay_id' => $resolvedBarangayId ?? $historical->barangay_id,
            'incident_date' => $incident->occurred_at?->toDateString()
                ?? $spotReport?->fire_started?->toDateString()
                ?? $barangayFireRecord?->incident_date?->toDateString()
                ?? $historical->incident_date
                ?? $occurredAt->toDateString(),
            'incident_time' => $incident->occurred_at?->format('H:i:s')
                ?? $spotReport?->fire_started?->format('H:i:s')
                ?? $historical->incident_time
                ?? $occurredAt->format('H:i:s'),
            'source_name' => $historical->source_name ?: 'System Fire Incident Report',
            'source_type' => 'system_incident',
            'source_reference' => $historical->source_reference ?: 'incident:' . $incident->incident_id,
            'fire_classification' => $barangayFireRecord?->fire_classification
                ?? $historical->fire_classification
                ?? 'undetermined',
            'fire_cause' => $progressReport?->fire_cause
                ?? $barangayFireRecord?->fire_cause
                ?? $historical->fire_cause,
            'incident_location' => $finalReport?->exact_address
                ?? static::resolveIncidentLocation($incident)
                ?? $historical->incident_location,
            'incident_count' => $barangayFireRecord?->incident_count
                ?? $historical->incident_count
                ?? 1,
            'injured_count' => (int) ($spotReport?->injured ?? $historical->injured_count ?? 0),
            'death_count' => (int) ($spotReport?->dead ?? $historical->death_count ?? 0),
            'estimated_damage' => $finalReport?->total_damage
                ?? $spotReport?->estimated_damage
                ?? $historical->estimated_damage,
            'temperature' => $historical->temperature
                ?? ($context?->temperature !== null ? (float) $context->temperature : null),
            'temperature_2m' => $historical->temperature_2m
                ?? ($context?->temperature_2m !== null ? (float) $context->temperature_2m : null),
            'humidity' => $historical->humidity
                ?? ($context?->humidity !== null ? (float) $context->humidity : null),
            'relative_humidity_2m' => $historical->relative_humidity_2m
                ?? ($context?->relative_humidity_2m !== null ? (float) $context->relative_humidity_2m : null),
            'precipitation' => $historical->precipitation
                ?? ($context?->precipitation !== null ? (float) $context->precipitation : null),
            'rain' => $historical->rain
                ?? ($context?->rain !== null ? (float) $context->rain : null),
            'wind_speed' => $historical->wind_speed
                ?? ($context?->wind_speed !== null ? (float) $context->wind_speed : null),
            'wind_speed_10m' => $historical->wind_speed_10m
                ?? ($context?->wind_speed_10m !== null ? (float) $context->wind_speed_10m : null),
            'wind_gusts' => $historical->wind_gusts
                ?? ($context?->wind_gusts !== null ? (float) $context->wind_gusts : null),
            'wind_gusts_10m' => $historical->wind_gusts_10m
                ?? ($context?->wind_gusts_10m !== null ? (float) $context->wind_gusts_10m : null),
            'wind_direction' => $historical->wind_direction
                ?? $context?->wind_direction,
            'wind_direction_10m' => $historical->wind_direction_10m
                ?? ($context?->wind_direction_10m !== null ? (float) $context->wind_direction_10m : null),
            'weather_code' => $historical->weather_code
                ?? $context?->weather_code,
            'weather_condition' => $historical->weather_condition
                ?? $context?->weather_condition,
            'notes' => $notes,
        ]);

        if (! $historical->exists || $needsLocationReview) {
            $historical->is_verified = false;
        }

        $historical->save();
    }

    private static function resolveIncidentLocation(Incident $incident): ?string
    {
        $description = trim((string) ($incident->marker?->description ?? ''));
        if ($description !== '') {
            return $description;
        }

        $title = trim((string) ($incident->marker?->title ?? ''));
        return $title !== '' ? $title : null;
    }

    private static function buildNotes(?string $barangayNotes, $spotReport, $progressReport, ?string $existingNotes): ?string
    {
        $segments = [];

        if ($barangayNotes) {
            $segments[] = trim($barangayNotes);
        }

        if ($spotReport?->owner) {
            $segments[] = 'Owner: ' . trim((string) $spotReport->owner);
        }

        if ($spotReport?->occupancy) {
            $segments[] = 'Occupancy: ' . trim((string) $spotReport->occupancy);
        }

        if ($spotReport?->estimated_area !== null) {
            $segments[] = 'Estimated area: ' . trim((string) $spotReport->estimated_area);
        }

        if ($progressReport?->responsible_arson) {
            $segments[] = 'Responsible / arson note: ' . trim((string) $progressReport->responsible_arson);
        }

        if (empty($segments)) {
            return $existingNotes;
        }

        return implode("\n", $segments);
    }
}
