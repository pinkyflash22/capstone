<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentResponse;
use App\Models\StationOfficer;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IncidentResponderSnapshotService
{
    private const TEAM_MEMBER_COLUMNS = [
        'commander_driver',
        'pumper',
        'team_leader',
        'firefighter_officer_1',
        'firefighter_officer_2',
        'firefighter_officer_3',
        'rescue_radio',
    ];

    public function captureForIncident(Incident $incident): ?IncidentResponse
    {
        if (! $incident->incident_id || ! $incident->team_id) {
            return null;
        }

        $team = Team::query()->find($incident->team_id);
        if (! $team) {
            return null;
        }

        $officerIds = collect(self::TEAM_MEMBER_COLUMNS)
            ->map(fn (string $column) => $team->getAttribute($column))
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($officerIds->isEmpty()) {
            return null;
        }

        $responders = StationOfficer::query()
            ->whereIn('station_officer_id', $officerIds->all())
            ->get(['station_officer_id', 'station_id'])
            ->map(fn (StationOfficer $officer) => [
                'station_officer_id' => (int) $officer->station_officer_id,
                'station_id' => (int) $officer->station_id,
            ])
            ->values()
            ->all();

        if ($responders === []) {
            return null;
        }

        return DB::transaction(function () use ($incident, $responders) {
            $snapshot = IncidentResponse::query()
                ->where('incident_id', $incident->incident_id)
                ->lockForUpdate()
                ->first();

            if (! $snapshot) {
                return IncidentResponse::query()->create([
                    'incident_id' => (int) $incident->incident_id,
                    'responders' => $this->mergeResponders([], $responders),
                ]);
            }

            $snapshot->responders = $this->mergeResponders(
                is_array($snapshot->responders) ? $snapshot->responders : [],
                $responders
            );
            $snapshot->save();

            return $snapshot;
        });
    }

    public function mergeResponders(array $existing, array $incoming): array
    {
        return collect([...$existing, ...$incoming])
            ->filter(fn ($responder) => is_array($responder)
                && isset($responder['station_officer_id'], $responder['station_id']))
            ->map(fn (array $responder) => [
                'station_officer_id' => (int) $responder['station_officer_id'],
                'station_id' => (int) $responder['station_id'],
            ])
            ->unique(fn (array $responder) => $responder['station_officer_id'].'|'.$responder['station_id'])
            ->values()
            ->all();
    }

    public function attachResponderDetails(iterable $incidents): void
    {
        $incidentCollection = $incidents instanceof Collection ? $incidents : collect($incidents);
        $officerIds = $incidentCollection
            ->flatMap(fn (Incident $incident) => collect($incident->responderSnapshot?->responders ?? [])
                ->pluck('station_officer_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $officers = StationOfficer::query()
            ->with([
                'user:user_id,first_name,middle_name,last_name',
                'station:station_id,station_name',
            ])
            ->whereIn('station_officer_id', $officerIds->all())
            ->get(['station_officer_id', 'station_id', 'user_id', 'rank'])
            ->keyBy(fn (StationOfficer $officer) => (int) $officer->station_officer_id);

        $incidentCollection->each(function (Incident $incident) use ($officers) {
            $details = collect($incident->responderSnapshot?->responders ?? [])
                ->map(function ($responder) use ($officers) {
                    $officerId = (int) ($responder['station_officer_id'] ?? 0);
                    $stationId = (int) ($responder['station_id'] ?? 0);
                    $officer = $officers->get($officerId);
                    $name = collect([
                        $officer?->user?->first_name,
                        $officer?->user?->middle_name,
                        $officer?->user?->last_name,
                    ])->filter()->implode(' ');

                    return [
                        'station_officer_id' => $officerId,
                        'station_id' => $stationId,
                        'name' => $name ?: "Officer #{$officerId}",
                        'rank' => $officer?->rank,
                        'station_name' => $officer?->station?->station_name ?: "Station #{$stationId}",
                    ];
                })
                ->filter(fn (array $responder) => $responder['station_officer_id'] > 0)
                ->values()
                ->all();

            $incident->setAttribute('responder_details', $details);
        });
    }
}
