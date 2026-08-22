<?php

namespace App\Services;

use App\Models\Apparatus;
use App\Models\EquipmentToStation;
use App\Models\Incident;
use App\Models\IncidentResponseUnit;
use App\Models\Station;
use App\Models\StationOfficer;
use App\Models\Team;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class IncidentResourceDispatchService
{
    private const ACTIVE_UNIT_STATUSES = ['reserved', 'en_route', 'on_scene'];

    public function reservePrimary(Incident $incident, Team $team, int $stationId, ?float $distanceMeters = null, ?int $requestedBy = null): ?IncidentResponseUnit
    {
        $existing = IncidentResponseUnit::query()
            ->where('incident_id', $incident->incident_id)
            ->where('team_id', $team->team_id)
            ->whereIn('status', self::ACTIVE_UNIT_STATUSES)
            ->first();
        if ($existing) return $existing;

        return $this->reserveUnit($incident, $team, $stationId, 'primary', $requestedBy, $distanceMeters);
    }

    public function requestBackup(Incident $incident, int $userId): IncidentResponseUnit
    {
        return DB::transaction(function () use ($incident, $userId) {
            $lockedIncident = Incident::query()->with('marker')->whereKey($incident->incident_id)->lockForUpdate()->firstOrFail();
            if (! $this->incidentRequiresFiretruck($lockedIncident)) {
                throw ValidationException::withMessages(['incident' => 'Firetruck backup is available only for fire incidents.']);
            }
            if (! in_array(strtolower((string) $lockedIncident->status), ['responding', 'on scene'], true)) {
                throw ValidationException::withMessages(['incident' => 'Backup can only be requested for an active responding or on-scene incident.']);
            }

            $requester = StationOfficer::query()->where('user_id', $userId)->first();
            if (! $requester || ! $this->officerIsRespondingToIncident($requester, $lockedIncident)) {
                throw new AuthorizationException('Only a firefighter assigned to this response can request backup.');
            }

            $excludedTeamIds = collect([$lockedIncident->team_id])
                ->concat(IncidentResponseUnit::query()->where('incident_id', $lockedIncident->incident_id)->whereIn('status', self::ACTIVE_UNIT_STATUSES)->pluck('team_id'))
                ->filter()->map(fn ($id) => (int) $id)->unique()->values();
            $stations = $this->candidateStations($lockedIncident, (int) $requester->station_id);

            foreach ($stations as $station) {
                $stationId = (int) $station->station_id;
                $team = $this->findAvailableTeamForStation($stationId, $excludedTeamIds);
                if (! $team) continue;
                $source = $stationId === (int) $requester->station_id ? 'same_station' : 'other_station';
                $unit = $this->reserveUnit($lockedIncident, $team, $stationId, $source, $userId, $station->distance_meters ?? null);
                if ($unit) return $unit;
            }

            throw ValidationException::withMessages([
                'backup' => 'No backup unit currently has both an available firetruck and at least two available firefighters.',
            ]);
        });
    }

    public function findAvailableTeamForStation(int $stationId, Collection|array $excludedTeamIds = []): ?Team
    {
        app(DutyRosterService::class)->sync($stationId);
        $excluded = collect($excludedTeamIds)->filter()->map(fn ($id) => (int) $id)->all();
        $teams = Team::query()->where('station_id', $stationId)->where('status', 'available')
            ->when($excluded, fn ($query) => $query->whereNotIn('team_id', $excluded))
            ->whereDoesntHave('incidents', fn ($query) => $query->where('status', 'Reported'))
            ->lockForUpdate()->orderBy('team_id')->get();

        foreach ($teams as $team) {
            $memberIds = $this->teamMemberIds($team);
            if ($memberIds->count() < 2) continue;
            $availableMembers = StationOfficer::query()->whereIn('station_officer_id', $memberIds)
                ->where('station_id', $stationId)->where('status', 'available')->count();
            if ($availableMembers >= 2) return $team;
        }
        return null;
    }

    public function stationAvailableFiretruckCount(int $stationId): int
    {
        if (Apparatus::query()->where('station_id', $stationId)->exists()) {
            return Apparatus::query()->where('station_id', $stationId)->dispatchable()->count();
        }
        return (int) $this->firetruckAssignments($stationId)->sum('available_quantity');
    }

    public function stationHasFiretruckCapacityForPendingAssignment(int $stationId, ?int $ignoreIncidentId = null): bool
    {
        $available = $this->stationAvailableFiretruckCount($stationId);
        if ($available < 1) return false;

        $pendingFireAssignments = Incident::query()
            ->with(['marker', 'team'])
            ->where('status', 'Reported')
            ->whereNotNull('team_id')
            ->when($ignoreIncidentId, fn ($query) => $query->where('incident_id', '!=', $ignoreIncidentId))
            ->whereHas('team', fn ($query) => $query->where('station_id', $stationId))
            ->get()
            ->filter(fn (Incident $pending) => $this->incidentRequiresFiretruck($pending))
            ->count();

        return $available > $pendingFireAssignments;
    }

    public function teamCanAcceptIncident(Team $team, Incident $incident): bool
    {
        app(DutyRosterService::class)->sync((int) $team->station_id);
        $team->refresh();
        if (strtolower((string) $team->status) !== 'available') return false;

        $hasAnotherPendingIncident = $team->incidents()
            ->where('status', 'Reported')
            ->where('incident_id', '!=', $incident->incident_id)
            ->exists();
        if ($hasAnotherPendingIncident) return false;

        $memberIds = $this->teamMemberIds($team);
        if ($memberIds->count() < 2) return false;
        $availableMembers = StationOfficer::query()
            ->where('station_id', $team->station_id)
            ->whereIn('station_officer_id', $memberIds)
            ->where('status', 'available')
            ->count();
        if ($availableMembers < 2) return false;

        return ! $this->incidentRequiresFiretruck($incident)
            || $this->stationHasFiretruckCapacityForPendingAssignment(
                (int) $team->station_id,
                (int) $incident->incident_id
            );
    }

    public function dispatchableTeamsForIncident(Incident $incident, int $stationId): Collection
    {
        return Team::query()->where('station_id', $stationId)->orderBy('team_id')->get()
            ->filter(fn (Team $team) => $this->teamCanAcceptIncident($team, $incident))
            ->values();
    }

    public function stationsByIncidentDistance(Incident $incident): Collection
    {
        $incident->loadMissing('marker');
        if ($incident->marker?->lat === null || $incident->marker?->lng === null) return collect();

        return Station::query()->with('marker')->get()
            ->filter(fn (Station $station) => $station->marker?->lat !== null && $station->marker?->lng !== null)
            ->map(function (Station $station) use ($incident) {
                $station->distance_meters = $this->distanceMeters(
                    (float) $incident->marker->lat,
                    (float) $incident->marker->lng,
                    (float) $station->marker->lat,
                    (float) $station->marker->lng
                );
                return $station;
            })
            ->sortBy('distance_meters')
            ->values();
    }

    public function requestExternalResponse(Incident $incident, int $requestedBy): array
    {
        return DB::transaction(function () use ($incident, $requestedBy) {
            $locked = Incident::query()->with('marker')->whereKey($incident->incident_id)->lockForUpdate()->firstOrFail();
            if (! $this->incidentRequiresFiretruck($locked)) {
                throw ValidationException::withMessages(['incident' => 'Cross-station firetruck response is available only for fire incidents.']);
            }

            $status = strtolower(trim((string) $locked->status));
            if (! in_array($status, ['reported', 'responding', 'on scene'], true)) {
                throw ValidationException::withMessages(['incident' => 'This incident is no longer eligible for another station response.']);
            }

            $originStationId = (int) ($locked->station_id ?? 0);
            if ($status === 'reported' && $originStationId > 0
                && $this->dispatchableTeamsForIncident($locked, $originStationId)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'backup' => 'An available team still exists at the incident station. Reassign locally before calling another station.',
                ]);
            }
            $activeStationIds = IncidentResponseUnit::query()
                ->where('incident_id', $locked->incident_id)
                ->whereIn('status', self::ACTIVE_UNIT_STATUSES)
                ->pluck('station_id')->map(fn ($id) => (int) $id);
            $excludedTeamIds = collect([$locked->team_id])
                ->concat(IncidentResponseUnit::query()->where('incident_id', $locked->incident_id)
                    ->whereIn('status', self::ACTIVE_UNIT_STATUSES)->pluck('team_id'))
                ->filter()->map(fn ($id) => (int) $id)->unique()->values();

            foreach ($this->stationsByIncidentDistance($locked) as $station) {
                $stationId = (int) $station->station_id;
                if ($stationId === $originStationId || $activeStationIds->contains($stationId)) continue;

                $team = $this->findAvailableTeamForStation($stationId, $excludedTeamIds);
                if (! $team || ! $this->stationHasFiretruckCapacityForPendingAssignment($stationId, (int) $locked->incident_id)) continue;

                if ($status === 'reported') {
                    $previousTeamId = $locked->team_id ? (int) $locked->team_id : null;
                    $locked->team_id = $team->team_id;
                    if (Schema::hasColumn('incidents', 'dispatch_assigned_at')) $locked->dispatch_assigned_at = now();
                    $locked->save();
                    return [
                        'mode' => 'external_assignment',
                        'incident' => $locked->fresh(['team', 'station']),
                        'team' => $team,
                        'station' => $station,
                        'unit' => null,
                        'previous_team_id' => $previousTeamId,
                    ];
                }

                $unit = $this->reserveUnit($locked, $team, $stationId, 'other_station', $requestedBy, (float) $station->distance_meters);
                if ($unit) {
                    return [
                        'mode' => 'backup_unit',
                        'incident' => $locked,
                        'team' => $team,
                        'station' => $station,
                        'unit' => $unit,
                        'previous_team_id' => $locked->team_id ? (int) $locked->team_id : null,
                    ];
                }
            }

            throw ValidationException::withMessages([
                'backup' => 'No other station currently has both an available firetruck and a deployable team.',
            ]);
        });
    }

    public function incidentRequiresFiretruck(Incident $incident): bool
    {
        $category = strtolower(trim((string) $incident->marker?->category));
        $title = strtolower(trim((string) $incident->marker?->title));
        return str_contains($category, 'fire') || str_contains($title, 'fire');
    }

    public function dispatchTeam(Team $team): void
    {
        $this->markTeamResponding($team);
    }

    public function markIncidentUnitsOnScene(Incident $incident): void
    {
        IncidentResponseUnit::query()->where('incident_id', $incident->incident_id)->whereIn('status', ['reserved', 'en_route'])
            ->update(['status' => 'on_scene', 'arrived_at' => now(), 'updated_at' => now()]);

        $teamIds = collect([$incident->team_id])
            ->concat(IncidentResponseUnit::query()->where('incident_id', $incident->incident_id)->where('status', 'on_scene')->pluck('team_id'))
            ->filter()->unique();
        foreach ($teamIds as $teamId) {
            $team = Team::find($teamId);
            if (! $team) continue;
            $team->forceFill(['status' => 'responding'])->save();
            StationOfficer::query()->whereIn('station_officer_id', $this->teamMemberIds($team))
                ->where('status', 'dispatched')->update(['status' => 'on-scene', 'updated_at' => now()]);
        }
    }

    public function releaseIncidentUnits(Incident $incident): int
    {
        $teamIds = collect([$incident->team_id])->filter();
        $released = DB::transaction(function () use ($incident, &$teamIds) {
            $units = IncidentResponseUnit::query()->where('incident_id', $incident->incident_id)
                ->whereIn('status', self::ACTIVE_UNIT_STATUSES)->lockForUpdate()->get();
            foreach ($units as $unit) {
                $this->releaseReservedVehicle($unit);
                $isUnarrivedBackup = $unit->dispatch_source !== 'primary' && in_array($unit->status, ['reserved', 'en_route'], true);
                $unit->update($isUnarrivedBackup
                    ? ['status' => 'recalled', 'recalled_at' => now(), 'released_at' => now(), 'recall_reason' => 'Incident was controlled before backup arrived.']
                    : ['status' => 'released', 'released_at' => now()]);
                if ($unit->team_id) $teamIds->push((int) $unit->team_id);
            }
            return $units->count();
        });

        foreach ($teamIds->unique() as $teamId) {
            $team = Team::find($teamId);
            if (! $team) continue;
            $hasAnotherActiveUnit = IncidentResponseUnit::query()
                ->where('team_id', $teamId)
                ->where('incident_id', '!=', $incident->incident_id)
                ->whereIn('status', self::ACTIVE_UNIT_STATUSES)
                ->exists();
            $hasAnotherActiveIncident = Incident::query()
                ->where('team_id', $teamId)
                ->where('incident_id', '!=', $incident->incident_id)
                ->whereIn('status', ['Reported', 'Responding', 'On Scene'])
                ->exists();
            if ($hasAnotherActiveUnit || $hasAnotherActiveIncident) continue;
            $team->forceFill(['status' => 'available'])->save();
            $members = StationOfficer::query()->whereIn('station_officer_id', $this->teamMemberIds($team))->get(['station_officer_id', 'user_id']);
            StationOfficer::query()->whereIn('station_officer_id', $members->pluck('station_officer_id'))->whereIn('status', ['dispatched', 'on-scene'])->update(['status' => 'available']);
            foreach ($members->pluck('user_id')->filter() as $userId) {
                Cache::forget("firefighter:assigned:{$userId}");
                Cache::forget("firefighter:dashboard:widgets:{$userId}");
            }
            if ($team->roster_managed) app(DutyRosterService::class)->syncTeam($team->fresh());
        }
        return $released;
    }

    public function serializeUnit(IncidentResponseUnit $unit): array
    {
        $unit->loadMissing([
            'station.marker', 'equipmentAssignment.equipment', 'apparatus.loadout.stationAssignment.equipment',
            'team.commander.user', 'team.pumpers.user', 'team.teamLeader.user',
            'team.officer1.user', 'team.officer2.user', 'team.officer3.user', 'team.rescueRadio.user',
        ]);
        $location = $this->unitLocation($unit);
        $etaMinutes = $unit->estimated_arrival_at && ! in_array($unit->status, ['on_scene', 'released', 'recalled'], true)
            ? max(0, now()->diffInMinutes($unit->estimated_arrival_at, false))
            : null;
        return [
            'id' => $unit->incident_response_unit_id,
            'run_number' => $unit->run_number,
            'source' => $unit->dispatch_source,
            'status' => $unit->status,
            'station_id' => $unit->station_id,
            'station_name' => $unit->station?->station_name,
            'team_id' => $unit->team_id,
            'team_label' => $unit->team ? 'Team #'.$unit->team->team_id : null,
            'team_members' => $this->serializeTeamMembers($unit->team),
            'equipment' => $unit->apparatus?->unit_number ?: $unit->equipmentAssignment?->equipment?->equipment_name,
            'apparatus_id' => $unit->apparatus_id,
            'unit_number' => $unit->apparatus?->unit_number,
            'plate_number' => $unit->apparatus?->plate_number,
            'apparatus_type' => $unit->apparatus?->apparatus_type,
            'water_capacity_liters' => $unit->apparatus?->water_capacity_liters,
            'gps_status' => $this->resolvedGpsStatus($unit->apparatus),
            'gps_last_seen_at' => $unit->apparatus?->gps_last_seen_at?->toIso8601String(),
            'gps_accuracy_meters' => $unit->apparatus?->gps_accuracy_meters,
            'gps_speed_kph' => $unit->apparatus?->gps_speed_kph,
            'gps_heading' => $unit->apparatus?->gps_heading,
            'loadout' => $unit->apparatus?->loadout?->map(fn ($item) => [
                'equipment_station_id' => $item->equipment_station_id,
                'equipment_id' => $item->stationAssignment?->equipment_id,
                'name' => $item->stationAssignment?->equipment?->equipment_name,
                'category' => $item->stationAssignment?->equipment?->category,
                'quantity' => $item->quantity,
                'notes' => $item->notes,
            ])->values()->all() ?? [],
            'lat' => $location['lat'],
            'lng' => $location['lng'],
            'distance_meters' => $unit->distance_meters,
            'eta_minutes' => $etaMinutes,
            'estimated_arrival_at' => $unit->estimated_arrival_at?->toIso8601String(),
            'dispatched_at' => $unit->dispatched_at?->toIso8601String(),
            'arrived_at' => $unit->arrived_at?->toIso8601String(),
            'released_at' => $unit->released_at?->toIso8601String(),
            'recalled_at' => $unit->recalled_at?->toIso8601String(),
            'recall_reason' => $unit->recall_reason,
            'can_recall' => $unit->dispatch_source !== 'primary' && in_array($unit->status, ['reserved', 'en_route'], true),
        ];
    }

    public function updateApparatusTelemetry(IncidentResponseUnit $unit, array $telemetry): IncidentResponseUnit
    {
        return DB::transaction(function () use ($unit, $telemetry) {
            $lockedUnit = IncidentResponseUnit::query()->with('incident.marker')->whereKey($unit->incident_response_unit_id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedUnit->apparatus_id, 409, 'This response has no registered apparatus to track.');
            abort_unless(in_array($lockedUnit->status, self::ACTIVE_UNIT_STATUSES, true), 409, 'This response unit is no longer active.');

            $apparatus = Apparatus::query()->whereKey($lockedUnit->apparatus_id)->lockForUpdate()->firstOrFail();
            $apparatus->update([
                'last_lat' => $telemetry['latitude'],
                'last_lng' => $telemetry['longitude'],
                'gps_accuracy_meters' => $telemetry['accuracy'] ?? null,
                'gps_speed_kph' => $telemetry['speed_kph'] ?? null,
                'gps_heading' => $telemetry['heading'] ?? null,
                'gps_status' => 'online',
                'gps_last_seen_at' => now(),
            ]);

            $marker = $lockedUnit->incident?->marker;
            if ($marker?->lat !== null && $marker?->lng !== null) {
                $remaining = $this->distanceMeters(
                    (float) $telemetry['latitude'], (float) $telemetry['longitude'],
                    (float) $marker->lat, (float) $marker->lng
                );
                $reportedSpeed = (float) ($telemetry['speed_kph'] ?? 0);
                $effectiveSpeed = $reportedSpeed >= 5 ? min(90, $reportedSpeed) : 35;
                $etaMinutes = max(1, (int) ceil($remaining / ($effectiveSpeed * 1000 / 60)));
                $lockedUnit->update([
                    'distance_meters' => $remaining,
                    'estimated_arrival_at' => now()->addMinutes($etaMinutes),
                ]);
            }

            return $lockedUnit->fresh();
        });
    }

    public function recallBackup(Incident $incident, IncidentResponseUnit $unit, int $userId, string $reason): IncidentResponseUnit
    {
        return DB::transaction(function () use ($incident, $unit, $userId, $reason) {
            $locked = IncidentResponseUnit::query()->whereKey($unit->incident_response_unit_id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $locked->incident_id === (int) $incident->incident_id, 404);
            if ($locked->dispatch_source === 'primary') {
                throw ValidationException::withMessages(['unit' => 'The primary response unit cannot be recalled as backup.']);
            }
            if (! in_array($locked->status, ['reserved', 'en_route'], true)) {
                throw ValidationException::withMessages(['unit' => 'Only backup units that have not arrived can be recalled.']);
            }
            $requester = StationOfficer::where('user_id', $userId)->first();
            if (! $requester || ! $this->officerIsRespondingToIncident($requester, $incident)) {
                throw new AuthorizationException('Only an assigned responding firefighter can recall this backup.');
            }

            $this->releaseReservedVehicle($locked);
            $locked->update(['status' => 'recalled', 'recalled_at' => now(), 'released_at' => now(), 'recalled_by' => $userId, 'recall_reason' => $reason]);
            $this->releaseTeam($locked->team_id);
            return $locked->fresh();
        });
    }

    private function reserveUnit(Incident $incident, Team $team, int $stationId, string $source, ?int $requestedBy, ?float $distanceMeters): ?IncidentResponseUnit
    {
        $apparatus = null;
        $truck = null;
        if (Apparatus::query()->where('station_id', $stationId)->exists()) {
            $apparatus = Apparatus::query()->where('station_id', $stationId)->dispatchable()->lockForUpdate()->orderBy('unit_number')->first();
            if (! $apparatus) return null;
            $truck = EquipmentToStation::firstOrCreate(
                ['station_id' => $stationId, 'equipment_id' => $apparatus->equipment_id],
                ['available_quantity' => 0, 'damaged_quantity' => 0, 'maintenance_quantity' => 0]
            );
            $apparatus->update(['status' => 'dispatched']);
        } else {
            $truck = $this->firetruckAssignments($stationId)->where('available_quantity', '>', 0)->lockForUpdate()->first();
            if (! $truck) return null;
            $truck->decrement('available_quantity');
        }
        $distanceMeters = $this->resolveDispatchDistance($incident, $stationId, $apparatus, $distanceMeters);
        $etaMinutes = max(2, (int) ceil(($distanceMeters ?: 0) / (35000 / 60)));
        $runNumber = (int) IncidentResponseUnit::query()->where('incident_id', $incident->incident_id)->max('run_number') + 1;
        $unit = IncidentResponseUnit::create([
            'incident_id' => $incident->incident_id, 'station_id' => $stationId, 'team_id' => $team->team_id,
            'equipment_station_id' => $truck->equipment_station_id, 'apparatus_id' => $apparatus?->apparatus_id, 'run_number' => $runNumber,
            'dispatch_source' => $source, 'status' => 'en_route', 'requested_by' => $requestedBy,
            'distance_meters' => $distanceMeters, 'estimated_arrival_at' => now()->addMinutes($etaMinutes), 'dispatched_at' => now(),
        ]);
        $this->markTeamResponding($team);
        return $unit;
    }

    private function firetruckAssignments(int $stationId)
    {
        return EquipmentToStation::query()->with('equipment')->where('station_id', $stationId)
            ->whereHas('equipment', function ($query) {
                $query->whereRaw("LOWER(COALESCE(category, '')) = ?", ['vehicles'])
                    ->where(function ($nameQuery) {
                        foreach (['fire truck', 'firetruck', 'fire engine', 'pumper truck', 'fire apparatus'] as $term) {
                            $nameQuery->orWhereRaw('LOWER(equipment_name) LIKE ?', ['%'.$term.'%']);
                        }
                    });
            });
    }

    private function markTeamResponding(Team $team): void
    {
        $team->forceFill(['status' => 'responding'])->save();
        $members = StationOfficer::query()->whereIn('station_officer_id', $this->teamMemberIds($team))->get(['station_officer_id', 'user_id']);
        StationOfficer::query()->whereIn('station_officer_id', $members->pluck('station_officer_id'))
            ->where('status', 'available')->update(['status' => 'dispatched', 'updated_at' => now()]);
        foreach ($members->pluck('user_id')->filter() as $userId) {
            Cache::forget("firefighter:assigned:{$userId}");
            Cache::forget("firefighter:dashboard:widgets:{$userId}");
        }
    }

    private function officerIsRespondingToIncident(StationOfficer $officer, Incident $incident): bool
    {
        $teamIds = collect([$incident->team_id])->concat(
            IncidentResponseUnit::query()->where('incident_id', $incident->incident_id)->whereIn('status', self::ACTIVE_UNIT_STATUSES)->pluck('team_id')
        )->filter()->unique();
        return Team::query()->whereIn('team_id', $teamIds)->where(function ($query) use ($officer) {
            foreach (DutyRosterService::DEPLOYABLE_ROLES as $column) $query->orWhere($column, $officer->station_officer_id);
        })->exists();
    }

    private function candidateStations(Incident $incident, int $requesterStationId): Collection
    {
        $sameStation = Station::find($requesterStationId);
        $others = Station::query()->with('marker')->where('station_id', '!=', $requesterStationId)->get()
            ->filter(fn ($station) => $station->marker?->lat !== null && $station->marker?->lng !== null && $incident->marker?->lat !== null && $incident->marker?->lng !== null)
            ->map(function ($station) use ($incident) {
                $station->distance_meters = $this->distanceMeters((float) $incident->marker->lat, (float) $incident->marker->lng, (float) $station->marker->lat, (float) $station->marker->lng);
                return $station;
            })->sortBy('distance_meters')->values();
        if ($sameStation) { $sameStation->distance_meters = 0; $others->prepend($sameStation); }
        return $others;
    }

    private function teamMemberIds(Team $team): Collection
    {
        return collect(DutyRosterService::DEPLOYABLE_ROLES)->map(fn ($role) => $team->{$role})->filter()->map(fn ($id) => (int) $id)->unique()->values();
    }

    private function serializeTeamMembers(?Team $team): array
    {
        if (! $team) return [];
        $roles = [
            'teamLeader' => 'Team Leader',
            'commander' => 'Commander / Driver',
            'pumpers' => 'Pumper',
            'officer1' => 'Firefighter Officer 1',
            'officer2' => 'Firefighter Officer 2',
            'officer3' => 'Firefighter Officer 3',
            'rescueRadio' => 'Rescue / Radio',
        ];

        return collect($roles)->map(function (string $role, string $relation) use ($team) {
            $officer = $team->{$relation};
            if (! $officer) return null;
            $user = $officer->user;
            $name = trim(implode(' ', array_filter([$user?->first_name, $user?->middle_name, $user?->last_name])));
            return [
                'station_officer_id' => $officer->station_officer_id,
                'user_id' => $officer->user_id,
                'name' => $name !== '' ? $name : 'Assigned firefighter',
                'role' => $role,
                'rank' => $officer->rank,
                'status' => $officer->status,
            ];
        })->filter()->values()->all();
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $radius = 6371000; $dLat = deg2rad($lat2 - $lat1); $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $radius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function resolveDispatchDistance(Incident $incident, int $stationId, ?Apparatus $apparatus, ?float $distanceMeters): ?float
    {
        $targetLat = $incident->marker?->lat;
        $targetLng = $incident->marker?->lng;
        if ($targetLat === null || $targetLng === null) return $distanceMeters;
        if ($apparatus?->last_lat !== null && $apparatus?->last_lng !== null && in_array($apparatus->gps_status, ['online', 'stale'], true)) {
            return $this->distanceMeters((float) $targetLat, (float) $targetLng, (float) $apparatus->last_lat, (float) $apparatus->last_lng);
        }
        $station = Station::with('marker')->find($stationId);
        if ($station?->marker?->lat !== null && $station?->marker?->lng !== null) {
            return $this->distanceMeters((float) $targetLat, (float) $targetLng, (float) $station->marker->lat, (float) $station->marker->lng);
        }
        return $distanceMeters;
    }

    private function unitLocation(IncidentResponseUnit $unit): array
    {
        if ($unit->apparatus?->last_lat !== null && $unit->apparatus?->last_lng !== null) {
            return ['lat' => (float) $unit->apparatus->last_lat, 'lng' => (float) $unit->apparatus->last_lng];
        }
        return ['lat' => $unit->station?->marker?->lat !== null ? (float) $unit->station->marker->lat : null, 'lng' => $unit->station?->marker?->lng !== null ? (float) $unit->station->marker->lng : null];
    }

    private function resolvedGpsStatus(?Apparatus $apparatus): string
    {
        if (! $apparatus) return 'station_fallback';
        if (! $apparatus->gps_last_seen_at || in_array($apparatus->gps_status, ['not_installed', 'offline'], true)) {
            return $apparatus->gps_status ?: 'offline';
        }
        $ageSeconds = $apparatus->gps_last_seen_at->diffInSeconds(now());
        if ($ageSeconds > 600) return 'offline';
        if ($ageSeconds > 120) return 'stale';
        return 'online';
    }

    private function releaseReservedVehicle(IncidentResponseUnit $unit): void
    {
        if ($unit->apparatus_id) {
            Apparatus::query()->whereKey($unit->apparatus_id)->where('status', 'dispatched')->update(['status' => 'available', 'updated_at' => now()]);
            return;
        }
        $assignment = EquipmentToStation::query()->whereKey($unit->equipment_station_id)->lockForUpdate()->first();
        if ($assignment) $assignment->increment('available_quantity');
    }

    private function releaseTeam(?int $teamId): void
    {
        if (! $teamId) return;
        $stillActive = IncidentResponseUnit::query()->where('team_id', $teamId)->whereIn('status', self::ACTIVE_UNIT_STATUSES)->exists();
        if ($stillActive) return;
        $team = Team::find($teamId);
        if (! $team) return;
        $team->forceFill(['status' => 'available'])->save();
        StationOfficer::query()->whereIn('station_officer_id', $this->teamMemberIds($team))->whereIn('status', ['dispatched', 'on-scene'])->update(['status' => 'available']);
    }
}
