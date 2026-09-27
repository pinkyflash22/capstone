<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentDispatchAudit;
use App\Models\Station;
use App\Models\StationOfficer;
use App\Models\Team;
use App\Notifications\IncidentAssignmentNotification;
use App\Notifications\IncidentDispatchUnavailableNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class IncidentAutoAssignmentService
{
    public const DEFAULT_RESPONSE_TIMEOUT_MINUTES = 2;
    public const DEFAULT_RECENT_ASSIGNMENT_MINUTES = 180;

    public function __construct(private readonly IncidentJurisdictionService $jurisdictionService)
    {
    }

    public function assignRecentUnassignedIncidents(int $minutes = self::DEFAULT_RECENT_ASSIGNMENT_MINUTES): array
    {
        $expired = $this->expireUnansweredAssignments();
        $cutoff = now()->subMinutes($minutes);

        $incidents = Incident::with('marker')
            ->whereNull('team_id')
            ->where('status', 'Reported')
            ->where('created_at', '>=', $cutoff)
            ->when(Schema::hasColumn('incidents', 'duplicate_of_incident_id'), fn ($query) => $query->whereNull('duplicate_of_incident_id'))
            ->when(
                Schema::hasColumn('incidents', 'dispatch_priority'),
                fn ($query) => $query->orderByRaw("CASE LOWER(COALESCE(dispatch_priority, 'medium')) WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            )
            ->orderBy('created_at', 'asc')
            ->get();

        $assignedIncidents = [];

        foreach ($incidents as $incident) {
            $assignedIncident = $this->assignSingleIncident($incident, [], null, 'auto_team_assigned', 'Assigned by automatic dispatch matching.', $minutes);
            if ($assignedIncident !== null) {
                $assignedIncidents[] = $assignedIncident;
            }
        }

        return [
            'checked' => $incidents->count(),
            'assigned' => $assignedIncidents,
            'expired' => $expired,
        ];
    }

    public function assignSingleIncidentById(
        int $incidentId,
        array $excludedTeamIds = [],
        ?int $previousTeamId = null,
        string $auditAction = 'auto_team_assigned',
        string $reason = 'Assigned by automatic dispatch matching.',
        ?int $auditWindowMinutes = null
    ): ?array
    {
        $incident = Incident::with('marker')->find($incidentId);

        if (! $incident) {
            return null;
        }

        return $this->assignSingleIncident($incident, $excludedTeamIds, $previousTeamId, $auditAction, $reason, $auditWindowMinutes);
    }

    public function assignSingleIncident(
        Incident $incident,
        array $excludedTeamIds = [],
        ?int $previousTeamId = null,
        string $auditAction = 'auto_team_assigned',
        string $reason = 'Assigned by automatic dispatch matching.',
        ?int $auditWindowMinutes = null
    ): ?array
    {
        return DB::transaction(function () use ($incident, $excludedTeamIds, $previousTeamId, $auditAction, $reason, $auditWindowMinutes) {
            $lockedIncident = Incident::with('marker')
                ->whereKey($incident->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedIncident) {
                return null;
            }

            $lockedIncident = $this->jurisdictionService->apply($lockedIncident);

            if (Schema::hasColumn('incidents', 'duplicate_of_incident_id') && $lockedIncident->duplicate_of_incident_id) {
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
                $resources = app(IncidentResourceDispatchService::class);
                $team = $resources->findAvailableTeamForStation($stationId, $excludedTeamIds);

                if (! $team) {
                    continue;
                }

                if ($resources->incidentRequiresFiretruck($lockedIncident)
                    && ! $resources->stationHasFiretruckCapacityForPendingAssignment($stationId)) {
                    continue;
                }

                $lockedIncident->team_id = $team->team_id;
                if (Schema::hasColumn('incidents', 'dispatch_assigned_at')) {
                    $lockedIncident->dispatch_assigned_at = now();
                }
                $lockedIncident->updated_at = now();
                $lockedIncident->save();

                $this->recordDispatchAudit($lockedIncident, $auditAction, $previousTeamId, (int) $team->team_id, $reason, [
                    'excluded_team_ids' => array_values(array_map('intval', $excludedTeamIds)),
                    'window_minutes' => $auditWindowMinutes ?? self::DEFAULT_RECENT_ASSIGNMENT_MINUTES,
                ]);

                $incidentId = (int) $lockedIncident->incident_id;
                $teamId = (int) $team->team_id;
                DB::afterCommit(fn () => $this->notifyIncidentAssignment($incidentId, $stationId, $teamId));

                return [
                    'incident_id' => $lockedIncident->incident_id,
                    'team_id' => $team->team_id,
                    'team_name' => $team->team_name ?? ('Team #'.$team->team_id),
                    'station_id' => $stationId,
                    'status' => 'awaiting-response',
                    'team_status_updated' => false,
                    'response_unit_id' => null,
                    'created_at' => optional($lockedIncident->created_at)->toISOString(),
                ];
            }

            if ($lockedIncident->barangay_id && $lockedIncident->station_id) {
                $incidentId = (int) $lockedIncident->incident_id;
                $stationId = (int) $lockedIncident->station_id;
                $barangayId = (int) $lockedIncident->barangay_id;
                $barangayName = $lockedIncident->barangay()->value('barangay_name');

                DB::afterCommit(fn () => $this->notifyStationCommandOfUnavailableResources(
                    $incidentId,
                    $stationId,
                    $barangayId,
                    $barangayName
                ));
            }

            return null;
        });
    }

    public function reassignReportedIncidentsFromOccupiedTeam(int $teamId, int $respondingIncidentId): array
    {
        $incidentIds = Incident::query()
            ->where('team_id', $teamId)
            ->where('status', 'Reported')
            ->where('incident_id', '!=', $respondingIncidentId)
            ->orderBy('created_at')
            ->pluck('incident_id');

        $reassigned = [];
        $queued = [];

        foreach ($incidentIds as $incidentId) {
            $released = DB::transaction(function () use ($incidentId, $teamId) {
                $pending = Incident::query()->whereKey($incidentId)->lockForUpdate()->first();
                if (! $pending || (int) $pending->team_id !== $teamId || $pending->status !== 'Reported') {
                    return false;
                }

                $pending->team_id = null;
                if (Schema::hasColumn('incidents', 'dispatch_assigned_at')) {
                    $pending->dispatch_assigned_at = null;
                }
                $pending->save();
                return true;
            });

            if (! $released) {
                continue;
            }

            $assignment = $this->assignSingleIncidentById(
                (int) $incidentId,
                [$teamId],
                $teamId,
                'auto_team_reassigned',
                'Previous team started another response; incident was rematched automatically.'
            );
            if ($assignment) {
                $reassigned[] = $assignment;
            } else {
                $queued[] = (int) $incidentId;
            }
        }

        return ['reassigned' => $reassigned, 'queued' => $queued];
    }

    public function expireUnansweredAssignments(?int $timeoutMinutes = null): array
    {
        if (! Schema::hasColumn('incidents', 'dispatch_assigned_at')) {
            return ['expired' => 0, 'reassigned' => [], 'renewed' => [], 'queued' => []];
        }

        $timeout = max(1, $timeoutMinutes ?? self::DEFAULT_RESPONSE_TIMEOUT_MINUTES);
        $cutoff = now()->subMinutes($timeout);
        $incidentIds = Incident::query()
            ->where('status', 'Reported')
            ->whereNotNull('team_id')
            ->where(function ($query) use ($cutoff) {
                $query->where('dispatch_assigned_at', '<=', $cutoff)
                    ->orWhere(function ($legacy) use ($cutoff) {
                        $legacy->whereNull('dispatch_assigned_at')->where('updated_at', '<=', $cutoff);
                    });
            })
            ->orderBy('created_at')
            ->pluck('incident_id');

        $reassigned = [];
        $renewed = [];
        $queued = [];

        foreach ($incidentIds as $incidentId) {
            $previousTeamId = DB::transaction(function () use ($incidentId, $cutoff) {
                $pending = Incident::query()->whereKey($incidentId)->lockForUpdate()->first();
                if (! $pending || $pending->status !== 'Reported' || ! $pending->team_id) {
                    return null;
                }

                $assignedAt = $pending->dispatch_assigned_at ?? $pending->updated_at;
                if ($assignedAt && $assignedAt->isAfter($cutoff)) {
                    return null;
                }

                $teamId = (int) $pending->team_id;
                $pending->team_id = null;
                $pending->dispatch_assigned_at = null;
                $pending->save();
                return $teamId;
            });

            if (! $previousTeamId) {
                continue;
            }

            $alternative = $this->assignSingleIncidentById(
                (int) $incidentId,
                [$previousTeamId],
                $previousTeamId,
                'auto_team_reassigned',
                'Previous team did not accept before timeout; incident was rematched automatically.'
            );
            if ($alternative) {
                $reassigned[] = $alternative;
                continue;
            }

            $fallback = $this->assignSingleIncidentById(
                (int) $incidentId,
                [],
                $previousTeamId,
                'auto_team_renewed',
                'No alternate team was available; assignment was renewed automatically.'
            );
            if ($fallback) {
                $renewed[] = $fallback;
            } else {
                $queued[] = (int) $incidentId;
            }
        }

        return [
            'expired' => $incidentIds->count(),
            'reassigned' => $reassigned,
            'renewed' => $renewed,
            'queued' => $queued,
        ];
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

    private function getCandidateStationsForIncident(Incident $incident, float $lat, float $lng)
    {
        $stationLimit = strtolower((string) ($incident->marker?->category ?? '')) === 'fire' ? 3 : 1;
        $nearbyStations = $this->getStationsSortedByDistance($lat, $lng);
        $primaryStationId = (int) ($incident->station_id ?? 0);
        $orderedStations = collect();

        if ($primaryStationId > 0) {
            $primaryStation = Station::with('marker')->find($primaryStationId);
            if ($primaryStation) {
                if ($primaryStation->marker && $primaryStation->marker->lat !== null && $primaryStation->marker->lng !== null) {
                    $primaryStation->distance_meters = $this->calculateDistanceMeters(
                        $lat,
                        $lng,
                        (float) $primaryStation->marker->lat,
                        (float) $primaryStation->marker->lng
                    );
                } else {
                    $primaryStation->distance_meters = 0;
                }

                $orderedStations->push($primaryStation);
            }
        }

        return $orderedStations
            ->concat($nearbyStations->reject(fn ($station) => (int) $station->station_id === $primaryStationId))
            ->unique(fn ($station) => (int) $station->station_id)
            ->take($stationLimit)
            ->values();
    }

    private function notifyStationCommandOfUnavailableResources(
        int $incidentId,
        int $stationId,
        int $barangayId,
        ?string $barangayName
    ): void {
        try {
            $recipients = StationOfficer::query()
                ->with('user.role')
                ->where('station_id', $stationId)
                ->get()
                ->pluck('user')
                ->filter(function ($user) {
                    $role = strtolower(trim((string) $user?->role?->role));

                    return in_array($role, ['chief', 'stationadmin'], true);
                })
                ->unique('user_id')
                ->values();

            foreach ($recipients as $recipient) {
                $alreadyNotified = $recipient->notifications()
                    ->where('type', IncidentDispatchUnavailableNotification::class)
                    ->where('created_at', '>=', now()->subMinutes(15))
                    ->latest('created_at')
                    ->limit(50)
                    ->get()
                    ->contains(fn ($notification) =>
                        (int) data_get($notification->data, 'meta.incident_id') === $incidentId
                    );

                if (! $alreadyNotified) {
                    $recipient->notify(new IncidentDispatchUnavailableNotification(
                        $incidentId,
                        $stationId,
                        $barangayId,
                        $barangayName
                    ));
                }
            }
        } catch (Throwable $exception) {
            Log::error('Unable to notify station command about unavailable incident resources.', [
                'incident_id' => $incidentId,
                'station_id' => $stationId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function recordDispatchAudit(
        Incident $incident,
        string $action,
        ?int $previousTeamId,
        int $assignedTeamId,
        string $reason,
        array $metadata = []
    ): void {
        if (! Schema::hasTable('incident_dispatch_audits')) {
            return;
        }

        if ($previousTeamId === $assignedTeamId) {
            return;
        }

        try {
            $assignedTeam = Team::query()->find($assignedTeamId);

            IncidentDispatchAudit::create([
                'incident_id' => $incident->incident_id,
                'actor_user_id' => null,
                'action' => $action,
                'previous_team_id' => $previousTeamId,
                'assigned_team_id' => $assignedTeamId,
                'source_station_id' => $incident->station_id,
                'target_station_id' => $assignedTeam?->station_id,
                'reason' => $reason,
                'metadata' => array_merge([
                    'source' => 'incident_auto_assignment_service',
                    'incident_status' => $incident->status,
                    'dispatch_assigned_at' => optional($incident->dispatch_assigned_at)->toISOString(),
                ], $metadata),
            ]);
        } catch (Throwable $exception) {
            Log::error('Unable to write incident auto-assignment audit.', [
                'incident_id' => $incident->incident_id,
                'previous_team_id' => $previousTeamId,
                'assigned_team_id' => $assignedTeamId,
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function notifyIncidentAssignment(int $incidentId, int $stationId, int $teamId): void
    {
        try {
            $team = Team::query()->find($teamId);
            if (! $team) {
                return;
            }

            $memberIds = collect(DutyRosterService::DEPLOYABLE_ROLES)
                ->map(fn (string $role) => $team->{$role})
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $responders = StationOfficer::query()
                ->with('user')
                ->whereIn('station_officer_id', $memberIds)
                ->get()
                ->pluck('user')
                ->filter()
                ->unique('user_id');
            $responderUserIds = $responders->pluck('user_id')->map(fn ($id) => (int) $id);

            foreach ($responders as $responder) {
                $responder->notify(new IncidentAssignmentNotification($incidentId, $stationId, $teamId, true));
            }

            $stationCommand = StationOfficer::query()
                ->with('user.role')
                ->where('station_id', $stationId)
                ->get()
                ->pluck('user')
                ->filter(function ($user) {
                    $role = strtolower(trim((string) $user?->role?->role));

                    return in_array($role, ['chief', 'stationadmin'], true);
                })
                ->reject(fn ($user) => $responderUserIds->contains((int) $user->user_id))
                ->unique('user_id');

            foreach ($stationCommand as $recipient) {
                $recipient->notify(new IncidentAssignmentNotification($incidentId, $stationId, $teamId));
            }
        } catch (Throwable $exception) {
            Log::error('Unable to notify responders and station command of incident assignment.', [
                'incident_id' => $incidentId,
                'station_id' => $stationId,
                'team_id' => $teamId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

}
