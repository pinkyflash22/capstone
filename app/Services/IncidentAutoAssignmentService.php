<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\Station;
use App\Models\StationOfficer;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class IncidentAutoAssignmentService
{
    public function assignRecentUnassignedIncidents(int $minutes = 60): array
    {
        $cutoff = now()->subMinutes($minutes);

        $incidents = Incident::with('marker')
            ->whereNull('team_id')
            ->where('status', 'Reported')
            ->where('created_at', '>=', $cutoff)
            ->orderBy('created_at', 'asc')
            ->get();

        $assignedIncident = null;

        foreach ($incidents as $incident) {
            $assignedIncident = $this->assignSingleIncident($incident);

            if ($assignedIncident !== null) {
                break;
            }
        }

        return [
            'checked' => $incidents->count(),
            'assigned' => $assignedIncident ? [$assignedIncident] : [],
        ];
    }

    public function assignSingleIncidentById(int $incidentId): ?array
    {
        $incident = Incident::with('marker')->find($incidentId);

        if (! $incident) {
            return null;
        }

        return $this->assignSingleIncident($incident);
    }

    public function assignSingleIncident(Incident $incident): ?array
    {
        return DB::transaction(function () use ($incident) {
            $lockedIncident = Incident::with('marker')
                ->whereKey($incident->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedIncident) {
                return null;
            }

            if ($lockedIncident->team_id || $lockedIncident->status !== 'Reported') {
                return null;
            }

            $marker = $lockedIncident->marker;
            if (! $marker || $marker->lat === null || $marker->lng === null) {
                return null;
            }

            $nearestStations = $this->getCandidateStationsForIncident(
                $lockedIncident,
                (float) $marker->lat,
                (float) $marker->lng
            );

            foreach ($nearestStations as $station) {
                $stationId = (int) $station->station_id;
                $team = $this->findAvailableTeamForStation($stationId);

                if (! $team) {
                    continue;
                }

                $lockedIncident->team_id = $team->team_id;
                $lockedIncident->status = 'Responding';
                $lockedIncident->updated_at = now();
                $lockedIncident->save();

                $this->markTeamResponding($team);

                return [
                    'incident_id' => $lockedIncident->incident_id,
                    'team_id' => $team->team_id,
                    'station_id' => $stationId,
                    'status' => 'en-route',
                    'team_status_updated' => true,
                    'created_at' => optional($lockedIncident->created_at)->toISOString(),
                ];
            }

            return null;
        });
    }

    private function calculateDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function getStationsSortedByDistance(float $lat, float $lng)
    {
        return Station::with('marker')
            ->get()
            ->filter(function ($station) {
                return $station->marker && $station->marker->lat !== null && $station->marker->lng !== null;
            })
            ->map(function ($station) use ($lat, $lng) {
                $station->distance_meters = $this->calculateDistanceMeters(
                    $lat,
                    $lng,
                    (float) $station->marker->lat,
                    (float) $station->marker->lng
                );

                return $station;
            })
            ->sortBy('distance_meters')
            ->values();
    }

    private function findAvailableTeamForStation(int $stationId): ?Team
    {
        $stationOfficerIds = StationOfficer::where('station_id', $stationId)
            ->pluck('station_officer_id')
            ->filter()
            ->values();

        if ($stationOfficerIds->isEmpty()) {
            return null;
        }

        return Team::where('status', 'available')
            ->whereIn('team_leader', $stationOfficerIds->all())
            ->lockForUpdate()
            ->orderBy('team_id')
            ->first();
    }

    private function getCandidateStationsForIncident(Incident $incident, float $lat, float $lng)
    {
        $stationLimit = strtolower((string) ($incident->marker?->category ?? '')) === 'fire' ? 5 : 1;

        return $this->getStationsSortedByDistance($lat, $lng)
            ->take($stationLimit);
    }

    private function markTeamResponding(Team $team): void
    {
        $team->status = 'responding';
        $team->updated_at = now();
        $team->save();

        $teamMemberStationOfficerIds = collect([
            $team->commander_driver,
            $team->pumper,
            $team->team_leader,
            $team->firefighter_officer_1,
            $team->firefighter_officer_2,
            $team->firefighter_officer_3,
            $team->rescue_radio,
        ])->filter()->unique()->values();

        if ($teamMemberStationOfficerIds->isNotEmpty()) {
            StationOfficer::whereIn('station_officer_id', $teamMemberStationOfficerIds->all())
                ->update([
                    'status' => 'dispatched',
                    'updated_at' => now(),
                ]);
        }
    }
}
