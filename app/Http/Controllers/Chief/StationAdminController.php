<?php

namespace App\Http\Controllers\Chief;

use App\Http\Controllers\Controller;

use App\Models\Marker;
use App\Models\StationOfficer;
use App\Models\Incident;
use App\Models\Role;
use App\Models\User;
use App\Models\Station;
use App\Models\Team;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use App\Models\BarangayOfficer;
use App\Models\BarangayPredictionChecklist;
use App\Models\AiActionPlanChecklist;
use App\Models\AiActionPlanChecklistItem;
use App\Models\FullAddress;
use App\Models\FsicRequest;
use App\Models\FsicApproval;
use App\Models\MitigationAction;
use App\Models\MitigationActionAttachment;
use App\Models\RiskArea;
use App\Models\SpotInvestigationReport;
use App\Models\ProgressInvestigationReport;
use App\Models\FinalInvestigationReport;
use App\Models\EventActivity;


use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\MediaPath;
use App\Support\UserActivityLogger;
use App\Services\PredictedRiskAreaService;
use Carbon\Carbon;

class StationAdminController extends Controller
{
    public function __construct(
        protected PredictedRiskAreaService $predictedRiskAreaService
    ) {
    }

    protected function serializeScheduleEvent(EventActivity $event): array
    {
        return [
            'event_id' => $event->event_id,
            'event' => $event->event,
            'event_date' => optional($event->event_date)->toDateString(),
            'start_time' => $event->start_time,
            'end_time' => $event->end_time,
            'schedule' => $event->schedule,
            'activity_type' => $event->activity_type ?: 'operations',
            'status' => $event->status,
            'barangay' => $event->barangay,
            'notes' => $event->notes,
            'station_id' => $event->station_id,
            'user_id' => $event->user_id,
            'source_type' => 'official',
            'source_label' => 'Official Schedule',
            'can_edit' => true,
            'can_delete' => true,
        ];
    }

    protected function validateSchedulePayload(Request $request, array $validBarangayNames): array
    {
        return $request->validate([
            'event' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'activity_type' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'barangay' => ['nullable', 'string', 'max:255', Rule::in($validBarangayNames)],
            'notes' => ['nullable', 'string'],
        ]);
    }

    protected function assertValidScheduleWindow(array $validated): ?\Illuminate\Http\JsonResponse
    {
        $manilaNow = Carbon::now('Asia/Manila');
        $eventDate = Carbon::parse($validated['event_date'], 'Asia/Manila');

        if ($eventDate->isSameDay($manilaNow)) {
            $minimumStartHour = $manilaNow->minute > 0 ? $manilaNow->hour + 1 : $manilaNow->hour;
            $requestedStartHour = (int) Carbon::createFromFormat('H:i', $validated['start_time'], 'Asia/Manila')->format('H');

            if ($requestedStartHour < $minimumStartHour) {
                return response()->json([
                    'success' => false,
                    'message' => 'For today, only current or future Asia/Manila time slots can be scheduled.'
                ], 422);
            }
        }

        return null;
    }

    protected function buildScheduleLabel(string $startTime, string $endTime): string
    {
        return Carbon::createFromFormat('H:i', $startTime)->format('g:i A')
            . ' - '
            . Carbon::createFromFormat('H:i', $endTime)->format('g:i A');
    }

    protected function hasOfficialScheduleConflict(int $stationId, array $validated, ?int $ignoreEventId = null): bool
    {
        return EventActivity::query()
            ->where('station_id', $stationId)
            ->whereNull('user_id')
            ->whereDate('event_date', $validated['event_date'])
            ->when($ignoreEventId, function ($query) use ($ignoreEventId) {
                $query->where('event_id', '!=', $ignoreEventId);
            })
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time', '>', $validated['start_time'])
            ->exists();
    }

    protected function buildIncidentTrendData($incidents): array
    {
        $weekLabels = collect(range(6, 0))
            ->map(fn (int $daysAgo) => Carbon::now()->subDays($daysAgo))
            ->values();

        $monthLabels = collect(range(3, 0))
            ->map(function (int $weeksAgo) {
                $start = Carbon::now()->startOfWeek()->subWeeks($weeksAgo);

                return [
                    'label' => $start->format('M d'),
                    'start' => $start->copy()->startOfDay(),
                    'end' => $start->copy()->endOfWeek()->endOfDay(),
                ];
            })
            ->values();

        $yearLabels = collect(range(11, 0))
            ->map(fn (int $monthsAgo) => Carbon::now()->startOfMonth()->subMonths($monthsAgo))
            ->values();

        return [
            'week' => [
                'labels' => $weekLabels->map(fn (Carbon $date) => $date->format('D'))->all(),
                'values' => $weekLabels->map(function (Carbon $date) use ($incidents) {
                    return $incidents->filter(fn ($incident) => optional($incident->created_at)?->isSameDay($date))->count();
                })->all(),
            ],
            'month' => [
                'labels' => $monthLabels->pluck('label')->all(),
                'values' => $monthLabels->map(function (array $range) use ($incidents) {
                    return $incidents->filter(function ($incident) use ($range) {
                        $createdAt = $incident->created_at;

                        return $createdAt
                            && $createdAt->between($range['start'], $range['end']);
                    })->count();
                })->all(),
            ],
            'year' => [
                'labels' => $yearLabels->map(fn (Carbon $date) => $date->format('M'))->all(),
                'values' => $yearLabels->map(function (Carbon $date) use ($incidents) {
                    return $incidents->filter(function ($incident) use ($date) {
                        $createdAt = $incident->created_at;

                        return $createdAt
                            && $createdAt->year === $date->year
                            && $createdAt->month === $date->month;
                    })->count();
                })->all(),
            ],
        ];
    }

    protected function buildStationReadinessData()
    {
        $eligibleRoles = ['firefighter', 'chief', 'station admin'];

        return Station::query()
            ->with(['users.role'])
            ->orderBy('station_name')
            ->get()
            ->map(function (Station $station) use ($eligibleRoles) {
                $personnel = $station->users->filter(function ($user) use ($eligibleRoles) {
                    $role = strtolower(trim($user->role->role ?? ''));

                    return in_array($role, $eligibleRoles, true);
                });

                $firefighterCount = $personnel->filter(function ($user) {
                    return strtolower(trim($user->role->role ?? '')) === 'firefighter';
                })->count();

                return [
                    'station_id' => $station->station_id,
                    'station_name' => $station->station_name,
                    'firefighter_count' => $firefighterCount,
                    'total_personnel_count' => $personnel->count(),
                ];
            });
    }

    protected function getAuthenticatedStationOfficer(): ?StationOfficer
    {
        return StationOfficer::where('user_id', Auth::user()->user_id)->first();
    }

    protected function getStationOfficerIdsForStation(?int $stationId)
    {
        if (!$stationId) {
            return collect();
        }

        return StationOfficer::where('station_id', $stationId)
            ->pluck('station_officer_id')
            ->filter()
            ->values();
    }

    protected function getTeamsForStation(?int $stationId)
    {
        $stationOfficerIds = $this->getStationOfficerIdsForStation($stationId);

        if ($stationOfficerIds->isEmpty()) {
            return Team::query()->whereRaw('1 = 0');
        }

        return Team::query()->where(function ($query) use ($stationOfficerIds) {
            $officerIds = $stationOfficerIds->all();

            $query->whereIn('commander_driver', $officerIds)
                ->orWhereIn('pumper', $officerIds)
                ->orWhereIn('team_leader', $officerIds)
                ->orWhereIn('firefighter_officer_1', $officerIds)
                ->orWhereIn('firefighter_officer_2', $officerIds)
                ->orWhereIn('firefighter_officer_3', $officerIds)
                ->orWhereIn('rescue_radio', $officerIds);
        });
    }

    protected function getFirstTeamForStation(?int $stationId): ?Team
    {
        if (!$stationId) {
            return null;
        }

        return $this->getTeamsForStation($stationId)
            ->orderBy('team_id')
            ->first();
    }

    protected function getTeamIdsForStation(?int $stationId)
    {
        if (!$stationId) {
            return collect();
        }

        return $this->getTeamsForStation($stationId)
            ->pluck('team_id')
            ->filter()
            ->values();
    }

    protected function getManagedBarangaysForStationAdmin()
    {
        $stationId = $this->getAuthenticatedStationOfficer()?->station_id;

        if (! $stationId) {
            return collect();
        }

        $coverageBarangays = DB::table('station_barangay_coverage')
            ->join('barangays', 'station_barangay_coverage.barangay_id', '=', 'barangays.barangay_id')
            ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
            ->where('station_barangay_coverage.station_id', $stationId)
            ->where('station_barangay_coverage.is_active', true)
            ->select('barangays.barangay_id', 'barangays.barangay_name', 'barangays.city_id', 'cities.city_name')
            ->orderBy('barangays.barangay_name')
            ->get();

        if ($coverageBarangays->isNotEmpty()) {
            return $coverageBarangays
                ->unique('barangay_id')
                ->values();
        }

        $barangays = collect();

        $stationAddressBarangays = DB::table('stations')
            ->leftJoin('full_addresses', 'stations.address_id', '=', 'full_addresses.address_id')
            ->leftJoin('barangays', 'full_addresses.barangay_id', '=', 'barangays.barangay_id')
            ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
            ->where('stations.station_id', $stationId)
            ->select('barangays.barangay_id', 'barangays.barangay_name', 'barangays.city_id', 'cities.city_name')
            ->get()
            ->filter(fn ($row) => ! empty($row->barangay_id));

        $barangays = $barangays->merge($stationAddressBarangays);

        $cityIds = $stationAddressBarangays
            ->pluck('city_id')
            ->filter()
            ->unique()
            ->values();

        if ($cityIds->isNotEmpty()) {
            $barangays = $barangays->merge(
                DB::table('barangays')
                    ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
                    ->whereIn('barangays.city_id', $cityIds)
                    ->select('barangays.barangay_id', 'barangays.barangay_name', 'barangays.city_id', 'cities.city_name')
                    ->get()
            );
        }

        $riskAreaBarangays = DB::table('risk_areas')
            ->join('barangay_halls', 'risk_areas.barangay_hall_id', '=', 'barangay_halls.barangay_hall_id')
            ->join('barangays', 'barangay_halls.barangay_id', '=', 'barangays.barangay_id')
            ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
            ->where('risk_areas.station_id', $stationId)
            ->select('barangays.barangay_id', 'barangays.barangay_name', 'barangays.city_id', 'cities.city_name')
            ->get();

        return $barangays
            ->merge($riskAreaBarangays)
            ->unique('barangay_id')
            ->sortBy('barangay_name')
            ->values();
    }

    protected function mitigationCategoryOptions(): array
    {
        return [
            'electrical_safety' => 'Electrical Safety',
            'water_supply' => 'Water Supply',
            'response_readiness' => 'Response Readiness',
            'access_routing' => 'Access & Routing',
            'inspection_enforcement' => 'Inspection & Enforcement',
            'community_prevention' => 'Community Prevention',
            'evacuation_life_safety' => 'Evacuation & Life Safety',
            'high_risk_area_monitoring' => 'High-Risk Area Monitoring',
            'environmental_preparedness' => 'Environmental Preparedness',
            'other' => 'Other',
        ];
    }

    protected function mitigationPriorityOptions(): array
    {
        return [
            'critical' => 'Critical',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
        ];
    }

    protected function mitigationStatusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'planned' => 'Planned',
            'approved' => 'Approved',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    protected function mitigationStatusLabels(): array
    {
        return $this->mitigationStatusOptions() + [
            'blocked' => 'Blocked',
        ];
    }

    protected function mitigationEffectivenessOptions(): array
    {
        return [
            'pending_evaluation' => 'Pending Evaluation',
            'effective' => 'Effective',
            'partially_effective' => 'Partially Effective',
            'ineffective' => 'Ineffective',
        ];
    }

    protected function mitigationSourceOptions(): array
    {
        return [
            'ai_action_plan' => 'AI Action Plan',
            'ai_recommendation' => 'AI Recommendation',
            'prediction' => 'Prediction',
            'hazard_report' => 'Hazard Report',
            'incident_review' => 'Incident Review',
            'manual' => 'Manual',
        ];
    }

    protected function mitigationValidationRules(): array
    {
        return [
            'barangay_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys($this->mitigationCategoryOptions()))],
            'priority_level' => ['required', Rule::in(array_keys($this->mitigationPriorityOptions()))],
            'status' => ['required', Rule::in(array_keys($this->mitigationStatusLabels()))],
            'source_type' => ['nullable', Rule::in(array_keys($this->mitigationSourceOptions()))],
            'description' => ['required', 'string', 'max:3000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'implementation_notes' => ['nullable', 'string', 'max:3000'],
            'completion_notes' => ['nullable', 'string', 'max:3000'],
            'outcome_result' => ['nullable', 'string', 'max:3000'],
            'effectiveness' => ['nullable', Rule::in(array_keys($this->mitigationEffectivenessOptions()))],
            'follow_up_required' => ['nullable', 'boolean'],
            'follow_up_notes' => ['nullable', 'string', 'max:3000'],
            'target_date' => ['nullable', 'date'],
            'risk_area_id' => ['nullable', 'integer'],
            'barangay_prediction_checklist_id' => ['nullable', 'integer'],
            'ai_action_plan_checklist_id' => ['nullable', 'integer'],
            'responsible_team_id' => ['nullable', 'integer'],
            'responsible_user_id' => ['nullable', 'integer'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:10240'],
        ];
    }

    protected function validateMitigationLinksForStation(array $validated, array $allowedBarangayIds): array
    {
        $barangayId = (int) $validated['barangay_id'];
        $riskAreaId = ! empty($validated['risk_area_id']) ? (int) $validated['risk_area_id'] : null;
        $checklistId = ! empty($validated['barangay_prediction_checklist_id']) ? (int) $validated['barangay_prediction_checklist_id'] : null;
        $aiChecklistId = ! empty($validated['ai_action_plan_checklist_id']) ? (int) $validated['ai_action_plan_checklist_id'] : null;

        if (! in_array($barangayId, $allowedBarangayIds, true)) {
            throw ValidationException::withMessages([
                'barangay_id' => 'Select a barangay within your station jurisdiction.',
            ]);
        }

        if ($riskAreaId) {
            $riskAreaExists = RiskArea::query()
                ->where('risk_area_id', $riskAreaId)
                ->whereHas('barangayHall', fn ($query) => $query->where('barangay_id', $barangayId))
                ->exists();

            if (! $riskAreaExists) {
                throw ValidationException::withMessages([
                    'risk_area_id' => 'Select a risk or hazard record that belongs to the chosen barangay.',
                ]);
            }
        }

        if ($checklistId) {
            $checklistExists = BarangayPredictionChecklist::query()
                ->where('barangay_prediction_checklist_id', $checklistId)
                ->where('barangay_id', $barangayId)
                ->exists();

            if (! $checklistExists) {
                throw ValidationException::withMessages([
                    'barangay_prediction_checklist_id' => 'Select a checklist item that belongs to the chosen barangay.',
                ]);
            }
        }

        if ($aiChecklistId) {
            $aiChecklistExists = DB::table('ai_action_plan_checklists')
                ->where('ai_action_plan_checklist_id', $aiChecklistId)
                ->where('barangay_id', $barangayId)
                ->exists();

            if (! $aiChecklistExists) {
                throw ValidationException::withMessages([
                    'ai_action_plan_checklist_id' => 'Select an AI action plan that belongs to the chosen barangay.',
                ]);
            }
        }

        return [
            'barangay_id' => $barangayId,
            'risk_area_id' => $riskAreaId,
            'barangay_prediction_checklist_id' => $checklistId,
            'ai_action_plan_checklist_id' => $aiChecklistId,
        ];
    }

    protected function validateMitigationAssignments(array $validated, ?int $stationId): array
    {
        $teamId = ! empty($validated['responsible_team_id']) ? (int) $validated['responsible_team_id'] : null;
        $userId = ! empty($validated['responsible_user_id']) ? (int) $validated['responsible_user_id'] : null;

        if ($teamId && $stationId) {
            $teamExists = $this->getTeamsForStation($stationId)
                ->where('team_id', $teamId)
                ->exists();

            if (! $teamExists) {
                throw ValidationException::withMessages([
                    'responsible_team_id' => 'Select a response team that belongs to your station.',
                ]);
            }
        }

        if ($userId && $stationId) {
            $userExists = StationOfficer::query()
                ->where('station_id', $stationId)
                ->where('user_id', $userId)
                ->exists();

            if (! $userExists) {
                throw ValidationException::withMessages([
                    'responsible_user_id' => 'Select a station officer assigned to your station.',
                ]);
            }
        }

        return [
            'responsible_team_id' => $teamId,
            'responsible_user_id' => $userId,
        ];
    }

    protected function normalizeMitigationCategory(?string $value): string
    {
        $value = trim((string) $value);
        if ($value !== '' && array_key_exists($value, $this->mitigationCategoryOptions())) {
            return $value;
        }

        return match ($value) {
            'electrical' => 'electrical_safety',
            'hydrant_water' => 'water_supply',
            'equipment', 'training', 'community_response' => 'response_readiness',
            'emergency_access' => 'access_routing',
            'inspection', 'policy' => 'inspection_enforcement',
            'awareness', 'housekeeping' => 'community_prevention',
            'infrastructure' => 'environmental_preparedness',
            default => 'other',
        };
    }

    protected function mitigationCategoryFilterAliases(string $value): array
    {
        return match ($value) {
            'electrical_safety' => ['electrical_safety', 'electrical'],
            'water_supply' => ['water_supply', 'hydrant_water'],
            'response_readiness' => ['response_readiness', 'equipment', 'training', 'community_response'],
            'access_routing' => ['access_routing', 'emergency_access'],
            'inspection_enforcement' => ['inspection_enforcement', 'inspection', 'policy'],
            'community_prevention' => ['community_prevention', 'awareness', 'housekeeping'],
            'environmental_preparedness' => ['environmental_preparedness', 'infrastructure'],
            default => [$value],
        };
    }

    protected function resolveMitigationSourceType(array $attributes): string
    {
        if (! empty($attributes['ai_action_plan_checklist_id'])) {
            return 'ai_action_plan';
        }

        if (! empty($attributes['barangay_prediction_checklist_id'])) {
            return 'prediction';
        }

        if (! empty($attributes['risk_area_id'])) {
            return 'hazard_report';
        }

        return $attributes['source_type'] ?? 'manual';
    }

    protected function getMitigationSourceLabel(?string $sourceType): string
    {
        return $this->mitigationSourceOptions()[$sourceType ?: 'manual'] ?? ucfirst(str_replace('_', ' ', (string) $sourceType));
    }

    protected function getMitigationStatusLabel(?string $status): string
    {
        return $this->mitigationStatusLabels()[$status ?: 'draft'] ?? ucfirst(str_replace('_', ' ', (string) $status));
    }

    protected function buildMitigationProgressSnapshot(MitigationAction $action): array
    {
        $items = collect($action->aiActionPlanChecklist?->items ?? []);
        $totalTasks = $items->count();
        $completedTasks = $items->where('status', 'followed')->count();

        if ($action->status === 'completed' && $totalTasks > 0) {
            $completedTasks = $totalTasks;
        }

        $percent = match ($action->status) {
            'draft' => 0,
            'planned' => 15,
            'approved' => 30,
            'in_progress' => $totalTasks > 0 ? (int) round(($completedTasks / max(1, $totalTasks)) * 100) : 55,
            'completed' => 100,
            'cancelled' => 0,
            'blocked' => $totalTasks > 0 ? (int) round(($completedTasks / max(1, $totalTasks)) * 100) : 40,
            default => $totalTasks > 0 ? (int) round(($completedTasks / max(1, $totalTasks)) * 100) : 0,
        };

        if ($totalTasks > 0 && $percent === 0 && in_array($action->status, ['planned', 'approved'], true)) {
            $percent = $action->status === 'approved' ? 30 : 15;
        }

        return [
            'completed_tasks' => $completedTasks,
            'total_tasks' => $totalTasks,
            'percent' => min(100, max(0, $percent)),
        ];
    }

    protected function resolveManagedMitigationAction(int $mitigationActionId, array $allowedBarangayIds): MitigationAction
    {
        return MitigationAction::query()
            ->with([
                'barangay.city',
                'attachments',
                'riskArea.marker',
                'checklist',
                'aiActionPlanChecklist.analysis',
                'aiActionPlanChecklist.suggestions',
                'aiActionPlanChecklist.items',
                'creator',
                'responsibleTeam',
                'responsibleUser',
                'approver',
            ])
            ->whereIn('barangay_id', $allowedBarangayIds)
            ->findOrFail($mitigationActionId);
    }

    protected function buildMitigationDraftFromActionPlanChecklist(int $checklistId, array $allowedBarangayIds): ?array
    {
        $stationId = $this->getAuthenticatedStationOfficer()?->station_id;
        $checklist = AiActionPlanChecklist::query()
            ->with(['analysis', 'barangay', 'riskArea.marker', 'suggestions'])
            ->where('ai_action_plan_checklist_id', $checklistId)
            ->where(function ($query) use ($allowedBarangayIds, $stationId) {
                if (! empty($allowedBarangayIds)) {
                    $query->whereIn('barangay_id', $allowedBarangayIds);

                    if ($stationId) {
                        $query->orWhere(function ($fallbackQuery) use ($stationId) {
                            $fallbackQuery
                                ->whereNull('barangay_id')
                                ->where('station_id', $stationId);
                        });
                    }

                    return;
                }

                if ($stationId) {
                    $query->where('station_id', $stationId);
                    return;
                }

                $query->whereRaw('1 = 0');
            })
            ->first();

        if (! $checklist) {
            return null;
        }

        $firstSuggestion = $checklist->suggestions->sortByDesc('created_at')->first();
        $steps = collect($firstSuggestion?->steps ?: $checklist->checklist_items ?: [])
            ->filter()
            ->map(fn ($step) => '- ' . trim((string) $step))
            ->values()
            ->all();

        $resources = collect($firstSuggestion?->resources_needed ?: [])
            ->filter()
            ->map(fn ($resource) => '- ' . trim((string) $resource))
            ->values()
            ->all();

        $descriptionParts = array_filter([
            $checklist->plan_description ?: $checklist->source_description,
            ! empty($steps) ? "Suggested steps:\n" . implode("\n", $steps) : null,
            ! empty($resources) ? "Resources needed:\n" . implode("\n", $resources) : null,
            $firstSuggestion?->estimated_timeline ? "Suggested timeline: {$firstSuggestion->estimated_timeline}" : null,
        ]);

        return [
            'barangay_id' => $checklist->barangay_id,
            'title' => $checklist->plan_title,
            'category' => $this->inferMitigationCategoryFromChecklist($checklist),
            'priority_level' => $checklist->priority_level ?: 'medium',
            'status' => 'draft',
            'source_type' => 'ai_action_plan',
            'target_date' => optional($checklist->target_date)->format('Y-m-d'),
            'description' => implode("\n\n", $descriptionParts),
            'remarks' => $firstSuggestion?->estimated_timeline
                ? 'Drafted from AI action-plan checklist. Timeline suggestion: ' . $firstSuggestion->estimated_timeline
                : 'Drafted from AI action-plan checklist.',
            'implementation_notes' => null,
            'completion_notes' => null,
            'outcome_result' => null,
            'effectiveness' => 'pending_evaluation',
            'follow_up_required' => false,
            'follow_up_notes' => null,
            'responsible_team_id' => null,
            'responsible_user_id' => null,
            'risk_area_id' => $checklist->risk_area_id,
            'ai_action_plan_checklist_id' => $checklist->ai_action_plan_checklist_id,
            'source_title' => $checklist->source_title,
            'workflow_note' => $this->isGeneratedMitigationDraftChecklist($checklist)
                ? 'Prediction / Risk Area -> System Recommendation Draft -> Human Review -> Mitigation Action Plan'
                : 'Prediction -> Recommendation -> Checklist -> Mitigation Action Plan',
        ];
    }

    protected function inferMitigationCategoryFromChecklist(AiActionPlanChecklist $checklist): string
    {
        $payloadCategory = data_get($checklist->ai_payload, 'recommended_category');
        if (is_string($payloadCategory) && array_key_exists($payloadCategory, $this->mitigationCategoryOptions())) {
            return $payloadCategory;
        }

        $haystack = Str::lower(trim(implode(' ', array_filter([
            $checklist->plan_title,
            $checklist->plan_description,
            $checklist->source_title,
            $checklist->source_description,
        ]))));

        return match (true) {
            str_contains($haystack, 'inspect') || str_contains($haystack, 'inspection') || str_contains($haystack, 'enforcement') => 'inspection_enforcement',
            str_contains($haystack, 'awareness') || str_contains($haystack, 'brief') || str_contains($haystack, 'campaign') => 'community_prevention',
            str_contains($haystack, 'route') || str_contains($haystack, 'access') || str_contains($haystack, 'dispatch') => 'access_routing',
            str_contains($haystack, 'hydrant') || str_contains($haystack, 'water') => 'water_supply',
            str_contains($haystack, 'electrical') || str_contains($haystack, 'wiring') => 'electrical_safety',
            str_contains($haystack, 'waste') || str_contains($haystack, 'housekeeping') || str_contains($haystack, 'combustible') => 'community_prevention',
            str_contains($haystack, 'community') || str_contains($haystack, 'resident') || str_contains($haystack, 'volunteer') => 'community_prevention',
            str_contains($haystack, 'training') || str_contains($haystack, 'drill') || str_contains($haystack, 'readiness') => 'response_readiness',
            str_contains($haystack, 'evacu') || str_contains($haystack, 'life safety') => 'evacuation_life_safety',
            str_contains($haystack, 'monitor') || str_contains($haystack, 'surveillance') => 'high_risk_area_monitoring',
            str_contains($haystack, 'rain') || str_contains($haystack, 'weather') || str_contains($haystack, 'environment') => 'environmental_preparedness',
            default => 'other',
        };
    }

    protected function isGeneratedMitigationDraftChecklist(AiActionPlanChecklist $checklist): bool
    {
        return ! empty($checklist->source_signature)
            && data_get($checklist->ai_payload, 'workflow_type') === 'mitigation_recommendation_draft';
    }

    protected function buildMitigationResourceContext(?int $stationId): array
    {
        $teamCount = $stationId
            ? (int) $this->getTeamsForStation($stationId)->count()
            : 0;
        $personnelCount = $stationId
            ? (int) StationOfficer::query()->where('station_id', $stationId)->count()
            : 0;
        $availableCrewCount = $stationId
            ? (int) StationOfficer::query()
                ->where('station_id', $stationId)
                ->whereIn('status', ['available', 'dispatched', 'on-scene'])
                ->count()
            : 0;
        $hydrantCount = (int) Marker::query()
            ->where('category', 'hydrant')
            ->count();
        $activeIncidentCount = $stationId
            ? (int) Incident::query()
                ->whereIn('team_id', $this->getTeamIdsForStation($stationId)->all())
                ->whereIn('status', ['Responding', 'On Scene'])
                ->count()
            : 0;

        return [
            'team_count' => $teamCount,
            'personnel_count' => $personnelCount,
            'available_crew_count' => $availableCrewCount,
            'hydrant_count' => $hydrantCount,
            'active_incident_count' => $activeIncidentCount,
        ];
    }

    protected function buildSystemMitigationRecommendationPayload(array $predictedArea, array $resourceContext): ?array
    {
        $riskAreaId = (int) ($predictedArea['primary_risk_area_id'] ?? 0);
        $barangayId = (int) ($predictedArea['barangay_id'] ?? 0);

        if (! $riskAreaId || ! $barangayId) {
            return null;
        }

        $riskLevel = strtolower((string) ($predictedArea['risk_level'] ?? 'medium'));
        $category = $this->resolveMitigationCategoryForPredictedArea($predictedArea);
        $title = $this->buildMitigationRecommendationTitle($predictedArea, $category);
        $signature = 'risk-area:' . $riskAreaId . '|category:' . $category;
        $targetDays = match ($riskLevel) {
            'high' => 7,
            'medium' => 14,
            default => 30,
        };
        $actionTypeLabel = $this->mitigationCategoryOptions()[$category] ?? ucfirst(str_replace('_', ' ', $category));
        $drivers = array_slice(array_values($predictedArea['drivers'] ?? []), 0, 4);
        $factorSummary = array_slice(array_values($predictedArea['contributing_factors'] ?? []), 0, 4);
        $descriptionParts = array_filter([
            "System-generated mitigation draft for a {$riskLevel} predicted risk zone near " . ($predictedArea['name'] ?? 'the affected area') . '.',
            ! empty($drivers) ? 'Key drivers: ' . implode('; ', $drivers) . '.' : null,
            ! empty($factorSummary) ? 'Contributing factors observed: ' . implode(', ', array_map(fn ($factor) => str_replace('_', ' ', (string) $factor), $factorSummary)) . '.' : null,
            "Nearby approved hazards/risks: " . (int) data_get($predictedArea, 'metrics.verified_records', 0)
                . " | Historical incidents: " . (int) data_get($predictedArea, 'metrics.historical_incidents', 0)
                . " | Support points in cluster: " . (int) data_get($predictedArea, 'metrics.support_points', 0) . '.',
            "Response/resource context: {$resourceContext['team_count']} team(s), {$resourceContext['personnel_count']} personnel, {$resourceContext['available_crew_count']} currently available/dispatched crew, {$resourceContext['hydrant_count']} hydrant marker(s), {$resourceContext['active_incident_count']} active incident(s).",
            "Recommended action type: {$actionTypeLabel}.",
        ]);

        $checklistItems = $this->buildGeneratedMitigationChecklistItems($predictedArea, $category, $resourceContext);

        return [
            'analysis_id' => null,
            'station_id' => $this->getAuthenticatedStationOfficer()?->station_id,
            'barangay_id' => $barangayId,
            'risk_area_id' => $riskAreaId,
            'source_type' => 'action_plan',
            'source_title' => 'System mitigation recommendation for ' . ($predictedArea['name'] ?? 'predicted risk area'),
            'source_description' => 'Generated from clustered nearby evidence, approved hazards/risks, historical incidents, and station response context.',
            'plan_title' => $title,
            'plan_description' => implode("\n\n", $descriptionParts),
            'checklist_items' => $checklistItems,
            'success_indicator' => 'Chief/Station Admin approves the draft and the resulting mitigation action is tracked manually until completion.',
            'priority_level' => $riskLevel === 'high' ? 'high' : 'medium',
            'status' => 'pending',
            'source_signature' => $signature,
            'target_date' => now()->addDays($targetDays)->toDateString(),
            'assigned_to' => null,
            'confirmed_by' => null,
            'confirmed_at' => null,
            'confirmation_notes' => null,
            'ai_payload' => [
                'workflow_type' => 'mitigation_recommendation_draft',
                'recommended_category' => $category,
                'risk_level' => $riskLevel,
                'risk_score' => $predictedArea['risk_score'] ?? null,
                'drivers' => $predictedArea['drivers'] ?? [],
                'contributing_factors' => $predictedArea['contributing_factors'] ?? [],
                'supporting_risk_area_ids' => $predictedArea['supporting_risk_area_ids'] ?? [],
                'resource_context' => $resourceContext,
                'predicted_area' => [
                    'name' => $predictedArea['name'] ?? null,
                    'center' => $predictedArea['center'] ?? null,
                    'radius_meters' => $predictedArea['radius_meters'] ?? null,
                    'metrics' => $predictedArea['metrics'] ?? [],
                ],
            ],
            'is_ai_generated' => false,
        ];
    }

    protected function resolveMitigationCategoryForPredictedArea(array $predictedArea): string
    {
        $factors = collect($predictedArea['contributing_factors'] ?? [])->map(fn ($factor) => (string) $factor);
        $condition = Str::lower((string) ($predictedArea['primary_condition_label'] ?? ''));
        $entryType = Str::lower((string) ($predictedArea['primary_entry_type'] ?? 'risk'));

        return match (true) {
            $factors->contains('blocked_hydrant') => 'water_supply',
            $factors->contains('poor_access') => 'access_routing',
            str_contains($condition, 'electrical') || str_contains($condition, 'wiring') => 'electrical_safety',
            str_contains($condition, 'gas') || str_contains($condition, 'lpg') => 'inspection_enforcement',
            $factors->contains('combustible_materials_present') || $factors->contains('poor_waste_disposal') => 'community_prevention',
            $factors->contains('informal_settlement') || $factors->contains('previous_fire_prone_location') => 'high_risk_area_monitoring',
            $factors->contains('cooking_fire_prone_activity') => 'community_prevention',
            $entryType === 'hazard' => 'inspection_enforcement',
            default => 'high_risk_area_monitoring',
        };
    }

    protected function buildMitigationRecommendationTitle(array $predictedArea, string $category): string
    {
        $location = $predictedArea['primary_marker_title']
            ?: $predictedArea['primary_condition_label']
            ?: ($predictedArea['name'] ?? 'risk zone');
        $barangayName = collect($predictedArea['barangay_names'] ?? [])->first() ?: 'the affected barangay';
        $actionLabel = match ($category) {
            'water_supply' => 'Restore hydrant and water access',
            'access_routing' => 'Clear emergency access routes',
            'electrical_safety' => 'Correct electrical fire hazards',
            'community_prevention' => 'Run focused community prevention work',
            'response_readiness' => 'Prepare targeted response readiness action',
            'evacuation_life_safety' => 'Improve evacuation and life-safety readiness',
            'high_risk_area_monitoring' => 'Monitor and reduce high-risk area exposure',
            'environmental_preparedness' => 'Strengthen environmental fire preparedness',
            'inspection_enforcement' => 'Inspect and enforce fire-safety compliance',
            default => 'Inspect and mitigate high-risk condition',
        };

        return trim($actionLabel . ' near ' . $location . ', ' . $barangayName);
    }

    protected function buildGeneratedMitigationChecklistItems(array $predictedArea, string $category, array $resourceContext): array
    {
        $baseItems = [
            'Assign the responsible station lead and coordinate with the barangay for field validation.',
            'Document the current risk condition, supporting markers, and nearby historical incident context before deployment.',
        ];

        $categoryItems = match ($category) {
            'water_supply' => [
                'Inspect the nearby hydrant or alternate water source and clear any blockage or access issue.',
                'Coordinate with barangay or utility partners if repair, access control, or water restoration is required.',
            ],
            'access_routing' => [
                'Survey roads, alleys, or obstructions limiting fire truck access within the predicted zone.',
                'Coordinate clearing, signage, or temporary access control with local responders and barangay officials.',
            ],
            'electrical_safety' => [
                'Conduct focused inspection on exposed wiring, overloaded outlets, or illegal connections in the affected area.',
                'Advise occupants or property managers on immediate electrical corrections and document compliance.',
            ],
            'community_prevention' => [
                'Remove or isolate combustible waste, improper storage, or flammable buildup contributing to the risk zone.',
                'Coordinate recurring cleanup and follow-up inspection with barangay personnel or property occupants.',
            ],
            'response_readiness' => [
                'Schedule a targeted drill or readiness activity for the responding team assigned to this risk pattern.',
                'Review resource gaps or staging needs identified from the current predicted risk context.',
            ],
            'inspection_enforcement' => [
                'Perform a focused inspection and document the code, hazard, or occupancy conditions driving the risk.',
                'Coordinate notices, enforcement reminders, or corrective actions with the responsible occupants or barangay.',
            ],
            'evacuation_life_safety' => [
                'Review evacuation paths, assembly points, and life-safety readiness around the affected area.',
                'Coordinate signage, drills, or occupant reminders that improve safe evacuation behavior.',
            ],
            'high_risk_area_monitoring' => [
                'Set a short-term monitoring cadence for the hotspot and verify whether the same drivers remain active.',
                'Coordinate barangay reporting and rapid follow-up if the risk cluster intensifies or spreads.',
            ],
            'environmental_preparedness' => [
                'Review weather-driven or environmental conditions contributing to the forecast and prepare preventive actions.',
                'Coordinate seasonal readiness measures for dry conditions, vegetation, wind, or similar local factors.',
            ],
            default => [
                'Perform a focused site inspection and validate the dominant factors contributing to the predicted zone.',
                'Apply the most appropriate risk reduction measure based on the verified field condition.',
            ],
        };

        $resourceItem = $resourceContext['team_count'] > 0
            ? "Confirm which of the {$resourceContext['team_count']} available team(s) will cover the follow-up and what support resources are required."
            : 'Confirm available response resources before scheduling the mitigation work.';

        return array_values(array_filter(array_merge(
            $baseItems,
            $categoryItems,
            [
                $resourceItem,
                'Record proof of action and keep manual progress/status updates on the official mitigation record after approval.',
            ]
        )));
    }

    protected function syncGeneratedMitigationDraftRecommendations(?int $stationId, array $allowedBarangayIds): void
    {
        if (! $stationId || empty($allowedBarangayIds)) {
            return;
        }

        $resourceContext = $this->buildMitigationResourceContext($stationId);
        $predictedAreas = $this->predictedRiskAreaService
            ->buildPredictedAreas($stationId)
            ->filter(fn (array $area) => in_array((int) ($area['barangay_id'] ?? 0), $allowedBarangayIds, true))
            ->values();

        foreach ($predictedAreas as $predictedArea) {
            $payload = $this->buildSystemMitigationRecommendationPayload($predictedArea, $resourceContext);
            if (! $payload) {
                continue;
            }

            $hasOpenMitigation = MitigationAction::query()
                ->where('risk_area_id', $payload['risk_area_id'])
                ->where('category', data_get($payload, 'ai_payload.recommended_category', 'other'))
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->exists();

            if ($hasOpenMitigation) {
                continue;
            }

            $existingDraft = AiActionPlanChecklist::query()
                ->where('source_signature', $payload['source_signature'])
                ->first();

            if ($existingDraft) {
                if ($existingDraft->status === 'rejected') {
                    continue;
                }

                if ($existingDraft->status === 'confirmed') {
                    $hasLinkedMitigation = MitigationAction::query()
                        ->where('ai_action_plan_checklist_id', $existingDraft->ai_action_plan_checklist_id)
                        ->exists();

                    if ($hasLinkedMitigation) {
                        continue;
                    }
                }

                $existingDraft->update([
                    'analysis_id' => $payload['analysis_id'],
                    'station_id' => $payload['station_id'],
                    'barangay_id' => $payload['barangay_id'],
                    'risk_area_id' => $payload['risk_area_id'],
                    'source_type' => $payload['source_type'],
                    'source_title' => $payload['source_title'],
                    'source_description' => $payload['source_description'],
                    'plan_title' => $payload['plan_title'],
                    'plan_description' => $payload['plan_description'],
                    'checklist_items' => $payload['checklist_items'],
                    'success_indicator' => $payload['success_indicator'],
                    'priority_level' => $payload['priority_level'],
                    'target_date' => $payload['target_date'],
                    'ai_payload' => $payload['ai_payload'],
                    'is_ai_generated' => $payload['is_ai_generated'],
                ]);

                $this->syncChecklistItems($existingDraft, $payload['checklist_items']);
                continue;
            }

            $draft = AiActionPlanChecklist::create($payload);
            $this->syncChecklistItems($draft, $payload['checklist_items']);
        }
    }

    protected function resolveMitigationDraftChecklist(int $checklistId, array $allowedBarangayIds): AiActionPlanChecklist
    {
        $stationId = $this->getAuthenticatedStationOfficer()?->station_id;
        $checklist = AiActionPlanChecklist::query()
            ->with(['barangay', 'riskArea.marker', 'mitigationActions'])
            ->where('ai_action_plan_checklist_id', $checklistId)
            ->where(function ($query) use ($allowedBarangayIds, $stationId) {
                if (! empty($allowedBarangayIds)) {
                    $query->whereIn('barangay_id', $allowedBarangayIds);

                    if ($stationId) {
                        $query->orWhere(function ($fallbackQuery) use ($stationId) {
                            $fallbackQuery
                                ->whereNull('barangay_id')
                                ->where('station_id', $stationId);
                        });
                    }

                    return;
                }

                if ($stationId) {
                    $query->where('station_id', $stationId);
                    return;
                }

                $query->whereRaw('1 = 0');
            })
            ->firstOrFail();

        $isEligibleDraft = $this->isGeneratedMitigationDraftChecklist($checklist)
            || (($checklist->source_type ?? null) === 'action_plan' && $checklist->status === 'pending');

        if (! $isEligibleDraft) {
            throw (new ModelNotFoundException())->setModel(AiActionPlanChecklist::class, [$checklistId]);
        }

        return $checklist;
    }

    protected function markGeneratedMitigationDraftApproved(AiActionPlanChecklist $checklist, ?int $userId, int $mitigationActionId): void
    {
        $checklist->update([
            'status' => 'confirmed',
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
            'confirmation_notes' => 'Approved and converted to mitigation action #' . $mitigationActionId . '.',
        ]);
    }

    protected function markGeneratedMitigationDraftRejected(AiActionPlanChecklist $checklist, ?string $notes = null): void
    {
        $checklist->update([
            'status' => 'rejected',
            'confirmed_by' => Auth::user()?->user_id,
            'confirmed_at' => now(),
            'confirmation_notes' => $notes ?: 'Rejected by Chief / Station Admin before mitigation action creation.',
        ]);
    }

    protected function syncLinkedActionPlanAfterMitigation(MitigationAction $action): void
    {
        if (! $action->ai_action_plan_checklist_id) {
            return;
        }

        $checklist = AiActionPlanChecklist::query()->find($action->ai_action_plan_checklist_id);
        if (! $checklist) {
            return;
        }

        if ($this->isGeneratedMitigationDraftChecklist($checklist)) {
            $this->markGeneratedMitigationDraftApproved(
                $checklist,
                Auth::user()?->user_id ?? $action->reviewed_by ?? $action->created_by,
                (int) $action->mitigation_action_id
            );
            return;
        }

        if ($action->status === 'completed' && ! in_array($checklist->status, ['confirmed', 'rejected'], true)) {
            $checklist->update(['status' => 'submitted']);
            return;
        }

        if (in_array($action->status, ['approved', 'in_progress', 'blocked'], true) && $checklist->status === 'pending') {
            $checklist->update(['status' => 'in_progress']);
            return;
        }

        if (in_array($action->status, ['draft', 'planned'], true) && $checklist->status === 'in_progress') {
            $checklist->update(['status' => 'pending']);
        }
    }

    protected function storeMitigationAttachments(MitigationAction $action, Request $request): void
    {
        foreach ($request->file('attachments', []) as $file) {
            if (! $file) {
                continue;
            }

            $storedPath = MediaPath::storeForDisk(
                $file->store('mitigation-actions', 'supabase'),
                'supabase'
            );

            MitigationActionAttachment::create([
                'mitigation_action_id' => $action->mitigation_action_id,
                'file_path' => $storedPath,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'attachment_type' => str_starts_with((string) $file->getMimeType(), 'image/') ? 'photo' : 'proof',
            ]);
        }
    }

    protected function syncChecklistItems(AiActionPlanChecklist $checklist, array $items): void
    {
        $checklist->items()->delete();

        $rows = collect($items)
            ->filter(fn ($item) => trim((string) $item) !== '')
            ->values()
            ->map(function ($item, $index) use ($checklist) {
                return [
                    'ai_action_plan_checklist_id' => $checklist->ai_action_plan_checklist_id,
                    'item_order' => $index + 1,
                    'item_text' => trim((string) $item),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->all();

        if (! empty($rows)) {
            AiActionPlanChecklistItem::query()->insert($rows);
        }
    }

    protected function generateCitizenAccountCode(): string
    {
        $roleCode = 'CIT';
        $lastUser = User::where('account_code', 'LIKE', $roleCode . '-%')
            ->orderByDesc('user_id')
            ->first();

        $newNumber = 1;
        if ($lastUser?->account_code) {
            $lastNumber = (int) substr($lastUser->account_code, strlen($roleCode) + 1);
            $newNumber = $lastNumber + 1;
        }

        return $roleCode . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    public function announcement(){
        return view('chief.announcements');
    }

    public function schedule()
    {
        $stationOfficer = $this->getAuthenticatedStationOfficer();
        $stationId = $stationOfficer?->station_id;
        $stationName = $stationOfficer?->station?->station_name;
        $urbiztondoBarangays = Barangay::query()
            ->whereHas('city', function ($query) {
                $query->whereRaw('LOWER(city_name) = ?', ['urbiztondo']);
            })
            ->orderBy('barangay_name')
            ->get(['barangay_id', 'barangay_name']);

        $scheduleEvents = collect();

        if ($stationId) {
            $scheduleEvents = EventActivity::query()
                ->where('station_id', $stationId)
                ->whereNull('user_id')
                ->orderBy('event_date')
                ->orderBy('start_time')
                ->get()
                ->map(fn (EventActivity $event) => $this->serializeScheduleEvent($event))
                ->values();
        }

        return view('chief.schedule', [
            'scheduleEvents' => $scheduleEvents,
            'stationId' => $stationId,
            'stationName' => $stationName,
            'urbiztondoBarangays' => $urbiztondoBarangays,
        ]);
    }

    public function storeScheduleEvent(Request $request)
    {
        $stationOfficer = $this->getAuthenticatedStationOfficer();
        $stationId = $stationOfficer?->station_id;

        if (!$stationId) {
            return response()->json([
                'success' => false,
                'message' => 'No station is linked to the current commander account.'
            ], 422);
        }

        $validBarangayNames = Barangay::query()
            ->whereHas('city', function ($query) {
                $query->whereRaw('LOWER(city_name) = ?', ['urbiztondo']);
            })
            ->pluck('barangay_name')
            ->all();

        $validated = $this->validateSchedulePayload($request, $validBarangayNames);
        $windowError = $this->assertValidScheduleWindow($validated);

        if ($windowError) {
            return $windowError;
        }

        $hasConflict = $this->hasOfficialScheduleConflict($stationId, $validated);

        if ($hasConflict) {
            return response()->json([
                'success' => false,
                'message' => 'The selected time range is already occupied for this station.'
            ], 422);
        }

        $scheduleLabel = $this->buildScheduleLabel($validated['start_time'], $validated['end_time']);

        $event = EventActivity::create([
            'event' => $validated['event'],
            'event_date' => $validated['event_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'schedule' => $scheduleLabel,
            'activity_type' => $validated['activity_type'] ?? 'operations',
            'status' => $validated['status'] ?? 'planned',
            'barangay' => $validated['barangay'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'station_id' => $stationId,
            'user_id' => null,
        ]);

        UserActivityLogger::log('created schedule event', Auth::user()?->user_id, $request);

        return response()->json([
            'success' => true,
            'message' => 'Schedule event created successfully.',
            'data' => [
                ...$this->serializeScheduleEvent($event),
            ],
        ]);
    }

    public function updateScheduleEvent(Request $request, EventActivity $eventActivity)
    {
        $stationOfficer = $this->getAuthenticatedStationOfficer();
        $stationId = $stationOfficer?->station_id;

        if (! $stationId || (int) $eventActivity->station_id !== (int) $stationId || $eventActivity->user_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to edit this schedule event.',
            ], 403);
        }

        $validBarangayNames = Barangay::query()
            ->whereHas('city', function ($query) {
                $query->whereRaw('LOWER(city_name) = ?', ['urbiztondo']);
            })
            ->pluck('barangay_name')
            ->all();

        $validated = $this->validateSchedulePayload($request, $validBarangayNames);
        $windowError = $this->assertValidScheduleWindow($validated);

        if ($windowError) {
            return $windowError;
        }

        if ($this->hasOfficialScheduleConflict($stationId, $validated, (int) $eventActivity->event_id)) {
            return response()->json([
                'success' => false,
                'message' => 'The selected time range is already occupied for this station.'
            ], 422);
        }

        $eventActivity->update([
            'event' => $validated['event'],
            'event_date' => $validated['event_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'schedule' => $this->buildScheduleLabel($validated['start_time'], $validated['end_time']),
            'activity_type' => $validated['activity_type'] ?? 'operations',
            'status' => $validated['status'] ?? 'planned',
            'barangay' => $validated['barangay'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        UserActivityLogger::log('updated schedule event', Auth::user()?->user_id, $request);

        return response()->json([
            'success' => true,
            'message' => 'Schedule event updated successfully.',
            'data' => $this->serializeScheduleEvent($eventActivity->fresh()),
        ]);
    }

    public function destroyScheduleEvent(Request $request, EventActivity $eventActivity)
    {
        $stationOfficer = $this->getAuthenticatedStationOfficer();
        $stationId = $stationOfficer?->station_id;

        if (! $stationId || (int) $eventActivity->station_id !== (int) $stationId || $eventActivity->user_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to delete this schedule event.',
            ], 403);
        }

        $deletedEventId = $eventActivity->event_id;
        $eventActivity->delete();
        UserActivityLogger::log('deleted schedule event', Auth::user()?->user_id, $request);

        return response()->json([
            'success' => true,
            'message' => 'Schedule event deleted successfully.',
            'data' => [
                'event_id' => $deletedEventId,
            ],
        ]);
    }

    public function profile()
    {
        $user = User::with(['role', 'address.barangay.city'])->findOrFail(Auth::user()->user_id);
        $cities = City::with('barangays')
            ->orderBy('city_name')
            ->get();

        return view('chief.profile', compact('user', 'cities'));
    }

    public function updateProfile(Request $request)
    {
        $user = User::with('address.barangay.city')->findOrFail(Auth::user()->user_id);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'city_id' => ['required', 'exists:cities,city_id'],
            'barangay_id' => [
                'required',
                Rule::exists('barangays', 'barangay_id')->where(function ($query) use ($request) {
                    $query->where('city_id', $request->city_id);
                }),
            ],
            'street' => ['required', 'string', 'max:255'],
        ]);

        $user->first_name = $validated['first_name'];
        $user->middle_name = $validated['middle_name'] ?? null;
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->contact_number = $validated['contact_number'] ?? null;

        $address = $user->address;

        if ($address) {
            $address->street = $validated['street'];
            $address->barangay_id = $validated['barangay_id'];
            $address->save();
        } else {
            $address = FullAddress::create([
                'street' => $validated['street'],
                'barangay_id' => $validated['barangay_id'],
                'postal_id' => null,
            ]);

            $user->address_id = $address->address_id;
        }

        $user->save();
        UserActivityLogger::log('updated profile', Auth::user()?->user_id, $request);
        $user->load(['role', 'address.barangay.city']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'user' => [
                    'first_name' => $user->first_name,
                    'middle_name' => $user->middle_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'contact_number' => $user->contact_number,
                    'full_name' => trim($user->first_name . ' ' . $user->last_name),
                    'initials' => strtoupper(substr($user->first_name ?: 'C', 0, 1) . substr(($user->last_name ?: $user->first_name ?: 'P'), 0, 1)),
                    'role' => $user->role->role ?? 'Chief',
                    'street' => $user->address?->street,
                    'barangay' => $user->address?->barangay?->barangay_name,
                    'barangay_id' => $user->address?->barangay?->barangay_id,
                    'city' => $user->address?->barangay?->city?->city_name,
                    'city_id' => $user->address?->barangay?->city?->city_id,
                ],
            ]);
        }

        return redirect()
            ->route('station-admin.profile')
            ->with('success', 'Profile updated successfully.');
    }

    public function index()
    {   
        $accountId = Auth::user()->user_id;

        $mapmarker = Marker::with('stations')->get();
        $incidents = Incident::with(['marker', 'team', 'reporter'])
            ->orderBy('created_at')
            ->get();
        $alerts = $incidents;
        $stations = Station::all();
        $provinces = Province::orderBy('province_name')->get();
        $thisperson = $this->getAuthenticatedStationOfficer();
        $stationId = $thisperson->station_id;
        $stationperson = StationOfficer::where('station_id',$stationId)->with('station','station.users')->get();
        $citizenRoleId = Role::where('role', 'citizen')->value('role_id');
        $citizens = User::when($citizenRoleId, fn ($query) => $query->where('role_id', $citizenRoleId))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $barangayOfficers = BarangayOfficer::with(['user', 'barangay'])
            ->where('status', 'active')
            ->orderBy('position')
            ->get();
        $defaultTeam = $this->getFirstTeamForStation($stationId);
        $teamIds = $this->getTeamIdsForStation($stationId);
        $stationOfficerIds = StationOfficer::where('station_id', $stationId)
            ->pluck('station_officer_id')
            ->filter()
            ->values();
        $incidentTrendData = $this->buildIncidentTrendData($incidents);
        $stationReadiness = $this->buildStationReadinessData();
        $recentAnalyses = $stationId
            ? DB::table('analysis')
                ->where('station_id', $stationId)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(function ($analysis) {
                    $analysis->confidence_score = is_numeric($analysis->confidence_data ?? null)
                        ? (int) $analysis->confidence_data
                        : ($analysis->confidence_data ?? null);
                    $analysis->predictions = json_decode($analysis->predictions ?? 'null', true);
                    $analysis->recommendations = json_decode($analysis->recommendations ?? '[]', true);

                    return $analysis;
                })
            : collect();
        $analysisTrendSeries = collect($recentAnalyses)
            ->sortBy('created_at')
            ->values()
            ->map(function ($analysis) {
                return [
                    'label' => optional(\Carbon\Carbon::parse($analysis->created_at))->format('M d'),
                    'incident_count' => (int) ($analysis->incident_count ?? 0),
                    'predicted_fire_incidents' => (int) (data_get($analysis->predictions ?? [], 'predicted_fire_incidents') ?? 0),
                    'confidence' => (int) ($analysis->confidence_score ?? 0),
                    'risk_level' => (string) ($analysis->risk_level ?? 'Unknown'),
                ];
            })
            ->values();
        $dashboardStatsSeed = [
            'incident_total' => $teamIds->isNotEmpty()
                ? $incidents->whereIn('team_id', $teamIds->all())->count()
                : 0,
            'active_responses' => $teamIds->isNotEmpty()
                ? $incidents
                    ->whereIn('team_id', $teamIds->all())
                    ->whereIn('status', ['Responding', 'On Scene'])
                    ->count()
                : 0,
            'fsic_claims' => $stationOfficerIds->isNotEmpty()
                ? FsicApproval::whereIn('approved_by', $stationOfficerIds)->whereNotNull('date_issued')->count()
                : 0,
            'crew_online' => $stationperson->whereIn('status', ['available', 'dispatched', 'on-scene'])->count(),
            'station_id' => $stationId,
        ];

        return view('chief.dashboard', compact(
            'mapmarker',
            'incidents',
            'alerts',
            'stations',
            'provinces',
            'stationperson',
            'citizens',
            'barangayOfficers',
            'defaultTeam',
            'incidentTrendData',
            'stationReadiness',
            'recentAnalyses',
            'analysisTrendSeries',
            'dashboardStatsSeed'
        ));
    }

    // public function fsic(){
    //     $fsicRequests= FsicRequest::all();
    //     return view('chief.fsic', compact('fsicRequests'));
    // }
    //for reports

    public function reports()
    {
        $accountId = Auth::user()->user_id;
        $thisperson = StationOfficer::where('user_id',$accountId)->with(['station.address.barangay.city'])->first();
        $stationId = $thisperson->station_id;

        $mapmarker = Marker::with('stations')->get();
        $stationOfficerIds = StationOfficer::where('station_id', $stationId)
            ->pluck('station_officer_id')
            ->filter()
            ->values();
        $stationTeamIds = $this->getTeamIdsForStation($stationId);

        $incidents = Incident::with(['marker', 'team', 'reporter'])
            ->whereIn('team_id', $stationTeamIds)
            ->get();
        $alerts = Incident::with('marker')->get();
        $stations = Station::where('station_id', $stationId)->with(['marker', 'users', 'address.barangay.city'])->get();
        $provinces = Province::orderBy('province_name')->get();
        $stationperson = StationOfficer::where('station_id',$stationId)->with('station','station.users')->get();
        // Get all users with role 'chief'
        $chiefRole = Role::where('role', 'chief')->first();
        $chiefs = $chiefRole ? User::where('role_id', $chiefRole->role_id)->get() : collect();
        $reportPayload = $this->buildExecutiveReportPayload($stationOfficerIds->all(), $stationId);
        $stationProfile = $this->buildExecutiveStationProfile($thisperson);

        return view('chief.reports', compact('mapmarker', 'incidents', 'alerts', 'stations', 'chiefs', 'provinces','stationperson', 'reportPayload', 'stationProfile'));

    }

    public function mitigations()
    {
        try {
            $managedBarangays = $this->getManagedBarangaysForStationAdmin();
            $stationOfficer = $this->getAuthenticatedStationOfficer();
            $stationId = $stationOfficer?->station_id;
            $barangayIds = $managedBarangays->pluck('barangay_id')->map(fn ($id) => (int) $id)->all();
            $draftActionPlanChecklistId = request()->integer('draft_ai_action_plan_checklist_id');
            $filters = [
                'barangay_id' => request()->integer('barangay_id') ?: null,
                'category' => request()->string('category')->toString() ?: null,
                'priority_level' => request()->string('priority_level')->toString() ?: null,
                'status' => request()->string('status')->toString() ?: null,
                'responsible_team_id' => request()->integer('responsible_team_id') ?: null,
                'source_type' => request()->string('source_type')->toString() ?: null,
            ];

            try {
                $this->syncGeneratedMitigationDraftRecommendations($stationId, $barangayIds);
            } catch (\Throwable $e) {
                Log::warning('Generated mitigation draft sync failed while loading mitigation page.', [
                    'station_id' => $stationId,
                    'message' => $e->getMessage(),
                ]);
            }

            $mitigationBaseQuery = MitigationAction::query()
                ->select([
                    'mitigation_action_id',
                    'barangay_id',
                    'risk_area_id',
                    'barangay_prediction_checklist_id',
                    'ai_action_plan_checklist_id',
                    'responsible_team_id',
                    'responsible_user_id',
                    'title',
                    'category',
                    'priority_level',
                    'status',
                    'source_type',
                    'target_date',
                    'completed_at',
                    'description',
                    'remarks',
                    'implementation_notes',
                    'completion_notes',
                    'outcome_result',
                    'effectiveness',
                    'follow_up_required',
                    'follow_up_notes',
                    'created_by',
                    'approved_by',
                    'approved_at',
                    'reviewed_by',
                    'reviewed_at',
                    'created_at',
                ])
                ->with([
                    'barangay:barangay_id,barangay_name,city_id',
                    'barangay.city:city_id,city_name',
                    'attachments:mitigation_action_attachment_id,mitigation_action_id,file_path,file_name,attachment_type',
                    'riskArea:risk_area_id,barangay_hall_id,marker_id',
                    'riskArea.marker:marker_id,title',
                    'checklist:barangay_prediction_checklist_id,title',
                    'aiActionPlanChecklist:ai_action_plan_checklist_id,analysis_id,plan_title,plan_description,priority_level,source_title,source_description,risk_area_id,ai_payload',
                    'aiActionPlanChecklist.analysis:analysis_id,station_id,barangay_scope,scope_level,risk_level,confidence_score,predictions,contributing_factors,recommendations,created_at',
                    'aiActionPlanChecklist.suggestions:ai_recommendation_suggestion_id,ai_action_plan_checklist_id,recommendation_title,recommendation_description,steps,resources_needed,estimated_timeline',
                    'aiActionPlanChecklist.items:ai_action_plan_checklist_item_id,ai_action_plan_checklist_id,item_text,status,item_order',
                    'creator:user_id,first_name,middle_name,last_name',
                    'responsibleTeam:team_id',
                    'responsibleUser:user_id,first_name,middle_name,last_name',
                    'approver:user_id,first_name,middle_name,last_name',
                ])
                ->when(! empty($barangayIds), fn ($query) => $query->whereIn('barangay_id', $barangayIds));

            if (empty($barangayIds)) {
                $mitigationBaseQuery->whereRaw('1 = 0');
            }

            $summaryActions = (clone $mitigationBaseQuery)
                ->get()
                ->each(function (MitigationAction $action) {
                    $action->category = $this->normalizeMitigationCategory($action->category);
                    $action->source_type = $this->resolveMitigationSourceType($action->toArray());
                });

            $mitigationActions = (clone $mitigationBaseQuery)
                ->when($filters['barangay_id'], fn ($query, $value) => $query->where('barangay_id', $value))
                ->when($filters['category'], function ($query, $value) {
                    $query->whereIn('category', $this->mitigationCategoryFilterAliases($value));
                })
                ->when($filters['priority_level'], fn ($query, $value) => $query->where('priority_level', $value))
                ->when($filters['status'], fn ($query, $value) => $query->where('status', $value))
                ->when($filters['responsible_team_id'], fn ($query, $value) => $query->where('responsible_team_id', $value))
                ->when($filters['source_type'], function ($query, $value) {
                    match ($value) {
                        'ai_action_plan' => $query->whereNotNull('ai_action_plan_checklist_id'),
                        'prediction' => $query->whereNotNull('barangay_prediction_checklist_id')->whereNull('ai_action_plan_checklist_id'),
                        'hazard_report' => $query->whereNotNull('risk_area_id')->whereNull('barangay_prediction_checklist_id')->whereNull('ai_action_plan_checklist_id'),
                        'manual' => $query
                            ->whereNull('risk_area_id')
                            ->whereNull('barangay_prediction_checklist_id')
                            ->whereNull('ai_action_plan_checklist_id')
                            ->where(function ($sourceQuery) {
                                $sourceQuery->whereNull('source_type')->orWhere('source_type', 'manual');
                            }),
                        default => $query->where('source_type', $value),
                    };
                })
                ->orderByRaw("
                    CASE priority_level
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                        ELSE 5
                    END
                ")
                ->orderByRaw("
                    CASE status
                        WHEN 'draft' THEN 1
                        WHEN 'planned' THEN 2
                        WHEN 'approved' THEN 3
                        WHEN 'in_progress' THEN 4
                        WHEN 'blocked' THEN 5
                        WHEN 'completed' THEN 6
                        WHEN 'cancelled' THEN 7
                        ELSE 8
                    END
                ")
                ->orderByRaw("
                    CASE
                        WHEN target_date IS NULL THEN 2
                        WHEN target_date < CURRENT_DATE AND status NOT IN ('completed', 'cancelled') THEN 1
                        ELSE 3
                    END
                ")
                ->orderBy('target_date')
                ->orderByDesc('created_at')
                ->get()
                ->each(function (MitigationAction $action) {
                    $action->category = $this->normalizeMitigationCategory($action->category);
                    $action->source_type = $this->resolveMitigationSourceType($action->toArray());
                });

            $newAiDraftMitigations = $mitigationActions
                ->filter(function (MitigationAction $action) {
                    return $action->status === 'draft'
                        && $action->source_type === 'ai_action_plan';
                })
                ->sortByDesc('created_at')
                ->take(6)
                ->values();

            $summaryCounts = [
                'total' => $summaryActions->count(),
                'awaiting_approval' => $summaryActions->whereIn('status', ['draft', 'planned'])->count(),
                'in_progress' => $summaryActions->whereIn('status', ['approved', 'in_progress', 'blocked'])->count(),
                'overdue' => $summaryActions
                    ->filter(fn (MitigationAction $action) => $action->target_date && $action->target_date->lt(today()) && ! in_array($action->status, ['completed', 'cancelled'], true))
                    ->count(),
                'high_priority' => $summaryActions->whereIn('priority_level', ['critical', 'high'])->count(),
                'completed' => $summaryActions->where('status', 'completed')->count(),
            ];

            $riskAreas = RiskArea::query()
                ->select(['risk_area_id', 'barangay_hall_id', 'marker_id'])
                ->with([
                    'marker:marker_id,title',
                    'barangayHall:barangay_hall_id,barangay_id',
                    'barangayHall.barangay:barangay_id,barangay_name',
                ])
                ->when(! empty($barangayIds), function ($query) use ($barangayIds) {
                    $query->whereHas('barangayHall', fn ($hallQuery) => $hallQuery->whereIn('barangay_id', $barangayIds));
                })
                ->when(empty($barangayIds), fn ($query) => $query->whereRaw('1 = 0'))
                ->orderByDesc('created_at')
                ->get();

            $predictionChecklists = BarangayPredictionChecklist::query()
                ->select([
                    'barangay_prediction_checklist_id',
                    'barangay_id',
                    'title',
                    'priority_level',
                    'status',
                    'target_date',
                    'assigned_to',
                    'reviewed_by',
                ])
                ->with([
                    'assignee:user_id,first_name,middle_name,last_name',
                    'reviewer:user_id,first_name,middle_name,last_name',
                    'barangay:barangay_id,barangay_name',
                ])
                ->when(! empty($barangayIds), fn ($query) => $query->whereIn('barangay_id', $barangayIds))
                ->when(empty($barangayIds), fn ($query) => $query->whereRaw('1 = 0'))
                ->orderByRaw("
                    CASE priority_level
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                        ELSE 5
                    END
                ")
                ->orderBy('target_date')
                ->get();

            $aiActionPlanOptions = AiActionPlanChecklist::query()
                ->with([
                    'analysis:analysis_id,barangay_scope,scope_level,risk_level,created_at',
                    'suggestions:ai_recommendation_suggestion_id,ai_action_plan_checklist_id,recommendation_title,recommendation_description',
                    'items:ai_action_plan_checklist_item_id,ai_action_plan_checklist_id,item_text,status,item_order',
                ])
                ->when(! empty($barangayIds), fn ($query) => $query->whereIn('barangay_id', $barangayIds))
                ->when(empty($barangayIds), fn ($query) => $query->whereRaw('1 = 0'))
                ->orderByDesc('created_at')
                ->get([
                    'ai_action_plan_checklist_id',
                    'analysis_id',
                    'barangay_id',
                    'risk_area_id',
                    'plan_title',
                    'plan_description',
                    'priority_level',
                    'status',
                    'source_title',
                    'source_description',
                    'ai_payload',
                    'created_at',
                ]);

            $generatedMitigationDrafts = AiActionPlanChecklist::query()
                ->with([
                    'barangay:barangay_id,barangay_name',
                    'riskArea:risk_area_id,marker_id',
                    'riskArea.marker:marker_id,title',
                    'analysis:analysis_id,risk_level,barangay_scope,created_at',
                    'suggestions:ai_recommendation_suggestion_id,ai_action_plan_checklist_id,recommendation_title,recommendation_description',
                    'mitigationActions:mitigation_action_id,ai_action_plan_checklist_id,status',
                ])
                ->where(function ($query) use ($barangayIds, $stationId) {
                    if (! empty($barangayIds)) {
                        $query->whereIn('barangay_id', $barangayIds);

                        if ($stationId) {
                            $query->orWhere(function ($fallbackQuery) use ($stationId) {
                                $fallbackQuery
                                    ->where('station_id', $stationId)
                                    ->whereNull('barangay_id');
                            });
                        }

                        return;
                    }

                    if ($stationId) {
                        $query->where('station_id', $stationId);
                        return;
                    }

                    $query->whereRaw('1 = 0');
                })
                ->where('status', 'pending')
                ->where(function ($query) {
                    $query->whereNotNull('source_signature')
                        ->orWhere('source_type', 'action_plan');
                })
                ->whereDoesntHave('mitigationActions', function ($query) {
                    $query->whereNotIn('status', ['cancelled']);
                })
                ->orderByRaw("
                    CASE priority_level
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                        ELSE 5
                    END
                ")
                ->orderByDesc('created_at')
                ->get();

            $mitigationDraft = $draftActionPlanChecklistId
                ? $this->buildMitigationDraftFromActionPlanChecklist($draftActionPlanChecklistId, $barangayIds)
                : null;

            $stationTeams = $this->getTeamsForStation($stationId)
                ->orderBy('team_id')
                ->get(['team_id']);

            $stationUsers = StationOfficer::query()
                ->with(['user:user_id,first_name,middle_name,last_name'])
                ->when($stationId, fn ($query) => $query->where('station_id', $stationId))
                ->when(! $stationId, fn ($query) => $query->whereRaw('1 = 0'))
                ->orderBy('rank')
                ->get()
                ->pluck('user')
                ->filter()
                ->unique('user_id')
                ->values();

            return view('chief.mitigations', [
                'managedBarangays' => $managedBarangays,
                'mitigationActions' => $mitigationActions,
                'newAiDraftMitigations' => $newAiDraftMitigations,
                'mitigationSummaryCounts' => $summaryCounts,
                'riskAreas' => $riskAreas,
                'predictionChecklists' => $predictionChecklists,
                'aiActionPlanOptions' => $aiActionPlanOptions,
                'generatedMitigationDrafts' => $generatedMitigationDrafts,
                'mitigationDraft' => $mitigationDraft,
                'autoOpenCreateModal' => (bool) $mitigationDraft,
                'mitigationCategoryOptions' => $this->mitigationCategoryOptions(),
                'mitigationPriorityOptions' => $this->mitigationPriorityOptions(),
                'mitigationStatusOptions' => $this->mitigationStatusOptions(),
                'mitigationStatusLabels' => $this->mitigationStatusLabels(),
                'mitigationEffectivenessOptions' => $this->mitigationEffectivenessOptions(),
                'mitigationSourceOptions' => $this->mitigationSourceOptions(),
                'stationTeams' => $stationTeams,
                'stationUsers' => $stationUsers,
                'filters' => $filters,
            ]);
        } catch (\Throwable $e) {
            Log::error('Mitigation page load failed.', [
                'message' => $e->getMessage(),
                'user_id' => Auth::user()?->user_id,
            ]);

            return view('chief.mitigations', [
                'managedBarangays' => collect(),
                'mitigationActions' => collect(),
                'newAiDraftMitigations' => collect(),
                'mitigationSummaryCounts' => [
                    'total' => 0,
                    'awaiting_approval' => 0,
                    'in_progress' => 0,
                    'overdue' => 0,
                    'high_priority' => 0,
                    'completed' => 0,
                ],
                'riskAreas' => collect(),
                'predictionChecklists' => collect(),
                'aiActionPlanOptions' => collect(),
                'generatedMitigationDrafts' => collect(),
                'mitigationDraft' => null,
                'autoOpenCreateModal' => false,
                'mitigationCategoryOptions' => $this->mitigationCategoryOptions(),
                'mitigationPriorityOptions' => $this->mitigationPriorityOptions(),
                'mitigationStatusOptions' => $this->mitigationStatusOptions(),
                'mitigationStatusLabels' => $this->mitigationStatusLabels(),
                'mitigationEffectivenessOptions' => $this->mitigationEffectivenessOptions(),
                'mitigationSourceOptions' => $this->mitigationSourceOptions(),
                'stationTeams' => collect(),
                'stationUsers' => collect(),
                'filters' => [],
            ])->withErrors([
                'mitigations' => 'Mitigation data could not be loaded right now. The page was opened in safe mode instead of returning a server error.',
            ]);
        }
    }

    public function approveGeneratedMitigationDraft(Request $request, int $checklistId)
    {
        $allowedBarangayIds = $this->getManagedBarangaysForStationAdmin()
            ->pluck('barangay_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $checklist = $this->resolveMitigationDraftChecklist($checklistId, $allowedBarangayIds);
        $draft = $this->buildMitigationDraftFromActionPlanChecklist($checklistId, $allowedBarangayIds);

        if (! $draft) {
            return redirect()->route('station-admin.mitigations')
                ->with('error', 'Unable to load the mitigation draft.');
        }

        $existingAction = MitigationAction::query()
            ->where('ai_action_plan_checklist_id', $checklist->ai_action_plan_checklist_id)
            ->first();

        if ($existingAction) {
            $this->markGeneratedMitigationDraftApproved($checklist, Auth::user()?->user_id, (int) $existingAction->mitigation_action_id);

            return redirect()->route('station-admin.mitigations')
                ->with('success', 'This AI mitigation draft was already converted into an official mitigation action.');
        }

        DB::transaction(function () use ($draft, $checklist, $request) {
            $action = MitigationAction::create([
                'barangay_id' => $draft['barangay_id'],
                'risk_area_id' => $draft['risk_area_id'] ?? null,
                'barangay_prediction_checklist_id' => $draft['barangay_prediction_checklist_id'] ?? null,
                'ai_action_plan_checklist_id' => $draft['ai_action_plan_checklist_id'] ?? null,
                'responsible_team_id' => $draft['responsible_team_id'] ?? null,
                'responsible_user_id' => $draft['responsible_user_id'] ?? null,
                'title' => $draft['title'],
                'category' => $this->normalizeMitigationCategory($draft['category'] ?? null),
                'priority_level' => $draft['priority_level'],
                'status' => 'planned',
                'source_type' => $this->resolveMitigationSourceType($draft),
                'target_date' => $draft['target_date'] ?? null,
                'completed_at' => null,
                'description' => $draft['description'],
                'remarks' => $draft['remarks'] ?? null,
                'implementation_notes' => $draft['implementation_notes'] ?? null,
                'completion_notes' => $draft['completion_notes'] ?? null,
                'outcome_result' => $draft['outcome_result'] ?? null,
                'effectiveness' => $draft['effectiveness'] ?? 'pending_evaluation',
                'follow_up_required' => (bool) ($draft['follow_up_required'] ?? false),
                'follow_up_notes' => $draft['follow_up_notes'] ?? null,
                'created_by' => Auth::user()?->user_id,
            ]);

            $this->markGeneratedMitigationDraftApproved($checklist, Auth::user()?->user_id, (int) $action->mitigation_action_id);
            UserActivityLogger::log('approved generated mitigation draft', Auth::user()?->user_id, $request);
        });

        return redirect()->route('station-admin.mitigations')
            ->with('success', 'AI mitigation draft approved and saved as an official mitigation action.');
    }

    public function rejectGeneratedMitigationDraft(Request $request, int $checklistId)
    {
        $allowedBarangayIds = $this->getManagedBarangaysForStationAdmin()
            ->pluck('barangay_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $checklist = $this->resolveMitigationDraftChecklist($checklistId, $allowedBarangayIds);

        $this->markGeneratedMitigationDraftRejected(
            $checklist,
            trim((string) $request->input('notes', '')) ?: null
        );
        UserActivityLogger::log('rejected generated mitigation draft', Auth::user()?->user_id, $request);

        return redirect()->route('station-admin.mitigations')
            ->with('success', 'AI mitigation draft rejected.');
    }

    public function storeMitigation(Request $request)
    {
        $validated = $request->validate($this->mitigationValidationRules());
        $allowedBarangayIds = $this->getManagedBarangaysForStationAdmin()
            ->pluck('barangay_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $stationId = $this->getAuthenticatedStationOfficer()?->station_id;
        $linkedIds = $this->validateMitigationLinksForStation($validated, $allowedBarangayIds);
        $assignmentIds = $this->validateMitigationAssignments($validated, $stationId);
        $status = $validated['status'];
        $sourceType = $this->resolveMitigationSourceType(array_merge($validated, $linkedIds));

        DB::transaction(function () use ($validated, $linkedIds, $assignmentIds, $status, $sourceType, $request) {
            $action = MitigationAction::create([
                'barangay_id' => $linkedIds['barangay_id'],
                'risk_area_id' => $linkedIds['risk_area_id'],
                'barangay_prediction_checklist_id' => $linkedIds['barangay_prediction_checklist_id'],
                'ai_action_plan_checklist_id' => $linkedIds['ai_action_plan_checklist_id'],
                'responsible_team_id' => $assignmentIds['responsible_team_id'],
                'responsible_user_id' => $assignmentIds['responsible_user_id'],
                'title' => $validated['title'],
                'category' => $this->normalizeMitigationCategory($validated['category']),
                'priority_level' => $validated['priority_level'],
                'status' => $status,
                'source_type' => $sourceType,
                'target_date' => $validated['target_date'] ?? null,
                'completed_at' => $status === 'completed' ? now() : null,
                'description' => $validated['description'],
                'remarks' => $validated['remarks'] ?? null,
                'implementation_notes' => $validated['implementation_notes'] ?? null,
                'completion_notes' => $validated['completion_notes'] ?? null,
                'outcome_result' => $validated['outcome_result'] ?? null,
                'effectiveness' => $validated['effectiveness'] ?? 'pending_evaluation',
                'follow_up_required' => (bool) ($validated['follow_up_required'] ?? false),
                'follow_up_notes' => $validated['follow_up_notes'] ?? null,
                'created_by' => Auth::user()?->user_id,
                'approved_by' => in_array($status, ['approved', 'in_progress', 'completed'], true) ? Auth::user()?->user_id : null,
                'approved_at' => in_array($status, ['approved', 'in_progress', 'completed'], true) ? now() : null,
            ]);

            $this->storeMitigationAttachments($action, $request);
            $this->syncLinkedActionPlanAfterMitigation($action);
            UserActivityLogger::log('created station mitigation action', Auth::user()?->user_id, $request);
        });

        return redirect()->route('station-admin.mitigations')
            ->with('success', 'Mitigation action created successfully.');
    }

    public function updateMitigation(Request $request, int $mitigationActionId)
    {
        $validated = $request->validate($this->mitigationValidationRules());
        $allowedBarangayIds = $this->getManagedBarangaysForStationAdmin()
            ->pluck('barangay_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $stationId = $this->getAuthenticatedStationOfficer()?->station_id;
        $action = $this->resolveManagedMitigationAction($mitigationActionId, $allowedBarangayIds);
        $linkedIds = $this->validateMitigationLinksForStation($validated, $allowedBarangayIds);
        $assignmentIds = $this->validateMitigationAssignments($validated, $stationId);
        $status = $validated['status'];
        $sourceType = $this->resolveMitigationSourceType(array_merge($validated, $linkedIds));

        DB::transaction(function () use ($validated, $linkedIds, $assignmentIds, $status, $sourceType, $action, $request) {
            $approvalTriggered = in_array($status, ['approved', 'in_progress', 'completed'], true)
                && ! $action->approved_at;

            $action->update([
                'barangay_id' => $linkedIds['barangay_id'],
                'risk_area_id' => $linkedIds['risk_area_id'],
                'barangay_prediction_checklist_id' => $linkedIds['barangay_prediction_checklist_id'],
                'ai_action_plan_checklist_id' => $linkedIds['ai_action_plan_checklist_id'],
                'responsible_team_id' => $assignmentIds['responsible_team_id'],
                'responsible_user_id' => $assignmentIds['responsible_user_id'],
                'title' => $validated['title'],
                'category' => $this->normalizeMitigationCategory($validated['category']),
                'priority_level' => $validated['priority_level'],
                'status' => $status,
                'source_type' => $sourceType,
                'target_date' => $validated['target_date'] ?? null,
                'completed_at' => $status === 'completed'
                    ? ($action->completed_at ?? now())
                    : null,
                'description' => $validated['description'],
                'remarks' => $validated['remarks'] ?? null,
                'implementation_notes' => $validated['implementation_notes'] ?? null,
                'completion_notes' => $validated['completion_notes'] ?? null,
                'outcome_result' => $validated['outcome_result'] ?? null,
                'effectiveness' => $validated['effectiveness'] ?? 'pending_evaluation',
                'follow_up_required' => (bool) ($validated['follow_up_required'] ?? false),
                'follow_up_notes' => $validated['follow_up_notes'] ?? null,
                'approved_by' => $approvalTriggered ? Auth::user()?->user_id : $action->approved_by,
                'approved_at' => $approvalTriggered ? now() : $action->approved_at,
                'reviewed_by' => Auth::user()?->user_id,
                'reviewed_at' => now(),
            ]);

            $this->storeMitigationAttachments($action, $request);
            $this->syncLinkedActionPlanAfterMitigation($action->fresh());
            UserActivityLogger::log('updated station mitigation action', Auth::user()?->user_id, $request);
        });

        return redirect()->route('station-admin.mitigations')
            ->with('success', 'Mitigation action updated successfully.');
    }

    public function destroyMitigation(Request $request, int $mitigationActionId)
    {
        $allowedBarangayIds = $this->getManagedBarangaysForStationAdmin()
            ->pluck('barangay_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $action = $this->resolveManagedMitigationAction($mitigationActionId, $allowedBarangayIds);

        DB::transaction(function () use ($action, $request) {
            foreach ($action->attachments as $attachment) {
                if (! empty($attachment->file_path)) {
                    MediaPath::delete($attachment->file_path);
                }
            }

            $action->delete();
            UserActivityLogger::log('deleted station mitigation action', Auth::user()?->user_id, $request);
        });

        return redirect()->route('station-admin.mitigations')
            ->with('success', 'Mitigation action deleted successfully.');
    }


    public function alerts(){
        $incidents = Incident::all();
        return view('chief.alerts',compact('incidents'));
    }
    public function maps()
    {   
        $mapmarker = Marker::all();
        return view('chief.incidentmap', compact('mapmarker'));
    }

    /**
     * Get cities by province (AJAX)
     */
    public function getCities($provinceId)
    {
        $cities = City::where('province_id', $provinceId)
            ->orderBy('city_name')
            ->get();
        
        return response()->json($cities);
    }

    private function buildExecutiveReportPayload(array $stationOfficerIds, int $stationId): array
    {
        $spotReports = SpotInvestigationReport::with(['incident.marker', 'investigator.user', 'progressInvestigation'])
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->get()
            ->map(function ($report) use ($stationId) {
                return [
                    'id' => $report->spot_investigation_report_id,
                    'report_kind' => 'spot',
                    'report_label' => 'Spot Investigation Report',
                    'station_id' => $stationId,
                    'station_name' => $report->investigator?->station?->station_name,
                    'incident_id' => $report->incident_id,
                    'incident_title' => $report->incident?->marker?->title ?? ('Incident #' . $report->incident_id),
                    'incident_status' => $report->incident?->status,
                    'investigator_name' => trim(($report->investigator?->user?->first_name ?? '') . ' ' . ($report->investigator?->user?->last_name ?? '')),
                    'owner' => $report->owner,
                    'casualty' => trim(collect([
                        (int) ($report->dead ?? 0) > 0 ? ((int) $report->dead . ' dead') : null,
                        (int) ($report->injured ?? 0) > 0 ? ((int) $report->injured . ' injured') : null,
                    ])->filter()->implode(', ')) ?: 'none',
                    'fire_started' => optional($report->fire_started)->format('Y-m-d H:i:s'),
                    'fire_out' => optional($report->fire_out)->format('Y-m-d H:i:s'),
                    'estimated_damage' => (float) ($report->estimated_damage ?? 0),
                    'occupancy' => $report->occupancy,
                    'estimated_area' => (float) ($report->estimated_area ?? 0),
                    'status' => $report->progressInvestigation ? 'progressed' : 'filed',
                    'created_at' => optional($report->created_at)->format('Y-m-d H:i:s'),
                    'created_at_iso' => optional($report->created_at)?->toIso8601String(),
                ];
            })
            ->values();

        $progressReports = ProgressInvestigationReport::with(['spotInvestigation.incident.marker', 'investigator.user', 'fireOrigin', 'finalInvestigation'])
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->get()
            ->map(function ($report) use ($stationId) {
                return [
                    'id' => $report->progress_investigation_report_id,
                    'report_kind' => 'progress',
                    'report_label' => 'Progress Investigation Report',
                    'station_id' => $stationId,
                    'station_name' => $report->investigator?->station?->station_name,
                    'incident_id' => $report->spotInvestigation?->incident_id,
                    'incident_title' => $report->spotInvestigation?->incident?->marker?->title ?? ('Incident #' . ($report->spotInvestigation?->incident_id ?? '')),
                    'incident_status' => $report->spotInvestigation?->incident?->status,
                    'investigator_name' => trim(($report->investigator?->user?->first_name ?? '') . ' ' . ($report->investigator?->user?->last_name ?? '')),
                    'spot_investigation_id' => $report->spot_investigation_id,
                    'fire_origin' => $report->fireOrigin?->origin,
                    'responsible_arson' => $report->responsible_arson,
                    'fire_cause' => $report->fire_cause,
                    'status' => $report->finalInvestigation ? 'finalized' : 'conducting',
                    'created_at' => optional($report->created_at)->format('Y-m-d H:i:s'),
                    'created_at_iso' => optional($report->created_at)?->toIso8601String(),
                ];
            })
            ->values();

        $finalReports = FinalInvestigationReport::with(['progressInvestigation.spotInvestigation.incident.marker', 'progressInvestigation.investigator.user'])
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->get()
            ->map(function ($report) use ($stationId) {
                $investigator = $report->progressInvestigation?->investigator?->user;
                return [
                    'id' => $report->final_investigation_report_id,
                    'report_kind' => 'final',
                    'report_label' => 'Final Investigation Report',
                    'station_id' => $stationId,
                    'station_name' => $report->progressInvestigation?->investigator?->station?->station_name,
                    'incident_id' => $report->progressInvestigation?->spotInvestigation?->incident_id,
                    'incident_title' => $report->progressInvestigation?->spotInvestigation?->incident?->marker?->title ?? ('Incident #' . ($report->progressInvestigation?->spotInvestigation?->incident_id ?? '')),
                    'incident_status' => $report->progressInvestigation?->spotInvestigation?->incident?->status,
                    'investigator_name' => trim(($investigator?->first_name ?? '') . ' ' . ($investigator?->last_name ?? '')),
                    'progress_investigation_id' => $report->progress_investigation_id,
                    'total_damage' => (float) ($report->total_damage ?? 0),
                    'exact_address' => $report->exact_address,
                    'status' => 'completed',
                    'created_at' => optional($report->created_at)->format('Y-m-d H:i:s'),
                    'created_at_iso' => optional($report->created_at)?->toIso8601String(),
                ];
            })
            ->values();

        return [
            'spot' => $spotReports,
            'progress' => $progressReports,
            'final' => $finalReports,
        ];
    }

    private function buildExecutiveStationProfile(?StationOfficer $stationOfficer): array
    {
        $station = $stationOfficer?->station;
        $address = $station?->address;
        $barangay = $address?->barangay;
        $city = $barangay?->city;

        return [
            'station_name' => $station?->station_name,
            'station_code' => $station?->station_code,
            'contact_number' => $station?->contact_number,
            'email' => $station?->email,
            'address' => collect([
                $address?->street,
                $barangay?->barangay_name,
                $city?->city_name,
            ])->filter()->implode(', '),
            'chief_name' => trim((Auth::user()->first_name ?? '') . ' ' . (Auth::user()->last_name ?? '')),
            'personnel_count' => StationOfficer::where('station_id', $stationOfficer?->station_id)->count(),
        ];
    }

    /**
     * Get barangays by city (AJAX)
     */
    public function getBarangays($cityId)
    {
        $barangays = Barangay::where('city_id', $cityId)
            ->orderBy('barangay_name')
            ->get();
        
        return response()->json($barangays);
    }
public function storeFullAddress(Request $request)
{
    try {
        $address = FullAddress::create([
            'street' => $request->street,
            'barangay_id' => $request->barangay_id,
          
        ]);
        
        return response()->json([
            'success' => true,
            'address_id' => $address->address_id,
            'message' => 'Address created successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error creating address: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Store marker with station and address
     */
    public function storeMarker(Request $request)
    {
      try {
        DB::beginTransaction();

        // Create the marker
        $marker = Marker::create([
            'title' => $request->title,
            'description' => $request->description,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'category' => $request->category,
            'status' => 'active',
        ]);

        // If station category and station data exists
        if ($request->has('station_data')) {
            $stationData = $request->station_data;
            
            // Create station with the address_id from the request
            $station = Station::create([
                'station_name' => $stationData['station_name'],
                'station_code' => $stationData['station_code'],
                'contact_number' => $stationData['contact_number'],
                'email' => $stationData['email'],
                'address_id' => $stationData['address_id'] ?? null, // Use the address_id from the request
                'marker_id' => $marker->marker_id,
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Marker and station saved successfully',
            'marker' => $marker
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
    }

    public function createWalkInCitizen(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
        ]);

        $user = User::create([
            'account_code' => $this->generateCitizenAccountCode(),
            'first_name' => trim($validated['first_name']),
            'middle_name' => '',
            'last_name' => trim($validated['last_name']),
            'role_id' => 5,
            'address_id' => null,
            'contact_number' => $validated['contact_number'],
            'email' => null,
            'password' => null,
            'phone_verified_at' => now(),
            'is_verified' => true,
        ]);
        UserActivityLogger::log('created walk-in user', Auth::user()?->user_id, $request);

        return response()->json([
            'success' => true,
            'message' => 'Walk-in citizen saved successfully.',
            'user_id' => $user->user_id,
            'citizen' => [
                'user_id' => $user->user_id,
                'account_code' => $user->account_code,
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'contact_number' => $user->contact_number,
            ],
        ]);
    }

    public function dashboardStats()
    {
        $stationOfficer = $this->getAuthenticatedStationOfficer();
        $stationId = $stationOfficer?->station_id;
        $teamIds = $this->getTeamIdsForStation($stationId);
        $stationOfficerIds = StationOfficer::where('station_id', $stationId)
            ->pluck('station_officer_id')
            ->filter()
            ->values();

        $incidentTotal = $teamIds->isNotEmpty()
            ? Incident::whereIn('team_id', $teamIds)->count()
            : 0;

        $activeResponses = $teamIds->isNotEmpty()
            ? Incident::whereIn('team_id', $teamIds)
                ->whereIn('status', ['Responding', 'On Scene'])
                ->count()
            : 0;

        $fsicClaims = $stationOfficerIds->isNotEmpty()
            ? FsicApproval::whereIn('approved_by', $stationOfficerIds)
                ->whereNotNull('date_issued')
                ->count()
            : 0;

        $crewOnline = $stationId
            ? StationOfficer::where('station_id', $stationId)
                ->whereIn('status', ['available', 'dispatched', 'on-scene'])
                ->count()
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'incident_total' => $incidentTotal,
                'active_responses' => $activeResponses,
                'fsic_claims' => $fsicClaims,
                'crew_online' => $crewOnline,
                'station_id' => $stationId,
            ],
        ]);
    }

    public function incidentMapData()
    {
        $stationOfficer = $this->getAuthenticatedStationOfficer();
        $stationId = $stationOfficer?->station_id;

        if (! $stationId) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        try {
            $incidents = $this->getChiefIncidentMapCollection($stationId)
                ->map(fn (Incident $incident) => $this->serializeChiefIncidentMapItem($incident))
                ->values();
        } catch (\Throwable $error) {
            report($error);

            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Incident map is temporarily unavailable.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $incidents,
        ]);
    }

    public function incidentMapChanges(Request $request)
    {
        $validated = $request->validate([
            'cursor_updated_at' => ['nullable', 'date'],
            'cursor_incident_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $stationOfficer = $this->getAuthenticatedStationOfficer();
        $stationId = $stationOfficer?->station_id;

        if (! $stationId) {
            return response()->json([
                'success' => true,
                'data' => [],
                'meta' => [
                    'cursor' => [
                        'updated_at' => null,
                        'incident_id' => 0,
                    ],
                ],
            ]);
        }

        $cursorUpdatedAt = ! empty($validated['cursor_updated_at'])
            ? Carbon::parse($validated['cursor_updated_at'])
            : null;
        $cursorIncidentId = (int) ($validated['cursor_incident_id'] ?? 0);
        try {
            $incidents = $this->getChiefIncidentMapCollection($stationId);
        } catch (\Throwable $error) {
            report($error);

            return response()->json([
                'success' => false,
                'data' => [],
                'meta' => [
                    'cursor' => [
                        'updated_at' => null,
                        'incident_id' => 0,
                    ],
                ],
                'message' => 'Incident map updates are temporarily unavailable.',
            ]);
        }

        $changes = $incidents
            ->filter(function (Incident $incident) use ($cursorUpdatedAt, $cursorIncidentId) {
                $effectiveUpdatedAt = $this->resolveChiefIncidentMapUpdatedAt($incident);
                $incidentId = (int) $incident->incident_id;

                if (! $cursorUpdatedAt) {
                    return true;
                }

                if ($effectiveUpdatedAt && $effectiveUpdatedAt->gt($cursorUpdatedAt)) {
                    return true;
                }

                if ($effectiveUpdatedAt && $effectiveUpdatedAt->equalTo($cursorUpdatedAt) && $incidentId > $cursorIncidentId) {
                    return true;
                }

                return $incidentId > $cursorIncidentId;
            })
            ->sort(function (Incident $left, Incident $right) {
                $leftUpdatedAt = $this->resolveChiefIncidentMapUpdatedAt($left);
                $rightUpdatedAt = $this->resolveChiefIncidentMapUpdatedAt($right);
                $leftTimestamp = $leftUpdatedAt?->getTimestamp() ?? 0;
                $rightTimestamp = $rightUpdatedAt?->getTimestamp() ?? 0;

                if ($leftTimestamp === $rightTimestamp) {
                    return (int) $left->incident_id <=> (int) $right->incident_id;
                }

                return $leftTimestamp <=> $rightTimestamp;
            })
            ->values()
            ->map(fn (Incident $incident) => $this->serializeChiefIncidentMapItem($incident));

        return response()->json([
            'success' => true,
            'data' => $changes,
            'meta' => [
                'cursor' => $this->buildChiefIncidentMapCursor($incidents),
            ],
        ]);
    }

    protected function getChiefIncidentMapCollection(?int $stationId)
    {
        if (! $stationId) {
            return collect();
        }

        $teamIds = $this->getTeamIdsForStation($stationId)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();
        $stationMarkers = Cache::remember('station-admin:incident-map:station-markers:v1', now()->addSeconds(60), function () {
            return Station::query()
                ->select(['station_id', 'marker_id'])
                ->with(['marker:marker_id,lat,lng'])
                ->get()
                ->filter(fn (Station $station) => $station->marker && $station->marker->lat !== null && $station->marker->lng !== null)
                ->values();
        });

        return Incident::query()
            ->select([
                'incident_id',
                'marker_id',
                'team_id',
                'status',
                'updated_at',
            ])
            ->with([
                'marker:marker_id,lat,lng,category,updated_at',
                'incidentReport:incident_id,severity',
            ])
            ->whereHas('marker', function ($query) {
                $query->whereNotNull('lat')
                    ->whereNotNull('lng');
            })
            ->limit(500)
            ->get()
            ->filter(function (Incident $incident) use ($stationId, $teamIds, $stationMarkers) {
                if ($teamIds->isNotEmpty() && $incident->team_id && $teamIds->contains((int) $incident->team_id)) {
                    return true;
                }

                $nearestStationId = $this->resolveNearestStationIdForIncident($incident, $stationMarkers);

                return $nearestStationId !== null && $nearestStationId === (int) $stationId;
            })
            ->values();
    }

    protected function serializeChiefIncidentMapItem(Incident $incident): array
    {
        $effectiveUpdatedAt = $this->resolveChiefIncidentMapUpdatedAt($incident);

        return [
            'id' => $incident->incident_id,
            'latitude' => $incident->marker ? (float) $incident->marker->lat : null,
            'longitude' => $incident->marker ? (float) $incident->marker->lng : null,
            'status' => $incident->status,
            'incident_type' => $this->resolveIncidentMapType($incident),
            'severity' => $incident->incidentReport?->severity,
            'updated_at' => optional($effectiveUpdatedAt)->toIso8601String(),
        ];
    }

    protected function resolveChiefIncidentMapUpdatedAt(Incident $incident): ?Carbon
    {
        $incidentUpdatedAt = $incident->updated_at instanceof Carbon
            ? $incident->updated_at
            : ($incident->updated_at ? Carbon::parse($incident->updated_at) : null);
        $markerUpdatedAt = $incident->marker?->updated_at instanceof Carbon
            ? $incident->marker->updated_at
            : ($incident->marker?->updated_at ? Carbon::parse($incident->marker->updated_at) : null);

        if (! $incidentUpdatedAt) {
            return $markerUpdatedAt;
        }

        if (! $markerUpdatedAt) {
            return $incidentUpdatedAt;
        }

        return $markerUpdatedAt->greaterThan($incidentUpdatedAt) ? $markerUpdatedAt : $incidentUpdatedAt;
    }

    protected function buildChiefIncidentMapCursor($incidents): array
    {
        $latestIncident = $incidents
            ->sort(function (Incident $left, Incident $right) {
                $leftUpdatedAt = $this->resolveChiefIncidentMapUpdatedAt($left);
                $rightUpdatedAt = $this->resolveChiefIncidentMapUpdatedAt($right);
                $leftTimestamp = $leftUpdatedAt?->getTimestamp() ?? 0;
                $rightTimestamp = $rightUpdatedAt?->getTimestamp() ?? 0;

                if ($leftTimestamp === $rightTimestamp) {
                    return (int) $right->incident_id <=> (int) $left->incident_id;
                }

                return $rightTimestamp <=> $leftTimestamp;
            })
            ->first();

        return [
            'updated_at' => optional($latestIncident ? $this->resolveChiefIncidentMapUpdatedAt($latestIncident) : null)->toIso8601String(),
            'incident_id' => $latestIncident ? (int) $latestIncident->incident_id : 0,
        ];
    }

    protected function resolveIncidentMapType(Incident $incident): string
    {
        $category = strtolower((string) ($incident->marker?->category ?? 'fire'));

        return match ($category) {
            'fire' => 'Fire',
            'station' => 'Station',
            'hydrant' => 'Hydrant',
            'school' => 'School',
            default => Str::title(str_replace(['-', '_'], ' ', $category)),
        };
    }

    protected function resolveNearestStationIdForIncident(Incident $incident, $stationMarkers): ?int
    {
        $incidentLat = $incident->marker?->lat;
        $incidentLng = $incident->marker?->lng;

        if ($incidentLat === null || $incidentLng === null) {
            return null;
        }

        $nearestStationId = null;
        $nearestDistanceMeters = null;

        foreach ($stationMarkers as $station) {
            $stationLat = $station->marker?->lat;
            $stationLng = $station->marker?->lng;

            if ($stationLat === null || $stationLng === null) {
                continue;
            }

            $distanceMeters = $this->calculateDistanceMeters(
                (float) $incidentLat,
                (float) $incidentLng,
                (float) $stationLat,
                (float) $stationLng
            );

            if ($nearestDistanceMeters === null || $distanceMeters < $nearestDistanceMeters) {
                $nearestDistanceMeters = $distanceMeters;
                $nearestStationId = (int) $station->station_id;
            }
        }

        return $nearestStationId;
    }

    protected function calculateDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusMeters = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusMeters * $c;
    }
}
