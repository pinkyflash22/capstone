<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\StationOfficer;
use App\Models\Team;
use Illuminate\Support\Facades\Schema;

class IncidentDispatchStateService
{
    public function describe(Incident $incident): array
    {
        $priority = $this->priority($incident);
        $status = strtolower(trim((string) $incident->status));

        if ($this->supportsQueueFields() && $incident->duplicate_of_incident_id) {
            return $this->result($priority, 'linked_to_active_incident', 'Linked to active dispatch');
        }

        if ($incident->team_id || in_array($status, ['responding', 'en-route', 'en route', 'on scene', 'on-scene'], true)) {
            return $this->result($priority, 'dispatched', 'Team dispatched');
        }

        if ($status !== 'reported') {
            return $this->result($priority, 'not_waiting', ucfirst($status ?: 'Updated'));
        }

        $stationId = (int) ($incident->station_id ?? 0);
        if ($stationId < 1) {
            return $this->result($priority, 'requesting_station', 'Requesting a responding station');
        }

        $incident->loadMissing('marker');
        $requiresFiretruck = app(IncidentResourceDispatchService::class)->incidentRequiresFiretruck($incident);
        $hasTeam = $this->hasAvailableTeam($stationId);
        $hasFiretruck = ! $requiresFiretruck || $this->availableFiretruckCount($stationId) > 0;

        if ((! $hasTeam || ! $hasFiretruck) && $this->hasCapableAlternativeStation($stationId, $requiresFiretruck)) {
            return $this->result($priority, 'requesting_other_station', 'Requesting another station');
        }

        if (! $hasTeam) {
            return $this->result($priority, 'waiting_for_team', 'Waiting for an available response team');
        }

        if (! $hasFiretruck) {
            return $this->result($priority, 'waiting_for_apparatus', 'Waiting for an available firetruck');
        }

        return $this->result($priority, 'awaiting_dispatch', 'Awaiting dispatch confirmation');
    }

    private function priority(Incident $incident): string
    {
        if ($this->supportsQueueFields() && filled($incident->dispatch_priority)) {
            return strtolower((string) $incident->dispatch_priority);
        }

        if (! Schema::hasTable('incident_report')) {
            return 'medium';
        }

        $incident->loadMissing('incidentReport');
        return match (strtolower(trim((string) $incident->incidentReport?->severity))) {
            'code red', 'critical' => 'critical',
            'high' => 'high',
            'low' => 'low',
            default => 'medium',
        };
    }

    private function hasAvailableTeam(int $stationId): bool
    {
        if (! Schema::hasTable('teams') || ! Schema::hasTable('station_officers')) {
            return false;
        }

        return Team::query()
            ->where('station_id', $stationId)
            ->where('status', 'available')
            ->get()
            ->contains(function (Team $team) use ($stationId) {
                $memberIds = collect([
                    $team->commander_driver, $team->pumper, $team->team_leader,
                    $team->firefighter_officer_1, $team->firefighter_officer_2,
                    $team->firefighter_officer_3, $team->rescue_radio,
                ])->filter()->unique();

                return $memberIds->count() >= 2
                    && StationOfficer::query()->where('station_id', $stationId)
                        ->whereIn('station_officer_id', $memberIds)
                        ->where('status', 'available')
                        ->count() >= 2;
            });
    }

    private function availableFiretruckCount(int $stationId): int
    {
        if (! Schema::hasTable('apparatus') && ! Schema::hasTable('equipment_to_stations')) {
            return 0;
        }

        return app(IncidentResourceDispatchService::class)->stationAvailableFiretruckCount($stationId);
    }

    private function hasCapableAlternativeStation(int $primaryStationId, bool $requiresFiretruck): bool
    {
        if (! Schema::hasTable('stations')) {
            return false;
        }

        return \App\Models\Station::query()
            ->where('station_id', '!=', $primaryStationId)
            ->pluck('station_id')
            ->contains(fn ($stationId) => $this->hasAvailableTeam((int) $stationId)
                && (! $requiresFiretruck || $this->availableFiretruckCount((int) $stationId) > 0));
    }

    private function supportsQueueFields(): bool
    {
        return Schema::hasColumn('incidents', 'dispatch_priority');
    }

    private function result(string $priority, string $state, string $label): array
    {
        return ['dispatch_priority' => $priority, 'dispatch_state' => $state, 'dispatch_state_label' => $label];
    }
}
