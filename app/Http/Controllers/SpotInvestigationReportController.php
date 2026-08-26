<?php

namespace App\Http\Controllers;

use App\Models\SpotInvestigationReport;
use App\Models\Incident;
use App\Models\Weather;
use App\Models\StationOfficer;
use App\Services\SpotNarrativeDraftService;
use App\Services\InvestigationReportImageService;
use App\Support\HistoricalFireIncidentSynchronizer;
use App\Support\IncidentOccurrenceSynchronizer;
use App\Support\UserActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class SpotInvestigationReportController extends Controller
{
    public function index()
    {
        $reports = SpotInvestigationReport::with('incident', 'investigator.user', 'progressInvestigation', 'notedBy.user', 'approvedBy.user')
            ->paginate(15);
        return view('admin.investigations.spot-reports.index', compact('reports'));
    }

    public function create()
    {
        $incidents = Incident::doesntHave('spotInvestigationReport')->get();
        $users = StationOfficer::with('user')->get();
        $weatherReports = Weather::all();
        
        return view('admin.investigations.spot-reports.create', compact('incidents', 'users', 'weatherReports'));
    }

//     public function storeSpot(Request $request)
// {
//     $validated = $request->validate([
//         'investigator' => 'required|string',
//         'dead' => 'required|integer|min:0',
//         'injured' => 'required|integer|min:0',
//         'owner' => 'required|string',
//         'fire_started' => 'required|date',
//         'fire_out' => 'required|date',
//         'estimated_damage' => 'required|numeric',
//         'occupancy' => 'required|string',
//         'estimated_area' => 'required|numeric',
//         'incident_id' => 'required|exists:incidents,incident_id',
//     ]);
    
//     // Combine dead and injured into casualty field
//     $validated['casualty'] = $validated['dead'] . ' dead, ' . $validated['injured'] . ' injured';
//     unset($validated['dead'], $validated['injured']);
    
//     $spot = SpotInvestigation::create($validated);
    
//     return response()->json(['success' => true, 'data' => $spot]);
// }

    // Add this method if it doesn't exist
    public function getAvailableSpotInvestigations()
    {
        try {
        $spotInvestigations = SpotInvestigationReport::with(['incident', 'investigator'])
                ->whereDoesntHave('progressInvestigation')
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Transform the data to include the fields your JavaScript expects
            $formattedSpots = $spotInvestigations->map(function($spot) {
                return [
                    'spot_investigation_report_id' => $spot->spot_investigation_report_id,
                    'station_officer_id' => $spot->station_officer_id ?? 'N/A',
                    'fire_started' => $spot->fire_started ? $spot->fire_started->format('Y-m-d H:i:s') : 'N/A',
                    'incident_id' => $spot->incident_id,
                ];
            });
            
            return response()->json($formattedSpots);
            
        } catch (\Exception $e) {
            \Log::error('Error in getAvailableSpotInvestigations: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request, InvestigationReportImageService $imageService)
    {
        if ($cached = $imageService->cachedSubmissionResponse($request, 'spot:create')) {
            return response()->json($cached);
        }

        $validated = $request->validate([
            'incident_id' => 'required|exists:incidents,incident_id|unique:spot_investigation_report,incident_id',
            'station_officer_id' => 'required|exists:station_officers,station_officer_id',
            'dead' => 'nullable|numeric|min:0',
            'injured' => 'nullable|numeric|min:0',
            'owner' => 'nullable|string|max:255',
            'fire_started' => 'nullable|date',
            'fire_out' => 'nullable|date|after_or_equal:fire_started',
            'estimated_damage' => 'required|numeric|min:0',
            'occupancy' => 'required|in:residential,commercial,industrial',
            'estimated_area' => 'required|numeric|min:0',
            'memorandum_for' => 'nullable|string|max:255',
            'place_of_occurrence' => 'nullable|string|max:255',
            'involved_structure' => 'nullable|string|max:255',
            'establishment_name' => 'nullable|string|max:255',
            'occupant' => 'nullable|string|max:255',
            'highest_alarm_level' => 'nullable|in:1st Alarm,2nd Alarm,3rd Alarm,4th Alarm,5th Alarm',
            'affected_establishments_count' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'fatality_details' => 'nullable|string',
            'details_of_investigation' => 'nullable|string',
            'disposition' => 'nullable|string',
            'noted_by_station_officer_id' => 'nullable|exists:station_officers,station_officer_id',
            'approved_by_station_officer_id' => 'nullable|exists:station_officers,station_officer_id',
            ...InvestigationReportImageService::validationRules(),
        ]);

        unset($validated['proof_images']);
        $validated['report_completed_at'] = now();

        $response = SpotInvestigationReport::create($validated);
        $imageService->store($request, $response, 'spot');
        if ($response->fire_started) {
            $incident = Incident::find($response->incident_id);
            if ($incident) {
                IncidentOccurrenceSynchronizer::verify(
                    $incident,
                    $response->fire_started,
                    'spot_investigation',
                    auth()->user()?->user_id
                );
            }
        }
        HistoricalFireIncidentSynchronizer::trySyncForIncidentId((int) $response->incident_id);
        UserActivityLogger::log('created spot report', auth()->user()?->user_id, $request);
        $payload = ['success'=>true, 'data'=>InvestigationReportImageService::responsePayload($response)];

        return response()->json($imageService->rememberSubmissionResponse($request, 'spot:create', $payload));
    }

    public function show(SpotInvestigationReport $spotInvestigationReport)
    {
        $spotInvestigationReport->load('incident', 'investigator.user', 'progressInvestigation', 'notedBy.user', 'approvedBy.user');
        return view('admin.investigations.spot-reports.show', compact('spotInvestigationReport'));
    }

    public function edit(SpotInvestigationReport $spotInvestigationReport)
    {
        $incidents = Incident::all();
        $users = StationOfficer::with('user')->get();
        $weatherReports = Weather::all();
        
        return view('admin.investigations.spot-reports.edit', compact('spotInvestigationReport', 'incidents', 'users', 'weatherReports'));
    }

    public function update(Request $request, string $id, InvestigationReportImageService $imageService)
    {
         $spot = SpotInvestigationReport::find($id);
        if (!$spot) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }
        if ($cached = $imageService->cachedSubmissionResponse($request, "spot:update:{$id}")) {
            return response()->json($cached);
        }
        $validated = $request->validate([
            'dead' => 'sometimes|required|numeric|min:0',
            'injured' => 'sometimes|required|numeric|min:0',
            'owner' => 'nullable|string|max:255',
            'fire_started' => 'nullable|date',
            'fire_out' => 'nullable|date|after_or_equal:fire_started',
            'estimated_damage' => 'sometimes|required|numeric|min:0',
            'occupancy' => 'sometimes|required|in:residential,commercial,industrial',
            'estimated_area' => 'sometimes|required|numeric|min:0',
            'memorandum_for' => 'nullable|string|max:255',
            'place_of_occurrence' => 'nullable|string|max:255',
            'involved_structure' => 'nullable|string|max:255',
            'establishment_name' => 'nullable|string|max:255',
            'occupant' => 'nullable|string|max:255',
            'highest_alarm_level' => 'nullable|in:1st Alarm,2nd Alarm,3rd Alarm,4th Alarm,5th Alarm',
            'affected_establishments_count' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'fatality_details' => 'nullable|string',
            'details_of_investigation' => 'nullable|string',
            'disposition' => 'nullable|string',
            'noted_by_station_officer_id' => 'nullable|exists:station_officers,station_officer_id',
            'approved_by_station_officer_id' => 'nullable|exists:station_officers,station_officer_id',
            ...InvestigationReportImageService::validationRules(),
        ]);

        unset($validated['proof_images']);
        $spot->update($validated);
        $imageService->store($request, $spot, 'spot');
        if (array_key_exists('fire_started', $validated) && $spot->fire_started) {
            $incident = Incident::find($spot->incident_id);
            if ($incident) {
                IncidentOccurrenceSynchronizer::verify(
                    $incident,
                    $spot->fire_started,
                    'spot_investigation',
                    auth()->user()?->user_id
                );
            }
        }
        HistoricalFireIncidentSynchronizer::trySyncForIncidentId((int) $spot->incident_id);
        UserActivityLogger::log('updated spot report', auth()->user()?->user_id, $request);
        $payload = ['success'=>true, 'data' => InvestigationReportImageService::responsePayload($spot->fresh())];

        return response()->json($imageService->rememberSubmissionResponse($request, "spot:update:{$id}", $payload));
    }

    public function draftNarrative(Request $request, SpotNarrativeDraftService $draftService)
    {
        $validated = $request->validate([
            'incident_id' => 'nullable|exists:incidents,incident_id',
            'description' => 'nullable|string',
            'details_of_investigation' => 'nullable|string',
            'disposition' => 'nullable|string',
            'place_of_occurrence' => 'nullable|string|max:255',
            'incident_location' => 'nullable|string|max:255',
            'fire_started' => 'nullable|date',
            'fire_out' => 'nullable|date',
            'occupancy' => 'nullable|string|max:100',
            'involved_structure' => 'nullable|string|max:255',
            'establishment_name' => 'nullable|string|max:255',
            'owner' => 'nullable|string|max:255',
            'occupant' => 'nullable|string|max:255',
            'estimated_damage' => 'nullable',
            'estimated_area' => 'nullable',
            'highest_alarm_level' => 'nullable|in:1st Alarm,2nd Alarm,3rd Alarm,4th Alarm,5th Alarm',
            'dead' => 'nullable',
            'injured' => 'nullable',
            'fatality_details' => 'nullable|string',
        ]);

        if (! empty($validated['incident_id'])) {
            $incident = Incident::with(['context', 'historicalRecords'])->find($validated['incident_id']);

            if ($incident) {
                $context = $incident->context;
                $historical = $incident->historicalRecords
                    ?->sortByDesc(fn ($record) => $record->incident_date?->format('Y-m-d') . ' ' . ($record->incident_time ?? ''))
                    ?->first();

                $weatherSegments = collect([
                    $context?->weather_condition ?? $historical?->weather_condition,
                    ($context?->temperature ?? $historical?->temperature) !== null
                        ? 'Temperature: ' . number_format((float) ($context?->temperature ?? $historical?->temperature), 1) . ' C'
                        : null,
                    ($context?->humidity ?? $historical?->humidity) !== null
                        ? 'Humidity: ' . number_format((float) ($context?->humidity ?? $historical?->humidity), 1) . '%'
                        : null,
                    ($context?->wind_speed ?? $historical?->wind_speed) !== null
                        ? 'Wind: ' . number_format((float) ($context?->wind_speed ?? $historical?->wind_speed), 1) . ' km/h'
                        : null,
                    $context?->wind_direction ?? $historical?->wind_direction
                        ? 'Wind direction: ' . ($context?->wind_direction ?? $historical?->wind_direction)
                        : null,
                ])->filter()->implode(' | ');

                $validated['weather_condition'] = $context?->weather_condition ?? $historical?->weather_condition;
                $validated['weather_summary'] = $weatherSegments;
                $validated['incident_reported_at'] = $incident->created_at
                    ? Carbon::parse($incident->created_at)->format('F j, Y g:i A')
                    : null;
            }
        }

        $hasUsefulInput = collect($validated)
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->isNotEmpty();

        if (! $hasUsefulInput) {
            return response()->json([
                'success' => false,
                'message' => 'Add some field notes or incident details first before generating a narrative.',
            ], 422);
        }

        $narrative = $draftService->draftSpotNarrative($validated);

        return response()->json([
            'success' => true,
            'data' => [
                'narrative' => $narrative,
            ],
        ]);
    }

    public function destroy(string $id)
    {
         $spot = SpotInvestigationReport::find($id);

        if (!$spot) {
            return response()->json(['success' => false, 'message' => 'not found'], 404);

        }

        $incidentId = (int) $spot->incident_id;
        InvestigationReportImageService::deleteForReport($spot);
        $spot->delete();
        HistoricalFireIncidentSynchronizer::syncForIncidentId($incidentId);
        UserActivityLogger::log('deleted spot report', auth()->user()?->user_id, request());
        return response()->json(['success'=>true]);
    }
}
