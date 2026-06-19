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
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\UserActivityLogger;
use Carbon\Carbon;

class StationAdminController extends Controller
{
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
        $stationOfficer = StationOfficer::query()
            ->with(['station.address.barangay.city'])
            ->where('user_id', Auth::user()->user_id)
            ->first();

        $cityId = $stationOfficer?->station?->address?->barangay?->city?->city_id;

        return Barangay::query()
            ->when($cityId, fn ($query) => $query->where('city_id', $cityId))
            ->with('city')
            ->orderBy('barangay_name')
            ->get();
    }

    protected function mitigationCategoryOptions(): array
    {
        return [
            'awareness' => 'Awareness Campaign',
            'inspection' => 'Inspection / Patrol',
            'infrastructure' => 'Infrastructure Upgrade',
            'equipment' => 'Equipment / Supplies',
            'policy' => 'Policy / Ordinance',
            'training' => 'Training / Drill',
            'emergency_access' => 'Emergency Access',
            'hydrant_water' => 'Hydrant / Water Source',
            'electrical' => 'Electrical Safety',
            'housekeeping' => 'Housekeeping / Waste',
            'community_response' => 'Community Response',
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
            'planned' => 'Planned',
            'in_progress' => 'In Progress',
            'blocked' => 'Blocked',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    protected function mitigationValidationRules(): array
    {
        return [
            'barangay_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys($this->mitigationCategoryOptions()))],
            'priority_level' => ['required', Rule::in(array_keys($this->mitigationPriorityOptions()))],
            'status' => ['required', Rule::in(array_keys($this->mitigationStatusOptions()))],
            'description' => ['required', 'string', 'max:3000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'target_date' => ['nullable', 'date'],
            'risk_area_id' => ['nullable', 'integer'],
            'barangay_prediction_checklist_id' => ['nullable', 'integer'],
            'ai_action_plan_checklist_id' => ['nullable', 'integer'],
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

    protected function resolveManagedMitigationAction(int $mitigationActionId, array $allowedBarangayIds): MitigationAction
    {
        return MitigationAction::query()
            ->with(['barangay.city', 'attachments', 'riskArea.marker', 'checklist', 'aiActionPlanChecklist', 'creator'])
            ->whereIn('barangay_id', $allowedBarangayIds)
            ->findOrFail($mitigationActionId);
    }

    protected function storeMitigationAttachments(MitigationAction $action, Request $request): void
    {
        foreach ($request->file('attachments', []) as $file) {
            if (! $file) {
                continue;
            }

            $storedPath = $file->store('mitigation-actions', 'public');

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
                ->orderBy('event_date')
                ->orderBy('start_time')
                ->get()
                ->map(function (EventActivity $event) {
                    return [
                        'event_id' => $event->event_id,
                        'event' => $event->event,
                        'event_date' => optional($event->event_date)->toDateString(),
                        'start_time' => $event->start_time,
                        'end_time' => $event->end_time,
                        'schedule' => $event->schedule,
                        'activity_type' => $event->activity_type,
                        'status' => $event->status,
                        'barangay' => $event->barangay,
                        'notes' => $event->notes,
                        'station_id' => $event->station_id,
                    ];
                })
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

        $validated = $request->validate([
            'event' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'activity_type' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'barangay' => ['nullable', 'string', 'max:255', Rule::in($validBarangayNames)],
            'notes' => ['nullable', 'string'],
        ]);

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

        $hasConflict = EventActivity::query()
            ->where('station_id', $stationId)
            ->whereDate('event_date', $validated['event_date'])
            ->where('start_time', '<=', $validated['end_time'])
            ->where('end_time', '>=', $validated['start_time'])
            ->exists();

        if ($hasConflict) {
            return response()->json([
                'success' => false,
                'message' => 'The selected time range is already occupied for this station.'
            ], 422);
        }

        $scheduleLabel = Carbon::createFromFormat('H:i', $validated['start_time'])->format('g:i A')
            . ' - '
            . Carbon::createFromFormat('H:i', $validated['end_time'])->format('g:i A');

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
        ]);

        UserActivityLogger::log('created schedule event', Auth::user()?->user_id, $request);

        return response()->json([
            'success' => true,
            'message' => 'Schedule event created successfully.',
            'data' => [
                'event_id' => $event->event_id,
                'event' => $event->event,
                'event_date' => optional($event->event_date)->toDateString(),
                'start_time' => $event->start_time,
                'end_time' => $event->end_time,
                'schedule' => $event->schedule,
                'activity_type' => $event->activity_type,
                'status' => $event->status,
                'barangay' => $event->barangay,
                'notes' => $event->notes,
                'station_id' => $event->station_id,
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
        $alerts = Incident::with('marker')->get();
        $stations = Station::all();
        $provinces = Province::orderBy('province_name')->get();
        $thisperson = $this->getAuthenticatedStationOfficer();
        $stationId = $thisperson->station_id;
        $stationperson = StationOfficer::where('station_id',$stationId)->with('station','station.users')->get();
        // Get all users with role 'chief'
        $chiefRole = Role::where('role', 'chief')->first();
        $chiefs = $chiefRole ? User::where('role_id', $chiefRole->role_id)->get() : collect();
        $fsicRequests= FsicRequest::all();
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

        return view('chief.dashboard', compact(
            'fsicRequests',
            'mapmarker',
            'incidents',
            'alerts',
            'stations',
            'chiefs',
            'provinces',
            'stationperson',
            'citizens',
            'barangayOfficers',
            'defaultTeam',
            'incidentTrendData',
            'stationReadiness',
            'recentAnalyses'
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
        $managedBarangays = $this->getManagedBarangaysForStationAdmin();
        $barangayIds = $managedBarangays->pluck('barangay_id')->map(fn ($id) => (int) $id)->all();

        $mitigationActions = MitigationAction::query()
            ->with(['barangay.city', 'attachments', 'riskArea.marker', 'checklist', 'aiActionPlanChecklist', 'creator'])
            ->when(! empty($barangayIds), fn ($query) => $query->whereIn('barangay_id', $barangayIds))
            ->when(empty($barangayIds), fn ($query) => $query->whereRaw('1 = 0'))
            ->orderByRaw("FIELD(priority_level, 'critical', 'high', 'medium', 'low')")
            ->orderByRaw("FIELD(status, 'in_progress', 'planned', 'blocked', 'completed', 'cancelled')")
            ->orderBy('target_date')
            ->orderByDesc('created_at')
            ->get();

        $riskAreas = RiskArea::query()
            ->with(['marker', 'barangayHall.barangay'])
            ->when(! empty($barangayIds), function ($query) use ($barangayIds) {
                $query->whereHas('barangayHall', fn ($hallQuery) => $hallQuery->whereIn('barangay_id', $barangayIds));
            })
            ->when(empty($barangayIds), fn ($query) => $query->whereRaw('1 = 0'))
            ->orderByDesc('created_at')
            ->get();

        $predictionChecklists = BarangayPredictionChecklist::query()
            ->with(['assignee', 'reviewer', 'barangay'])
            ->when(! empty($barangayIds), fn ($query) => $query->whereIn('barangay_id', $barangayIds))
            ->when(empty($barangayIds), fn ($query) => $query->whereRaw('1 = 0'))
            ->orderByRaw("FIELD(priority_level, 'critical', 'high', 'medium', 'low')")
            ->orderBy('target_date')
            ->get();

        $aiActionPlanOptions = DB::table('ai_action_plan_checklists')
            ->when(! empty($barangayIds), fn ($query) => $query->whereIn('barangay_id', $barangayIds))
            ->when(empty($barangayIds), fn ($query) => $query->whereRaw('1 = 0'))
            ->orderByDesc('created_at')
            ->get(['ai_action_plan_checklist_id', 'barangay_id', 'plan_title', 'priority_level', 'status']);

        return view('chief.mitigations', [
            'managedBarangays' => $managedBarangays,
            'mitigationActions' => $mitigationActions,
            'riskAreas' => $riskAreas,
            'predictionChecklists' => $predictionChecklists,
            'aiActionPlanOptions' => $aiActionPlanOptions,
            'mitigationCategoryOptions' => $this->mitigationCategoryOptions(),
            'mitigationPriorityOptions' => $this->mitigationPriorityOptions(),
            'mitigationStatusOptions' => $this->mitigationStatusOptions(),
        ]);
    }

    public function storeMitigation(Request $request)
    {
        $validated = $request->validate($this->mitigationValidationRules());
        $allowedBarangayIds = $this->getManagedBarangaysForStationAdmin()
            ->pluck('barangay_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $linkedIds = $this->validateMitigationLinksForStation($validated, $allowedBarangayIds);

        DB::transaction(function () use ($validated, $linkedIds, $request) {
            $action = MitigationAction::create([
                'barangay_id' => $linkedIds['barangay_id'],
                'risk_area_id' => $linkedIds['risk_area_id'],
                'barangay_prediction_checklist_id' => $linkedIds['barangay_prediction_checklist_id'],
                'ai_action_plan_checklist_id' => $linkedIds['ai_action_plan_checklist_id'],
                'title' => $validated['title'],
                'category' => $validated['category'],
                'priority_level' => $validated['priority_level'],
                'status' => $validated['status'],
                'target_date' => $validated['target_date'] ?? null,
                'completed_at' => ($validated['status'] ?? null) === 'completed' ? now() : null,
                'description' => $validated['description'],
                'remarks' => $validated['remarks'] ?? null,
                'created_by' => Auth::user()?->user_id,
            ]);

            $this->storeMitigationAttachments($action, $request);
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
        $action = $this->resolveManagedMitigationAction($mitigationActionId, $allowedBarangayIds);
        $linkedIds = $this->validateMitigationLinksForStation($validated, $allowedBarangayIds);

        DB::transaction(function () use ($validated, $linkedIds, $action, $request) {
            $action->update([
                'barangay_id' => $linkedIds['barangay_id'],
                'risk_area_id' => $linkedIds['risk_area_id'],
                'barangay_prediction_checklist_id' => $linkedIds['barangay_prediction_checklist_id'],
                'ai_action_plan_checklist_id' => $linkedIds['ai_action_plan_checklist_id'],
                'title' => $validated['title'],
                'category' => $validated['category'],
                'priority_level' => $validated['priority_level'],
                'status' => $validated['status'],
                'target_date' => $validated['target_date'] ?? null,
                'completed_at' => ($validated['status'] ?? null) === 'completed'
                    ? ($action->completed_at ?? now())
                    : null,
                'description' => $validated['description'],
                'remarks' => $validated['remarks'] ?? null,
                'reviewed_by' => Auth::user()?->user_id,
                'reviewed_at' => now(),
            ]);

            $this->storeMitigationAttachments($action, $request);
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
                    Storage::disk('public')->delete($attachment->file_path);
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
}
