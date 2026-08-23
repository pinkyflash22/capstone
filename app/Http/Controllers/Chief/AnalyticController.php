<?php

namespace App\Http\Controllers\Chief;

use App\Http\Controllers\Controller;
use App\Models\AiActionPlanChecklist;
use App\Models\AiActionPlanChecklistActivity;
use App\Models\AiActionPlanChecklistItem;
use App\Models\AiRecommendationSuggestion;
use App\Models\Analysis;
use App\Models\Incident;
use App\Models\MitigationAction;
use App\Models\StationOfficer;
use App\Models\Team;
use App\Models\User;
use App\Services\BarangayPredictionService;
use App\Services\ForecastModelService;
use App\Services\PredictionFeedbackSignalService;
use App\Support\BarangayNotificationService;
use App\Support\ForecastIncidentCalibration;
use App\Support\PeakHourEstimator;
use App\Support\UserActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AnalyticController extends Controller
{
    private $apiKey;

    private ?string $lastAiModelUsed = null;

    private array $unavailableAiModels = [];

    private BarangayPredictionService $barangayPredictionService;

    private ForecastModelService $forecastModelService;

    private PredictionFeedbackSignalService $predictionFeedbackSignalService;

    public function __construct(
        ForecastModelService $forecastModelService,
        BarangayPredictionService $barangayPredictionService,
        PredictionFeedbackSignalService $predictionFeedbackSignalService
    ) {
        $this->forecastModelService = $forecastModelService;
        $this->barangayPredictionService = $barangayPredictionService;
        $this->predictionFeedbackSignalService = $predictionFeedbackSignalService;
        $this->apiKey = (string) config('services.openrouter.api_key', '');

        if (! $this->apiKey) {
            Log::warning('OpenRouter API key not found in .env file');
        } else {
            Log::info('OpenRouter API key loaded successfully');
        }
    }

    public function index()
    {
        return view('chief.analytics');
    }

    public function checklistIndex(Request $request)
    {
        $stationOfficer = StationOfficer::where('user_id', Auth::id())->first();
        $stationId = $stationOfficer?->station_id;
        $isProvincialAdmin = strtolower(trim((string) (Auth::user()?->role?->role ?? ''))) === 'provincial admin';

        $baseQuery = AiActionPlanChecklist::query()
            ->when($stationId, fn ($builder) => $builder->where('station_id', $stationId))
            ->when(! $stationId && ! $isProvincialAdmin, fn ($builder) => $builder->whereRaw('1 = 0'));

        $summary = [
            'total' => (clone $baseQuery)->count(),
            'open' => (clone $baseQuery)->whereIn('status', ['pending', 'in_progress'])->count(),
            'submitted' => (clone $baseQuery)->where('status', 'submitted')->count(),
            'confirmed' => (clone $baseQuery)->where('status', 'confirmed')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
        ];

        $query = (clone $baseQuery)
            ->with(['analysis', 'barangay', 'assignee', 'confirmer', 'suggestions', 'items.reviewer', 'mitigationActions'])
            ->withCount('mitigationActions');

        if ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->integer('barangay_id'));
        }

        $status = in_array($request->input('status'), ['pending', 'in_progress', 'submitted', 'confirmed', 'rejected'], true)
            ? $request->input('status') : null;
        if ($status) {
            $query->where('status', $status);
        }

        $priority = in_array($request->input('priority'), ['critical', 'high', 'medium', 'low'], true)
            ? $request->input('priority') : null;
        if ($priority) {
            $query->where('priority_level', $priority);
        }

        if ($request->filled('q')) {
            $search = mb_substr(trim((string) $request->input('q')), 0, 100);
            $query->where(function ($builder) use ($search) {
                $builder->where('plan_title', 'like', "%{$search}%")
                    ->orWhere('plan_description', 'like', "%{$search}%")
                    ->orWhere('source_title', 'like', "%{$search}%");
            });
        }

        $checklists = $query
            ->orderByRaw("
                CASE priority_level
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    ELSE 4
                END
            ")
            ->orderByDesc('created_at')
            ->paginate(max(5, min(25, $request->integer('per_page', 10))))
            ->withQueryString();

        $barangays = AiActionPlanChecklist::query()
            ->with('barangay')
            ->when($stationId, fn ($builder) => $builder->where('station_id', $stationId))
            ->when(! $stationId && ! $isProvincialAdmin, fn ($builder) => $builder->whereRaw('1 = 0'))
            ->whereNotNull('barangay_id')
            ->get()
            ->pluck('barangay')
            ->filter()
            ->unique('barangay_id')
            ->sortBy('barangay_name')
            ->values();

        $assignees = StationOfficer::query()
            ->with('user.role')
            ->when($stationId, fn ($builder) => $builder->where('station_id', $stationId))
            ->when(! $stationId && ! $isProvincialAdmin, fn ($builder) => $builder->whereRaw('1 = 0'))
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('user_id')
            ->sortBy(fn ($user) => strtolower(trim(($user->last_name ?? '').' '.($user->first_name ?? ''))))
            ->values();

        $viewData = [
            'checklists' => $checklists,
            'barangays' => $barangays,
            'assignees' => $assignees,
            'summary' => $summary,
            'filters' => [
                'barangay_id' => $request->input('barangay_id'),
                'status' => $status,
                'priority' => $priority,
                'q' => mb_substr((string) $request->input('q'), 0, 100),
            ],
        ];

        if ($request->boolean('partial')) {
            return view('chief.partials.course-checklist-workspace', $viewData);
        }

        return view('chief.course-checklists', $viewData);
    }

    /**
     * Get stations/locations for the analytics dropdown
     */
    public function getStationsForAnalytics()
    {
        try {
            Log::info('Fetching stations for analytics');

            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'data' => [['id' => 'all', 'name' => 'All Locations']],
                    'message' => 'User not authenticated',
                ]);
            }

            $locations = collect();

            // Get user's station
            $stationOfficer = StationOfficer::where('user_id', $user->user_id)->first();

            if ($stationOfficer && $stationOfficer->station_id) {
                $station = DB::table('stations')->where('station_id', $stationOfficer->station_id)->first();
                if ($station) {
                    $barangays = $this->resolveStationBarangays((int) $station->station_id)
                        ->map(fn ($barangay) => [
                            'barangay_id' => (int) $barangay->barangay_id,
                            'barangay_name' => $barangay->barangay_name,
                            'city_name' => $barangay->city_name ?? null,
                        ])
                        ->values()
                        ->all();

                    $locations->push((object) [
                        'id' => (string) $station->station_id,
                        'name' => $station->station_name,
                        'barangays' => $barangays,
                    ]);
                }
            }

            if ($locations->isEmpty()) {
                // Fallback: get all stations
                $stations = DB::table('stations')->get();
                foreach ($stations as $station) {
                    $barangays = $this->resolveStationBarangays((int) $station->station_id)
                        ->map(fn ($barangay) => [
                            'barangay_id' => (int) $barangay->barangay_id,
                            'barangay_name' => $barangay->barangay_name,
                            'city_name' => $barangay->city_name ?? null,
                        ])
                        ->values()
                        ->all();

                    $locations->push((object) [
                        'id' => (string) $station->station_id,
                        'name' => $station->station_name,
                        'barangays' => $barangays,
                    ]);
                }
            }

            if ($locations->isEmpty()) {
                $locations->push((object) [
                    'id' => 'all',
                    'name' => 'All Locations',
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $locations,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching stations: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'data' => [['id' => 'all', 'name' => 'All Locations']],
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * AI-powered predictive analysis based on REAL database data
     */
    public function runAIPredictiveAnalysis(Request $request)
    {
        try {
            @set_time_limit(300);
            @ini_set('max_execution_time', '300');

            Log::info('AI Analysis request received', $request->all());

            $locationId = $request->input('location_id', 'all');
            $selectedBarangayId = $this->normalizeSelectedBarangayId($request->input('barangay_id'));
            $days = (int) $request->input('days', 30);
            $predictionScope = $request->input('prediction_scope', 'current_month');
            $historicalWindow = $request->input('historical_window', '3');
            $analysisKind = $request->input('analysis_kind', $predictionScope === 'next_month' ? 'forecast' : 'actual_analysis');

            // Fetch REAL data from database
            $analysisData = $this->fetchRealTimeData($locationId, $days, $predictionScope, $selectedBarangayId, $historicalWindow);
            $analysisData['analysis_kind'] = $analysisKind;
            $analysisData['replace_existing'] = $request->boolean('replace_existing');

            $predictionAiResponse = null;
            $recommendationAiResponse = null;
            $predictionAiModel = null;
            $recommendationAiModel = null;
            $aiFailureMessage = null;
            try {
                $predictionAiResponse = $this->callAIApi(
                    $this->buildAIPredictionPrompt($analysisData),
                    'You are a fire risk forecasting analyst. Predict likely monthly fire activity, possible fire count, probable peak hours, and operational outlook using the provided station data.'
                );
                $predictionAiModel = $this->lastAiModelUsed;
                $recommendationAiResponse = $this->callAIApi(
                    $this->buildAIRecommendationPrompt($analysisData),
                    'You are a fire station operations strategist. Produce concrete, station-ready recommendations based on the provided incident, staffing, equipment, and weather context.'
                );
                $recommendationAiModel = $this->lastAiModelUsed;
                Log::info('AI prediction response received', ['response' => substr($predictionAiResponse, 0, 300)]);
                Log::info('AI recommendation response received', ['response' => substr($recommendationAiResponse, 0, 300)]);
            } catch (\Exception $aiError) {
                $aiFailureMessage = $aiError->getMessage();
                Log::warning('AI API failed: '.$aiError->getMessage());
            }

            // If AI succeeded, parse it; otherwise use data-driven analysis
            if ($predictionAiResponse && $recommendationAiResponse) {
                $structuredAnalysis = $this->structureAIAnalysisSeparated($predictionAiResponse, $recommendationAiResponse, $analysisData);
                $structuredAnalysis['ai_source'] = true;
                $structuredAnalysis['ai_models'] = [
                    'prediction' => $predictionAiModel,
                    'recommendation' => $recommendationAiModel,
                ];
            } else {
                $structuredAnalysis = $this->generateDataDrivenAnalysis($analysisData);
                $structuredAnalysis['note'] = 'Analysis based on data patterns (AI service temporarily unavailable)';
                $structuredAnalysis['ai_source'] = false;
                $structuredAnalysis['ai_warning'] = 'DeepSeek forecast model failed. Showing data-driven fallback analysis instead.';
                $structuredAnalysis['ai_failure_message'] = $aiFailureMessage ?: 'DeepSeek forecast model did not return a usable response.';
                $structuredAnalysis['ai_models'] = [
                    'prediction' => $predictionAiModel,
                    'recommendation' => $recommendationAiModel,
                ];
            }

            $structuredAnalysis = $this->applyStructuredIncidentCountSemantics($structuredAnalysis, $analysisData);

            $structuredAnalysis['model_metadata'] = $this->buildScopedModelMetadataContext(
                (int) ($analysisData['station_id'] ?? $locationId),
                $selectedBarangayId
            );
            $structuredAnalysis['workflow_context'] = $this->buildWorkflowContext($structuredAnalysis, $analysisData);

            $structuredAnalysis['checklist_debug'] = [
                'attempted' => false,
                'analysis_id' => null,
                'recommendation_count' => count($structuredAnalysis['recommendations'] ?? []),
                'persisted_checklist_rows' => 0,
                'persisted_checklist_items' => 0,
                'used_ai_generation' => false,
                'used_fallback_generation' => false,
                'warning' => null,
            ];
            $structuredAnalysis['suggestion_debug'] = [
                'attempted' => false,
                'analysis_id' => null,
                'persisted_suggestion_rows' => 0,
                'warning' => null,
            ];
            $structuredAnalysis['mitigation_debug'] = [
                'attempted' => false,
                'analysis_id' => null,
                'created_draft_rows' => 0,
                'updated_draft_rows' => 0,
                'skipped_existing_rows' => 0,
                'warning' => null,
            ];

            // Store analysis in database (optional, catch errors)
            $analysisId = null;
            try {
                $analysisId = $this->storeAnalysis($locationId, $days, $structuredAnalysis, $analysisData);
                if ($analysisId) {
                    $structuredAnalysis['analysis_id'] = $analysisId;
                    $structuredAnalysis['analysis_revision'] = max(
                        1,
                        (int) (Analysis::query()->whereKey($analysisId)->value('revision_number') ?? 1)
                    );
                    $structuredAnalysis['checklist_debug']['attempted'] = true;
                    $structuredAnalysis['checklist_debug']['analysis_id'] = $analysisId;

                    $checklistGeneration = $this->generateAndStoreActionPlanChecklists(
                        $analysisId,
                        $structuredAnalysis,
                        $analysisData
                    );
                    $structuredAnalysis['action_plan_checklists'] = $checklistGeneration['plans'];
                    $structuredAnalysis['checklist_debug'] = array_merge(
                        $structuredAnalysis['checklist_debug'],
                        $checklistGeneration['debug']
                    );
                    $structuredAnalysis['recommendations'] = $this->attachChecklistIdsToRecommendations(
                        $structuredAnalysis['recommendations'] ?? [],
                        $structuredAnalysis['action_plan_checklists'] ?? []
                    );
                    $structuredAnalysis['suggestion_debug']['attempted'] = true;
                    $structuredAnalysis['suggestion_debug']['analysis_id'] = $analysisId;
                    $suggestionGeneration = $this->generateAndStoreRecommendationSuggestions(
                        $analysisId,
                        $structuredAnalysis['recommendations'] ?? [],
                        $structuredAnalysis['action_plan_checklists'] ?? []
                    );
                    $structuredAnalysis['recommendation_suggestions'] = $suggestionGeneration['suggestions'];
                    $structuredAnalysis['suggestion_debug'] = array_merge(
                        $structuredAnalysis['suggestion_debug'],
                        $suggestionGeneration['debug']
                    );
                    $structuredAnalysis['mitigation_debug']['attempted'] = true;
                    $structuredAnalysis['mitigation_debug']['analysis_id'] = $analysisId;
                    $mitigationDraftGeneration = $this->syncMitigationDraftActionsForAnalysis(
                        $structuredAnalysis['action_plan_checklists'] ?? []
                    );
                    $structuredAnalysis['mitigation_debug'] = array_merge(
                        $structuredAnalysis['mitigation_debug'],
                        $mitigationDraftGeneration
                    );
                    $structuredAnalysis['workflow_context'] = $this->buildWorkflowContext($structuredAnalysis, $analysisData);
                    $this->updateStoredAnalysisArtifacts($analysisId, $structuredAnalysis, $analysisData);
                }
            } catch (\Exception $storeError) {
                Log::warning('Failed to store analysis/checklists: '.$storeError->getMessage(), [
                    'exception' => $storeError,
                ]);
                $structuredAnalysis['checklist_debug'] = array_merge(
                    $structuredAnalysis['checklist_debug'],
                    [
                        'analysis_id' => $analysisId,
                        'warning' => $storeError->getMessage(),
                    ]
                );
                $structuredAnalysis['suggestion_debug'] = array_merge(
                    $structuredAnalysis['suggestion_debug'],
                    [
                        'analysis_id' => $analysisId,
                        'warning' => $storeError->getMessage(),
                    ]
                );
                $structuredAnalysis['mitigation_debug'] = array_merge(
                    $structuredAnalysis['mitigation_debug'],
                    [
                        'analysis_id' => $analysisId,
                        'warning' => $storeError->getMessage(),
                    ]
                );
                $structuredAnalysis['warnings'][] = 'Action-plan checklist could not be fully stored. Check checklist_debug for details.';
            }

            if (! $analysisId) {
                Log::error('Analysis completed but was not saved.', [
                    'station_id' => $analysisData['station_id'] ?? null,
                    'barangay_id' => $selectedBarangayId,
                    'prediction_scope' => $predictionScope,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Analysis completed but could not be saved. Please retry after resolving the server error.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $structuredAnalysis,
                'location_name' => $analysisData['location_name'],
            ]);

        } catch (\Exception $e) {
            Log::error('AI Analysis error: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Analysis failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function runPredictionApi(Request $request)
    {
        try {
            if (! $this->forecastModelService->ensureReady()) {
                $report = $this->forecastModelService->lastTrainingReport();

                Log::warning('Forecast model is unavailable; continuing with database-driven analysis.', [
                    'forecast_report' => $report,
                ]);
            } else {
                $this->barangayPredictionService->assertRuntimeReady();
            }
        } catch (\Throwable $e) {
            // runAIPredictiveAnalysis already provides a deterministic fallback
            // from scoped incidents, risks, weather, personnel, and equipment.
            // A Python/runtime problem should degrade the model signal, not fail
            // the complete command analysis request.
            Log::warning('Prediction runtime unavailable; continuing with database-driven analysis.', [
                'message' => $e->getMessage(),
                'forecast_report' => $this->forecastModelService->lastTrainingReport(),
            ]);
        }

        return $this->runAIPredictiveAnalysis($request);
    }

    public function retrainForecastModel()
    {
        try {
            @set_time_limit(180);
            @ini_set('max_execution_time', '180');

            $report = $this->forecastModelService->retrain(exportDataset: true);

            if (! ($report['success'] ?? false)) {
                Log::warning('Manual forecast model retrain failed.', [
                    'report' => $report,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $report['message'] ?? 'Model retraining failed. Check server logs for details.',
                    'details' => $report['exception_message'] ?? null,
                    'diagnostics' => $report['diagnostics'] ?? [],
                    'code' => $report['status'] ?? 'training_failed',
                ], 500);
            }

            $metadata = $this->forecastModelService->readMetadataSummary();

            return response()->json([
                'success' => true,
                'message' => 'Forecast model retrained successfully.',
                'model_metadata' => $metadata,
            ]);
        } catch (\Throwable $e) {
            Log::error('Manual forecast model retrain error.', [
                'message' => $e->getMessage(),
                'report' => $this->forecastModelService->lastTrainingReport(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrain the model on this server.',
                'details' => 'Check the server logs for the training command output.',
                'diagnostics' => $this->forecastModelService->readinessDiagnostics(),
                'code' => 'training_exception',
            ], 500);
        }
    }

    /**
     * Fetch REAL data from your database tables
     */
    // private function fetchRealTimeData($locationId, $days)
    // {
    //     $startDate = Carbon::now()->subDays($days);

    //     // Get user's station
    //     $user = Auth::user();

    //     if (!$user) {
    //         throw new \Exception('User not authenticated');
    //     }

    //     $stationOfficer = StationOfficer::where('user_id', $user->user_id)->first();

    //     if (!$stationOfficer) {
    //         throw new \Exception('No station assigned to this user');
    //     }

    //     $stationId = $stationOfficer->station_id;

    //     // Get station name
    //     $station = DB::table('stations')->where('station_id', $stationId)->first();
    //     $locationName = $station ? $station->station_name : 'My Station';

    //     // Get all personnel in this station
    //     $allPersons = StationOfficer::where('station_id', $stationId)->get();
    //     $userIds = $allPersons->pluck('user_id')->toArray();

    //     // Get team IDs for this station
    //     $teamIds = Team::whereIn('team_leader', $userIds)->pluck('id')->toArray();

    //     // Get incidents for this station's teams
    //     $incidents = collect();

    //     if (!empty($teamIds)) {
    //         $incidents = Incident::whereIn('incident_status_id', [1, 2])
    //             ->whereIn('team_id', $teamIds)
    //             ->where('created_at', '>=', $startDate)
    //             ->get();
    //     }

    //     Log::info('Found ' . $incidents->count() . ' incidents for station ' . $stationId);

    //     // Calculate statistics
    //     $totalIncidents = $incidents->count();

    //     // Incidents by type
    //     $incidentsByType = [];
    //     $fireIncidents = 0;

    //     foreach ($incidents as $incident) {
    //         $type = $incident->incident_type ?? 'unknown';
    //         $incidentsByType[$type] = ($incidentsByType[$type] ?? 0) + 1;
    //         if (strtolower($type) === 'fire' || strpos(strtolower($type), 'fire') !== false) {
    //             $fireIncidents++;
    //         }
    //     }

    //     // Hourly distribution
    //     $hourlyData = array_fill(0, 24, 0);
    //     $dailyData = array_fill(0, 7, 0);

    //     foreach ($incidents as $incident) {
    //         $incidentDate = $incident->created_at ?? null;
    //         if ($incidentDate) {
    //             try {
    //                 $date = Carbon::parse($incidentDate);
    //                 $hour = (int)$date->format('H');
    //                 $dayOfWeek = $date->dayOfWeek;
    //                 $hourlyData[$hour]++;
    //                 $dailyData[$dayOfWeek]++;
    //             } catch (\Exception $e) {}
    //         }
    //     }

    //     // Find peak hour
    //     $peakHour = array_search(max($hourlyData), $hourlyData);
    //     if ($peakHour === false || max($hourlyData) === 0) {
    //         $peakHour = null;
    //     }

    //     // Calculate average response time
    //     $avgResponseTime = null;
    //     $completedIncidents = $incidents->filter(function($inc) {
    //         return isset($inc->dateEnded) && isset($inc->alarmdatetime);
    //     });

    //     if ($completedIncidents->count() > 0) {
    //         $totalMinutes = 0;
    //         foreach ($completedIncidents as $inc) {
    //             try {
    //                 $alarm = Carbon::parse($inc->alarmdatetime);
    //                 $ended = Carbon::parse($inc->dateEnded);
    //                 $totalMinutes += $alarm->diffInMinutes($ended);
    //             } catch (\Exception $e) {}
    //         }
    //         $avgResponseTime = round($totalMinutes / $completedIncidents->count(), 1);
    //     }

    //     // Get personnel data
    //     $totalPersonnel = $allPersons->count();
    //     $availablePersonnel = $allPersons->where('status', 'available')->count();

    //     // Get equipment data
    //     $totalEquipment = 0;
    //     $availableEquipment = 0;

    //     try {
    //         $totalEquipment = DB::table('equipments')->count();
    //         $availableEquipment = DB::table('equipments')
    //             ->where('status', 'operational')
    //             ->orWhereNull('status')
    //             ->count();
    //     } catch (\Exception $e) {
    //         $totalEquipment = 20;
    //         $availableEquipment = 12;
    //     }

    //     // Calculate risk score
    //     $riskScore = $this->calculateRiskScore($incidents, $fireIncidents, $days);

    //     // Identify risk factors
    //     $riskFactors = $this->identifyRiskFactors($incidents, $incidentsByType, $peakHour, $avgResponseTime);

    //     return [
    //         'location_name' => $locationName,
    //         'location_id' => $locationId,
    //         'station_id' => $stationId,
    //         'days_analyzed' => $days,
    //         'total_incidents' => $totalIncidents,
    //         'fire_incidents' => $fireIncidents,
    //         'incidents_by_type' => $incidentsByType,
    //         'hourly_data' => $hourlyData,
    //         'daily_data' => $dailyData,
    //         'peak_hour' => $peakHour,
    //         'avg_response_time' => $avgResponseTime,
    //         'total_personnel' => $totalPersonnel,
    //         'available_personnel' => $availablePersonnel,
    //         'total_equipment' => $totalEquipment,
    //         'available_equipment' => $availableEquipment,
    //         'risk_score' => $riskScore['score'],
    //         'risk_level' => $riskScore['level'],
    //         'risk_factors' => $riskFactors
    //     ];
    // }
    /**
     * Fetch REAL data from your database tables
     */
    private function fetchRealTimeData($locationId, $days, $predictionScope = 'current_month', ?int $selectedBarangayId = null, $historicalWindow = '3', bool $generatePredictions = true)
    {
        $startDate = Carbon::now()->subDays($days);

        // Get user's station
        $user = Auth::user();

        if (! $user) {
            throw new \Exception('User not authenticated');
        }

        $stationOfficer = StationOfficer::where('user_id', $user->user_id)->first();

        if (! $stationOfficer) {
            throw new \Exception('No station assigned to this user');
        }

        $stationId = $stationOfficer->station_id;

        // Get station name
        $station = DB::table('stations')->where('station_id', $stationId)->first();
        $locationName = $station ? $station->station_name : 'My Station';
        $weatherContext = $this->fetchMonthlyWeatherContext($station, $days, $locationName) ?? [];

        // Resolve teams through the station link first, then through assigned officers for legacy rows.
        $allPersons = StationOfficer::where('station_id', $stationId)->get();
        $officerIds = $allPersons->pluck('station_officer_id')->filter()->all();
        $userIds = $allPersons->pluck('user_id')->filter()->all();
        $teamOfficerColumns = [
            'commander_driver', 'pumper', 'team_leader',
            'firefighter_officer_1', 'firefighter_officer_2', 'firefighter_officer_3', 'rescue_radio',
        ];
        $teamIds = Team::query()
            ->where('station_id', $stationId)
            ->orWhere(function ($query) use ($teamOfficerColumns, $officerIds, $userIds) {
                foreach ($teamOfficerColumns as $column) {
                    if (! empty($officerIds)) {
                        $query->orWhereIn($column, $officerIds);
                    }
                    if (! empty($userIds)) {
                        $query->orWhereIn($column, $userIds);
                    }
                }
            })
            ->pluck('team_id')
            ->unique()
            ->values()
            ->all();

        Log::info('Team IDs found: '.json_encode($teamIds));

        // Include incidents directly assigned to the station as well as legacy team-assigned incidents.
        try {
            $incidentDateExpression = Schema::hasColumn('incidents', 'occurred_at')
                ? 'COALESCE(occurred_at, created_at)'
                : 'created_at';
            $incidents = Incident::query()
                ->whereRaw("{$incidentDateExpression} >= ?", [$startDate])
                ->whereRaw('LOWER(status) IN (?, ?)', ['closed', 'completed'])
                ->where(function ($query) use ($stationId, $teamIds) {
                    $query->where('station_id', $stationId);
                    if (! empty($teamIds)) {
                        $query->orWhereIn('team_id', $teamIds);
                    }
                })
                ->get();
        } catch (\Exception $e) {
            Log::warning('Error fetching station-scoped incidents: '.$e->getMessage());
            $incidents = collect();
        }

        if ($selectedBarangayId) {
            $incidents = $incidents
                ->filter(fn ($incident) => (int) ($incident->barangay_id ?? 0) === (int) $selectedBarangayId)
                ->values();
        }

        Log::info('Found '.$incidents->count().' incidents for station '.$stationId);

        // Calculate statistics
        $totalIncidents = $incidents->count();

        // Incidents by type
        $incidentsByType = [];
        $fireIncidents = 0;

        $hasIncidentTypeColumn = Schema::hasColumn('incidents', 'incident_type_id');
        foreach ($incidents as $incident) {
            $type = $hasIncidentTypeColumn ? ($incident->incident_type_id ?? '0') : 'fire';
            $incidentsByType[$type] = ($incidentsByType[$type] ?? 0) + 1;
            if (! $hasIncidentTypeColumn || $type == '1') {

                $fireIncidents++;
            }
        }

        // Hourly distribution
        $hourlyData = array_fill(0, 24, 0);
        $dailyData = array_fill(0, 7, 0);

        foreach ($incidents as $incident) {
            $incidentDate = $incident->occurred_at ?: ($incident->created_at ?? null);
            if ($incidentDate) {
                try {
                    $date = Carbon::parse($incidentDate);
                    $hour = (int) $date->format('H');
                    $dayOfWeek = $date->dayOfWeek;
                    $hourlyData[$hour]++;
                    $dailyData[$dayOfWeek]++;
                } catch (\Exception $e) {
                }
            }
        }

        // Find peak hour
        $peakHour = array_search(max($hourlyData), $hourlyData);
        if ($peakHour === false || max($hourlyData) === 0) {
            $peakHour = null;
        }

        // Calculate average response time
        $avgResponseTime = null;
        $completedIncidents = $incidents->filter(function ($inc) {
            return isset($inc->dateEnded) && isset($inc->alarmdatetime);
        });

        if ($completedIncidents->count() > 0) {
            $totalMinutes = 0;
            foreach ($completedIncidents as $inc) {
                try {
                    $alarm = Carbon::parse($inc->alarmdatetime);
                    $ended = Carbon::parse($inc->dateEnded);
                    $totalMinutes += $alarm->diffInMinutes($ended);
                } catch (\Exception $e) {
                }
            }
            $avgResponseTime = round($totalMinutes / $completedIncidents->count(), 1);
        }

        // Get personnel data
        $totalPersonnel = $allPersons->count();
        $availablePersonnel = $allPersons->where('status', 'available')->count();

        // Get equipment data only for this station
        $totalEquipment = 0;
        $availableEquipment = 0;

        try {
            $equipmentAssignments = DB::table('equipments_to_stations')
                ->where('station_id', $stationId)
                ->get();

            $totalEquipment = (int) $equipmentAssignments->sum(function ($assignment) {
                return (int) ($assignment->available_quantity ?? 0)
                    + (int) ($assignment->damaged_quantity ?? 0)
                    + (int) ($assignment->maintenance_quantity ?? 0);
            });

            $availableEquipment = (int) $equipmentAssignments->sum('available_quantity');
        } catch (\Exception $e) {
            Log::warning('Error fetching station equipment stats: '.$e->getMessage());
            $totalEquipment = 0;
            $availableEquipment = 0;
        }

        $predictionContext = $this->buildPredictionContext($stationId, $teamIds, $predictionScope);
        $barangayPredictions = $generatePredictions
            ? $this->buildBarangayPredictionData($stationId, $predictionContext, $weatherContext)
            : [];
        $barangayPredictions = $generatePredictions
            ? $this->attachBarangayHistoricalBaselines($stationId, $barangayPredictions, $predictionContext)
            : [];
        $modelPrediction = $generatePredictions
            ? $this->buildModelPredictionData($stationId, $predictionContext, $weatherContext, $barangayPredictions)
            : null;
        $selectedBarangayPrediction = null;

        if ($selectedBarangayId) {
            $selectedBarangayPrediction = collect($barangayPredictions)
                ->first(fn ($item) => (int) ($item['barangay_id'] ?? 0) === $selectedBarangayId);

            if ($selectedBarangayPrediction) {
                $barangayPredictions = [$selectedBarangayPrediction];
                $locationName = ($station ? $station->station_name : 'My Station').' · '.($selectedBarangayPrediction['barangay_name'] ?? 'Selected barangay');
            }
        }

        $effectiveBarangayId = $selectedBarangayPrediction['barangay_id'] ?? $selectedBarangayId;
        $mitigationContext = $this->buildMitigationContext($stationId, $effectiveBarangayId);
        $recommendationContext = $this->buildRecommendationContext($stationId, $selectedBarangayPrediction, $barangayPredictions);
        $chartContext = $this->buildAnalyticsChartContext(
            $stationId,
            $teamIds,
            $predictionContext,
            $modelPrediction,
            $selectedBarangayPrediction,
            $barangayPredictions,
            $effectiveBarangayId,
            $historicalWindow
        );

        // Calculate risk score
        $riskScore = $this->calculateRiskScore($incidents, $fireIncidents, $days);
        $riskScore = $this->applyMitigationToRiskScore($riskScore, $mitigationContext);

        // Identify risk factors
        $riskFactors = $this->identifyRiskFactors($incidents, $incidentsByType, $peakHour, $avgResponseTime);

        return [
            'location_name' => $locationName,
            'location_id' => $locationId,
            'station_id' => $stationId,
            'barangay_id' => $selectedBarangayPrediction['barangay_id'] ?? $selectedBarangayId,
            'selected_barangay_prediction' => $selectedBarangayPrediction,
            'days_analyzed' => $days,
            'total_incidents' => $totalIncidents,
            'fire_incidents' => $fireIncidents,
            'incidents_by_type' => $incidentsByType,
            'hourly_data' => $hourlyData,
            'daily_data' => $dailyData,
            'peak_hour' => $peakHour,
            'avg_response_time' => $avgResponseTime,
            'total_personnel' => $totalPersonnel,
            'available_personnel' => $availablePersonnel,
            'total_equipment' => $totalEquipment,
            'available_equipment' => $availableEquipment,
            'risk_score' => $riskScore['score'],
            'risk_level' => $riskScore['level'],
            'risk_factors' => $riskFactors,
            'weather_context' => $weatherContext,
            'prediction_scope' => $predictionScope,
            'historical_window' => (string) $historicalWindow,
            'prediction_context' => $predictionContext,
            'model_prediction' => $modelPrediction,
            'barangay_predictions' => $barangayPredictions,
            'mitigation_context' => $mitigationContext,
            'recommendation_context' => $recommendationContext,
            'chart_context' => $chartContext,
        ];
    }

    private function buildMitigationContext(int $stationId, ?int $selectedBarangayId = null): array
    {
        $priorityWeights = [
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'critical' => 4,
        ];

        $checklists = AiActionPlanChecklist::query()
            ->where('station_id', $stationId)
            ->when($selectedBarangayId, fn ($query) => $query->where('barangay_id', $selectedBarangayId))
            ->with('items')
            ->get(['ai_action_plan_checklist_id', 'priority_level', 'status', 'target_date', 'confirmed_at', 'created_at']);

        $barangayIds = $selectedBarangayId
            ? [(int) $selectedBarangayId]
            : $this->resolveStationBarangays($stationId)
                ->pluck('barangay_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

        $mitigationActions = MitigationAction::query()
            ->when(! empty($barangayIds), fn ($query) => $query->whereIn('barangay_id', $barangayIds))
            ->when(empty($barangayIds), fn ($query) => $query->whereRaw('1 = 0'))
            ->get(['mitigation_action_id', 'ai_action_plan_checklist_id', 'status', 'priority_level', 'created_at', 'completed_at']);

        $totalWeight = 0;
        $confirmedWeight = 0;
        $pendingWeight = 0;
        $itemTotal = 0;
        $itemFollowed = 0;
        $itemNotFollowed = 0;

        foreach ($checklists as $checklist) {
            $weight = $priorityWeights[strtolower($checklist->priority_level ?? 'medium')] ?? 2;
            $totalWeight += $weight;

            if (strtolower($checklist->status ?? '') === 'confirmed') {
                $confirmedWeight += $weight;
            } elseif (in_array(strtolower($checklist->status ?? ''), ['pending', 'in_progress', 'submitted', 'rejected'], true)) {
                $pendingWeight += $weight;
            }

            $items = $checklist->items ?? collect();
            $itemTotal += $items->count();
            $itemFollowed += $items->where('status', 'followed')->count();
            $itemNotFollowed += $items->where('status', 'not_followed')->count();
        }

        $checklistCompletionRatio = $totalWeight > 0 ? round($confirmedWeight / $totalWeight, 4) : 0.0;
        $itemFollowedRatio = $itemTotal > 0 ? round($itemFollowed / $itemTotal, 4) : 0.0;
        $mitigationTotal = $mitigationActions->count();
        $completedMitigations = $mitigationActions->where('status', 'completed')->count();
        $blockedMitigations = $mitigationActions->where('status', 'blocked')->count();
        $mitigationCompletionRatio = $mitigationTotal > 0 ? round($completedMitigations / $mitigationTotal, 4) : 0.0;
        $avgCompletionDays = round((float) $mitigationActions
            ->where('status', 'completed')
            ->filter(fn ($action) => ! empty($action->created_at) && ! empty($action->completed_at))
            ->avg(function ($action) {
                return Carbon::parse($action->created_at)->diffInDays(Carbon::parse($action->completed_at));
            }), 1);
        $riskReductionPercent = round(min(
            35,
            (($checklistCompletionRatio * 18) + ($mitigationCompletionRatio * 12) + ($itemFollowedRatio * 5))
        ), 2);
        $feedbackSummary = $itemTotal > 0
            ? "{$itemFollowed} of {$itemTotal} checklist steps are marked followed, and {$completedMitigations} mitigation action(s) are completed."
            : 'No checklist item review feedback has been submitted yet.';

        return [
            'total_checklists' => $checklists->count(),
            'total_mitigation_actions' => $mitigationTotal,
            'confirmed_weight' => $confirmedWeight,
            'pending_weight' => $pendingWeight,
            'completion_ratio' => $checklistCompletionRatio,
            'mitigation_completion_ratio' => $mitigationCompletionRatio,
            'item_total' => $itemTotal,
            'item_followed' => $itemFollowed,
            'item_not_followed' => $itemNotFollowed,
            'item_followed_ratio' => $itemFollowedRatio,
            'completed_mitigations' => $completedMitigations,
            'blocked_mitigations' => $blockedMitigations,
            'avg_completion_days' => $avgCompletionDays > 0 ? $avgCompletionDays : null,
            'risk_reduction_percent' => $riskReductionPercent,
            'summary' => $totalWeight > 0
                ? "{$checklists->count()} mitigation checklist(s) tracked, with ".round($checklistCompletionRatio * 100, 1).'% weighted completion.'
                : 'No confirmed mitigation checklist actions yet.',
            'effectiveness_summary' => $mitigationTotal > 0
                ? "{$completedMitigations} of {$mitigationTotal} mitigation action(s) are completed".($avgCompletionDays > 0 ? " with an average completion time of {$avgCompletionDays} day(s)." : '.')
                : 'No mitigation action records are linked to the current station coverage yet.',
            'feedback_summary' => $feedbackSummary,
        ];
    }

    private function buildModelMetadataContext(): array
    {
        $summary = $this->forecastModelService->readMetadataSummary();
        $summary['prediction_runtime'] = [
            'python_binary' => config('services.ai_prediction.python_binary', 'python'),
            'prediction_script' => config('services.ai_prediction.prediction_script', 'capstone-ai/predict_event.py'),
        ];

        return $summary;
    }

    private function normalizeSelectedBarangayId(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '' || strtolower($trimmed) === 'all') {
                return null;
            }
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function scopeBarangayIdFromRawData(array $rawData): ?int
    {
        return $this->normalizeSelectedBarangayId(
            $rawData['barangay_id']
            ?? data_get($rawData, 'selected_barangay_prediction.barangay_id')
            ?? null
        );
    }

    private function scopeSignatureFromRawData(array $rawData): array
    {
        $predictionContext = is_array($rawData['prediction_context'] ?? null)
            ? $rawData['prediction_context']
            : [];

        return [
            'prediction_scope' => (string) ($rawData['prediction_scope'] ?? 'current_month'),
            'analysis_kind' => (string) ($rawData['analysis_kind'] ?? (($rawData['prediction_scope'] ?? 'current_month') === 'next_month' ? 'forecast' : 'actual_analysis')),
            'barangay_id' => $this->scopeBarangayIdFromRawData($rawData),
            'target_month_label' => (string) ($predictionContext['target_month_label'] ?? ''),
        ];
    }

    private function scopeSignatureFromRequest(Request $request): array
    {
        $predictionScope = (string) ($request->input('prediction_scope') ?: 'current_month');
        $targetMonth = $predictionScope === 'next_month'
            ? Carbon::now()->addMonthNoOverflow()->startOfMonth()
            : Carbon::now()->startOfMonth();

        return [
            'prediction_scope' => $predictionScope,
            'barangay_id' => $this->normalizeSelectedBarangayId($request->input('barangay_id')),
            'target_month_label' => $targetMonth->format('F Y'),
        ];
    }

    private function scopeMatchesAnalysis(Analysis $analysis, int $stationId, array $rawData): bool
    {
        if ((int) ($analysis->station_id ?? 0) !== $stationId) {
            return false;
        }

        $candidateData = is_array($analysis->analysis_data) ? $analysis->analysis_data : [];
        $candidateRaw = $candidateData['raw_data'] ?? [];
        $expected = $this->scopeSignatureFromRawData($rawData);
        $candidate = $this->scopeSignatureFromRawData($candidateRaw);

        return $candidate['prediction_scope'] === $expected['prediction_scope']
            && $candidate['analysis_kind'] === $expected['analysis_kind']
            && $candidate['barangay_id'] === $expected['barangay_id']
            && $candidate['target_month_label'] === $expected['target_month_label'];
    }

    private function findExistingScopedAnalysis(int $stationId, array $rawData): ?Analysis
    {
        return Analysis::query()
            ->where('station_id', $stationId)
            ->latest('updated_at')
            ->get()
            ->first(fn (Analysis $analysis) => $this->scopeMatchesAnalysis($analysis, $stationId, $rawData));
    }

    private function mergeCurrentModelMetadata(?array $existing): array
    {
        $current = $this->buildModelMetadataContext();

        if (! is_array($existing) || $existing === []) {
            return $current;
        }

        $merged = array_replace_recursive($current, $existing);

        if (empty($existing['learning_performance'])) {
            $merged['learning_performance'] = $current['learning_performance'] ?? null;
        }

        if (empty($existing['global_feature_importance'])) {
            $merged['global_feature_importance'] = $current['global_feature_importance'] ?? [];
        }

        if (empty($existing['metrics']['cross_val_roc_auc_scores'])) {
            $merged['metrics']['cross_val_roc_auc_scores'] = $current['metrics']['cross_val_roc_auc_scores'] ?? [];
        }

        if (empty($existing['metrics']['classification_report'])) {
            $merged['metrics']['classification_report'] = $current['metrics']['classification_report'] ?? [];
        }

        return $merged;
    }

    private function buildScopedModelMetadataContext(int $stationId, ?int $selectedBarangayId = null, ?array $existing = null): array
    {
        $metadata = $this->mergeCurrentModelMetadata($existing);
        $learning = is_array($metadata['learning_performance'] ?? null)
            ? $metadata['learning_performance']
            : [];

        $actualVsPredictedLabels = collect($learning['actual_vs_predicted']['labels'] ?? [])
            ->filter(fn ($label) => trim((string) $label) !== '')
            ->values()
            ->all();
        $residualLabels = collect($learning['residual_pattern']['labels'] ?? [])
            ->filter(fn ($label) => trim((string) $label) !== '')
            ->values()
            ->all();

        if (! empty($actualVsPredictedLabels) && ! empty($residualLabels)) {
            $metadata['learning_performance'] = $learning;

            return $metadata;
        }

        $operationalFallback = $this->buildOperationalPredictionEvaluationSeries($stationId, $selectedBarangayId);
        if ($operationalFallback === null) {
            $metadata['learning_performance'] = $learning;

            return $metadata;
        }

        $existingWarning = trim((string) ($learning['warning'] ?? ''));
        $fallbackWarning = 'Showing saved forecast-run comparisons for the current scope until the row-level training-evaluation cache is available.';

        $learning['actual_vs_predicted'] = $learning['actual_vs_predicted'] ?? $operationalFallback['actual_vs_predicted'];
        if (empty($actualVsPredictedLabels)) {
            $learning['actual_vs_predicted'] = $operationalFallback['actual_vs_predicted'];
        }

        $learning['residual_pattern'] = $learning['residual_pattern'] ?? $operationalFallback['residual_pattern'];
        if (empty($residualLabels)) {
            $learning['residual_pattern'] = $operationalFallback['residual_pattern'];
        }

        $learning['partial'] = true;
        $learning['warning'] = trim($existingWarning.' '.$fallbackWarning);

        $metadata['learning_performance'] = $learning;

        return $metadata;
    }

    private function mergeLiveChartContext(int $stationId, ?array $existing, ?int $selectedBarangayId = null): array
    {
        $chartContext = is_array($existing) ? $existing : [];
        $chartContext['ml_trend_analysis'] = $this->buildMlTrendAnalysisSeries($stationId, $selectedBarangayId);

        return $chartContext;
    }

    private function buildWorkflowContext(array $analysis, array $rawData): array
    {
        $recommendationCount = count($analysis['recommendations'] ?? []);
        $checklistCount = count($analysis['action_plan_checklists'] ?? []);
        $selectedBarangay = $rawData['selected_barangay_prediction']['barangay_name'] ?? null;
        $topHotspot = collect($rawData['barangay_predictions'] ?? [])
            ->sortByDesc(fn ($item) => (float) ($item['probability_percent'] ?? 0))
            ->first();

        return [
            'prediction_stage' => 'Prediction',
            'recommendation_stage' => 'Recommendation',
            'mitigation_stage' => 'Mitigation Action Plan',
            'recommendation_count' => $recommendationCount,
            'generated_checklist_count' => $checklistCount,
            'focus_barangay' => $selectedBarangay ?: ($topHotspot['barangay_name'] ?? null),
            'focus_probability_percent' => $selectedBarangay
                ? ($rawData['selected_barangay_prediction']['probability_percent'] ?? null)
                : ($topHotspot['probability_percent'] ?? null),
            'next_actions' => [
                'Review the recommendation evidence and hotspot drivers.',
                'Confirm or revise the generated action-plan checklist.',
                'Convert approved checklist items into mitigation actions with owners and target dates.',
                'Track completion outcomes so the next forecast reflects verified follow-through.',
            ],
        ];
    }

    private function buildRecommendationContext(int $stationId, ?array $selectedBarangayPrediction, array $barangayPredictions): array
    {
        $focusBarangayIds = collect();

        if (! empty($selectedBarangayPrediction['barangay_id'])) {
            $focusBarangayIds->push((int) $selectedBarangayPrediction['barangay_id']);
        } else {
            $focusBarangayIds = collect($barangayPredictions)
                ->sortByDesc(fn ($item) => (float) ($item['probability_percent'] ?? 0))
                ->pluck('barangay_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
        }

        if ($focusBarangayIds->isEmpty()) {
            return [
                'focus_barangays' => [],
                'focus_findings' => [],
                'summary' => null,
            ];
        }

        $records = DB::table('risk_areas')
            ->join('barangay_halls', 'risk_areas.barangay_hall_id', '=', 'barangay_halls.barangay_hall_id')
            ->join('barangays', 'barangay_halls.barangay_id', '=', 'barangays.barangay_id')
            ->where('risk_areas.station_id', $stationId)
            ->whereIn('barangay_halls.barangay_id', $focusBarangayIds->all())
            ->where(function ($query) {
                $query->where('risk_areas.review_status', 'approved')
                    ->orWhere('risk_areas.status', 'verified');
            })
            ->where(function ($query) {
                $query->whereNull('risk_areas.review_type')
                    ->orWhere('risk_areas.review_type', '!=', 'delete');
            })
            ->select(
                'risk_areas.risk_area_id',
                'barangays.barangay_id',
                'barangays.barangay_name',
                'risk_areas.entry_type',
                'risk_areas.hazard_category',
                'risk_areas.risk_type',
                'risk_areas.street_or_sitio',
                'risk_areas.condition_label',
                'risk_areas.assessment_level',
                'risk_areas.road_accessibility',
                'risk_areas.near_hydrant',
                'risk_areas.near_water_source',
                'risk_areas.blocked_hydrant',
                'risk_areas.population_density_level',
                'risk_areas.building_density_level',
                'risk_areas.estimated_affected_households',
                'risk_areas.informal_settlement',
                'risk_areas.cooking_fire_prone_activity',
                'risk_areas.combustible_materials_present',
                'risk_areas.poor_waste_disposal',
                'risk_areas.previous_fire_prone_location',
                'risk_areas.assessment_description',
                'risk_areas.remarks',
                'risk_areas.created_at'
            )
            ->orderByRaw("
                CASE risk_areas.assessment_level
                    WHEN 'high' THEN 1
                    WHEN 'moderate' THEN 2
                    WHEN 'medium' THEN 2
                    ELSE 3
                END
            ")
            ->orderByDesc('risk_areas.created_at')
            ->get();

        // Keep the prompt compact without allowing the busiest barangay to
        // consume the evidence allowance for every other covered barangay.
        $records = $records
            ->groupBy('barangay_id')
            ->flatMap(fn ($barangayRecords) => $barangayRecords->take(5))
            ->values();

        $focusFindings = $records->map(function ($record) {
            $typeLabel = $record->entry_type === 'hazard'
                ? ($record->hazard_category ?: 'hazard')
                : ($record->risk_type ?: 'risk');

            $detailParts = collect([
                $record->condition_label,
                $record->street_or_sitio ? "at {$record->street_or_sitio}" : null,
                $record->road_accessibility ? "road access: {$record->road_accessibility}" : null,
                $record->population_density_level ? "population density: {$record->population_density_level}" : null,
                $record->building_density_level ? "building density: {$record->building_density_level}" : null,
                $record->blocked_hydrant ? 'blocked hydrant nearby' : null,
                ! $record->near_hydrant ? 'no nearby hydrant' : null,
                $record->informal_settlement ? 'informal settlement exposure' : null,
                $record->cooking_fire_prone_activity ? 'cooking/fire-prone activity present' : null,
                $record->combustible_materials_present ? 'combustible materials present' : null,
                $record->poor_waste_disposal ? 'poor waste disposal observed' : null,
                $record->previous_fire_prone_location ? 'previous fire-prone location' : null,
                $record->estimated_affected_households ? "{$record->estimated_affected_households} estimated households exposed" : null,
            ])->filter()->values();

            return [
                'risk_area_id' => (int) $record->risk_area_id,
                'barangay_id' => (int) $record->barangay_id,
                'barangay_name' => $record->barangay_name,
                'entry_type' => $record->entry_type,
                'type_label' => $typeLabel,
                'condition_label' => $record->condition_label,
                'assessment_level' => $record->assessment_level,
                'street_or_sitio' => $record->street_or_sitio,
                'summary' => trim($record->barangay_name.': '.ucfirst($typeLabel).($detailParts->isNotEmpty() ? ' — '.$detailParts->implode(', ') : '')),
                'notes' => collect([$record->assessment_description, $record->remarks])->filter()->implode(' '),
            ];
        })->values()->all();

        $summary = ! empty($focusFindings)
            ? 'Localized field findings available for '.collect($focusFindings)->pluck('barangay_name')->unique()->implode(', ').'.'
            : null;

        return [
            'focus_barangays' => $focusBarangayIds->all(),
            'focus_findings' => $focusFindings,
            'summary' => $summary,
        ];
    }

    private function applyMitigationToRiskScore(array $riskScore, array $mitigationContext): array
    {
        $baseScore = (float) ($riskScore['score'] ?? 0);
        $reductionPercent = (float) ($mitigationContext['risk_reduction_percent'] ?? 0);
        $reductionPoints = round($baseScore * ($reductionPercent / 100), 1);
        $adjustedScore = max(0, round($baseScore - $reductionPoints));

        $level = 'Low';
        if ($adjustedScore >= 70) {
            $level = 'Critical';
        } elseif ($adjustedScore >= 50) {
            $level = 'High';
        } elseif ($adjustedScore >= 30) {
            $level = 'Medium';
        }

        return [
            'score' => $adjustedScore,
            'level' => $level,
            'base_score' => $baseScore,
            'mitigation_reduction_points' => $reductionPoints,
        ];
    }

    private function buildModelPredictionData($stationId, array $predictionContext, array $weatherContext = [], array $barangayPredictions = []): ?array
    {
        try {
            if (empty($barangayPredictions)) {
                return null;
            }

            $probabilities = collect($barangayPredictions)->pluck('probability_percent')->filter(fn ($value) => is_numeric($value));
            $predictedIncidentCount = (int) round(collect($barangayPredictions)->sum(function ($item) {
                return (float) ($item['predicted_incident_count'] ?? 0);
            }));
            $incidentCalibration = $this->calibrateStationIncidentForecast(
                (int) $stationId,
                $predictionContext,
                $predictedIncidentCount
            );
            $calibratedIncidentCount = (int) $incidentCalibration['calibrated_midpoint'];
            $predictedDamage = round((float) collect($barangayPredictions)->sum(function ($item) {
                return (float) ($item['predicted_estimated_damage'] ?? 0);
            }), 2);

            $maxProbability = (float) ($probabilities->max() ?? 0);
            $averageProbability = (float) ($probabilities->avg() ?? 0);
            $stationProbability = round(($maxProbability * 0.55) + ($averageProbability * 0.45), 2);
            $riskIncidentCount = ($predictionContext['scope'] ?? 'current_month') === 'current_month'
                ? max($calibratedIncidentCount, (int) ($predictionContext['current_month_fire_incidents'] ?? 0))
                : $calibratedIncidentCount;

            $riskLevel = 'low';
            if ($stationProbability >= 65 || $riskIncidentCount >= 6 || $predictedDamage >= 500000) {
                $riskLevel = 'high';
            } elseif ($stationProbability >= 35 || $riskIncidentCount >= 3 || $predictedDamage >= 150000) {
                $riskLevel = 'moderate';
            }

            $driverSummaries = collect($barangayPredictions)
                ->take(3)
                ->flatMap(function ($item) {
                    $barangayName = $item['barangay_name'] ?? 'Unknown barangay';

                    return collect($item['drivers'] ?? [])
                        ->take(2)
                        ->map(function ($driver) use ($barangayName) {
                            $summary = $driver['summary'] ?? null;

                            return $summary ? "{$barangayName}: {$summary}" : null;
                        });
                })
                ->filter()
                ->values()
                ->all();

            $watchItems = collect($barangayPredictions)
                ->flatMap(fn ($item) => $item['watch_items'] ?? [])
                ->filter()
                ->unique()
                ->take(6)
                ->values()
                ->all();

            $confidenceBand = 'low';
            if ($stationProbability >= 75 || $stationProbability <= 20) {
                $confidenceBand = 'high';
            } elseif ($stationProbability >= 55 || $stationProbability <= 30) {
                $confidenceBand = 'medium';
            }

            $decoded = [
                'prediction' => $riskLevel === 'high' ? 1 : 0,
                'probability' => round($stationProbability / 100, 4),
                'probability_percent' => $stationProbability,
                'risk_level' => $riskLevel,
                'confidence_band' => $confidenceBand,
                'target' => 'station_monthly_fire_pressure',
                'raw_model_predicted_incident_count' => $predictedIncidentCount,
                'calibrated_predicted_incident_count' => $calibratedIncidentCount,
                'predicted_incident_count' => $calibratedIncidentCount,
                'incident_count_range' => $incidentCalibration['range'],
                'forecast_calibration' => $incidentCalibration,
                'predicted_estimated_damage' => $predictedDamage,
                'drivers' => array_merge(
                    array_map(fn ($summary) => ['summary' => $summary], $driverSummaries),
                    [[
                        'summary' => "Incident count calibrated from a raw barangay sum of {$predictedIncidentCount} to {$calibratedIncidentCount}, using observed pace and a "
                            .number_format((float) $incidentCalibration['historical_same_month_average'], 2)
                            .' same-month historical average.',
                    ]]
                ),
                'watch_items' => $watchItems,
            ];
            $decoded = $this->applyForecastIncidentCountSemantics(
                $decoded,
                (int) ($predictionContext['current_month_fire_incidents'] ?? 0),
                ($predictionContext['scope'] ?? 'current_month') === 'current_month'
            );

            return [
                'input' => [
                    'station_id' => $stationId,
                    'aggregation' => 'barangay_monthly_forecasts',
                    'barangay_count' => count($barangayPredictions),
                    'target_month_label' => $predictionContext['target_month_label'] ?? null,
                    'weather_context' => $weatherContext,
                ],
                'output' => $decoded,
            ];
        } catch (\Throwable $e) {
            Log::warning('Unable to build model prediction data: '.$e->getMessage());

            return null;
        }
    }

    private function calibrateStationIncidentForecast(int $stationId, array $predictionContext, int $rawModelTotal): array
    {
        try {
            $targetMonth = Carbon::createFromFormat(
                'F Y',
                (string) ($predictionContext['target_month_label'] ?? Carbon::now()->format('F Y'))
            )->startOfMonth();
        } catch (\Throwable) {
            $targetMonth = Carbon::now()->startOfMonth();
        }

        $comparisonYears = collect(range($targetMonth->year - 3, $targetMonth->year - 1));
        $barangayIds = $this->resolveStationBarangays($stationId)
            ->pluck('barangay_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        $totalsByYear = collect();

        if (! empty($barangayIds)) {
            $totalsByYear = DB::table('historical_fire_incidents')
                ->whereIn('barangay_id', $barangayIds)
                ->whereMonth('incident_date', $targetMonth->month)
                ->whereIn(DB::raw('EXTRACT(YEAR FROM incident_date)::int'), $comparisonYears->all())
                ->get(['incident_date', 'incident_count'])
                ->groupBy(fn ($row) => (int) Carbon::parse($row->incident_date)->year)
                ->map(fn ($rows) => (int) $rows->sum(fn ($row) => max(0, (int) ($row->incident_count ?? 1))));
        }

        $historicalTotals = $comparisonYears
            ->map(fn ($year) => (int) ($totalsByYear->get($year, 0)))
            ->all();
        $observedCurrentMonth = (int) ($predictionContext['current_month_fire_incidents'] ?? 0);
        $paceMonth = Carbon::now();

        return ForecastIncidentCalibration::calibrate(
            $rawModelTotal,
            $observedCurrentMonth,
            ($predictionContext['scope'] ?? 'current_month') === 'current_month',
            $paceMonth->daysInMonth,
            $paceMonth->day,
            $historicalTotals
        );
    }

    private function applyStructuredIncidentCountSemantics(array $analysis, array $rawData): array
    {
        $prediction = is_array($analysis['predictions'] ?? null) ? $analysis['predictions'] : [];
        $modelOutput = data_get($rawData, 'model_prediction.output', []);
        $isCurrentMonth = ($rawData['prediction_scope'] ?? 'current_month') === 'current_month';
        $selectedBarangay = $rawData['selected_barangay_prediction'] ?? null;
        $observedToDate = is_array($selectedBarangay)
            ? (int) data_get($selectedBarangay, 'input.current_month_fire_incidents', 0)
            : (int) data_get($rawData, 'prediction_context.current_month_fire_incidents', 0);

        $authoritativeForecast = is_array($selectedBarangay) && ! empty($selectedBarangay)
            ? $selectedBarangay
            : (is_array($modelOutput) && ! empty($modelOutput) ? $modelOutput : $prediction);
        $normalized = $this->applyForecastIncidentCountSemantics(
            $authoritativeForecast,
            $observedToDate,
            $isCurrentMonth
        );

        $prediction = array_merge($prediction, [
            'raw_model_predicted_incident_count' => $normalized['raw_model_predicted_incident_count'],
            'calibrated_predicted_incident_count' => $normalized['calibrated_predicted_incident_count'],
            'observed_fire_incidents_to_date' => $normalized['observed_fire_incidents_to_date'],
            'predicted_additional_fire_incidents' => $normalized['predicted_additional_fire_incidents'],
            'expected_month_total_fire_incidents' => $normalized['expected_month_total_fire_incidents'],
            'incident_count_range' => $normalized['incident_count_range'],
            'predicted_additional_incident_range' => $normalized['predicted_additional_incident_range'],
            'forecast_calibration' => $normalized['forecast_calibration'] ?? null,
            'predicted_fire_incidents' => $normalized['expected_month_total_fire_incidents'],
            'forecast_count_basis' => $normalized['forecast_count_basis'],
        ]);

        $targetLabel = (string) data_get($rawData, 'prediction_context.target_month_label', 'the forecast month');
        $outlook = (string) ($prediction['outlook'] ?? $analysis['risk_level'] ?? 'Unknown');
        $prediction['ai_narrative_original'] = $prediction['summary'] ?? null;
        $range = $normalized['incident_count_range'];
        $rangeLabel = $range['lower'] === $range['upper']
            ? (string) $range['lower']
            : "{$range['lower']}-{$range['upper']}";
        $prediction['incident_count_summary'] = $isCurrentMonth
            ? "{$normalized['observed_fire_incidents_to_date']} fire incident(s) have already been observed in {$targetLabel}. The calibrated planning range is {$rangeLabel} total incident(s), with {$normalized['expected_month_total_fire_incidents']} as the midpoint estimate."
            : "The calibrated planning range is {$rangeLabel} fire incident(s) for {$targetLabel}, with {$normalized['expected_month_total_fire_incidents']} as the midpoint estimate. The forecast month has not started, so there are no observed incidents in that window yet.";
        $prediction['summary'] = $prediction['incident_count_summary']." Risk outlook: {$outlook}. Review probability, confidence, and the listed drivers together before operational decisions.";

        $analysis['predictions'] = $prediction;

        return $analysis;
    }

    private function buildBarangayPredictionData(int $stationId, array $predictionContext, array $weatherContext = []): array
    {
        try {
            $modelReady = $this->forecastModelService->ensureReady();

            $targetMonth = ! empty($predictionContext['target_month_label'])
                ? Carbon::createFromFormat('F Y', $predictionContext['target_month_label'])->startOfMonth()
                : Carbon::now()->startOfMonth();

            $stationHistoricalRow = $this->buildStationHistoricalProfile($stationId, $targetMonth);
            $recentFireSignals = $this->buildRecentFireMomentumByBarangay($stationId);

            $barangays = $this->resolveStationBarangays($stationId);
            if ($barangays->isEmpty()) {
                return [];
            }

            $requests = [];

            foreach ($barangays as $barangay) {
                $payload = $this->buildBarangayPredictionPayload(
                    $stationId,
                    $barangay,
                    $stationHistoricalRow,
                    $targetMonth,
                    $weatherContext,
                    $recentFireSignals[(int) $barangay->barangay_id] ?? [],
                    $recentFireSignals[0] ?? [],
                    max(1, $barangays->count())
                );

                if (! $payload) {
                    continue;
                }

                $requests[] = [
                    'payload' => $payload,
                    'barangay' => $barangay,
                ];
            }

            $modelResults = [];
            if ($modelReady && ! empty($requests)) {
                try {
                    $modelResults = $this->barangayPredictionService->predictMany(array_column($requests, 'payload'));
                } catch (\Throwable $e) {
                    // A bulk model/runtime failure must not erase the barangay
                    // ranking. Each request below will use the deterministic,
                    // evidence-based fallback instead.
                    Log::warning('Bulk barangay prediction failed; using per-barangay fallback.', [
                        'station_id' => $stationId,
                        'barangay_count' => count($requests),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            $forecasts = [];

            foreach ($requests as $index => $requestData) {
                $payload = $requestData['payload'];
                $barangay = $requestData['barangay'];
                $decoded = $modelResults[$index] ?? null;

                if (! is_array($decoded)) {
                    $decoded = $this->buildBarangayPredictionFallback($payload);
                    $decoded['prediction_source'] = 'deterministic_fallback';
                } else {
                    $decoded['prediction_source'] = 'trained_model';
                }

                $decoded = $this->applyForecastIncidentCountSemantics(
                    $decoded,
                    (int) ($payload['current_month_fire_incidents'] ?? 0),
                    $targetMonth->isSameMonth(Carbon::now())
                );

                $forecasts[] = array_merge($decoded, [
                    'station_id' => $stationId,
                    'barangay_id' => (int) $barangay->barangay_id,
                    'barangay_name' => $barangay->barangay_name,
                    'city_name' => $barangay->city_name ?? null,
                    'input' => $payload,
                ]);
            }

            usort($forecasts, function (array $left, array $right) {
                return ($right['probability_percent'] ?? 0) <=> ($left['probability_percent'] ?? 0);
            });

            return $forecasts;
        } catch (\Throwable $e) {
            Log::warning('Unable to build barangay prediction data: '.$e->getMessage());

            return [];
        }
    }

    private function buildBarangayPredictionFallback(array $payload): array
    {
        $incidentCount = max(0, (int) ($payload['incident_count'] ?? 0));
        $temperature = (float) ($payload['average_temperature'] ?? 27);
        $humidity = (float) ($payload['average_humidity'] ?? 70);
        $wind = (float) ($payload['average_wind_speed'] ?? 5);
        $riskPressure = (float) ($payload['risk_area_pressure_signal'] ?? 0);
        $recentFireMomentum = (float) ($payload['recent_fire_momentum_signal'] ?? 0);
        $stationFireMomentum = (float) ($payload['station_recent_fire_momentum_signal'] ?? 0);
        $mitigationReadiness = (float) ($payload['mitigation_readiness_signal'] ?? 0);
        $completionRatio = (float) ($payload['mitigation_completion_ratio'] ?? 0);

        $score = 12
            + ($incidentCount * 7)
            + max(0, $temperature - 27) * 1.8
            + max(0, 65 - $humidity) * 0.35
            + ($wind * 0.45)
            + ($riskPressure * 2.4)
            + ($recentFireMomentum * 2.2)
            + ($stationFireMomentum * 0.55)
            - ($mitigationReadiness * 3)
            - ($completionRatio * 12);
        $probability = round(max(5, min(95, $score)), 2);
        $riskLevel = $probability >= 65 ? 'high' : ($probability >= 35 ? 'moderate' : 'low');
        $predictedIncidents = max(0, (int) round($incidentCount * (0.65 + ($probability / 100))));

        return [
            'prediction' => $riskLevel === 'high' ? 1 : 0,
            'probability' => round($probability / 100, 4),
            'probability_percent' => $probability,
            'risk_level' => $riskLevel,
            'confidence_band' => 'fallback',
            'target' => 'high_risk_barangay_month',
            'predicted_incident_count' => $predictedIncidents,
            'predicted_fire_incidents' => $predictedIncidents,
            'drivers' => [
                ['summary' => "{$incidentCount} historical incident(s) contribute to the barangay baseline."],
                ['summary' => (int) ($payload['current_month_fire_incidents'] ?? 0).' completed fire incident(s) this month contribute to recent fire momentum.'],
                ['summary' => (int) ($payload['station_current_month_fire_incidents'] ?? 0).' station-wide completed fire incident(s) influence the common monthly baseline.'],
                ['summary' => 'Verified risk-area pressure and current weather signals were included.'],
                ['summary' => 'Completed mitigation and checklist readiness reduce the fallback score.'],
            ],
            'watch_items' => ['incident activity', 'risk-area conditions', 'mitigation readiness'],
        ];
    }

    private function applyForecastIncidentCountSemantics(array $forecast, int $observedToDate, bool $isCurrentMonth): array
    {
        $rawModelCount = max(0, (int) round((float) (
            $forecast['raw_model_predicted_incident_count']
                ?? $forecast['predicted_incident_count']
                ?? $forecast['predicted_fire_incidents']
                ?? 0
        )));
        $calibratedCount = max(0, (int) round((float) (
            $forecast['calibrated_predicted_incident_count']
                ?? $forecast['predicted_incident_count']
                ?? $rawModelCount
        )));
        $observed = $isCurrentMonth ? max(0, $observedToDate) : 0;
        $expectedTotal = $isCurrentMonth ? max($observed, $calibratedCount) : $calibratedCount;
        $predictedAdditional = $isCurrentMonth
            ? max(0, $expectedTotal - $observed)
            : $expectedTotal;
        $providedRange = is_array($forecast['incident_count_range'] ?? null)
            ? $forecast['incident_count_range']
            : [];
        $rangeLower = max($isCurrentMonth ? $observed : 0, (int) ($providedRange['lower'] ?? $expectedTotal));
        $rangeUpper = max($rangeLower, (int) ($providedRange['upper'] ?? $expectedTotal));

        return array_merge($forecast, [
            'raw_model_predicted_incident_count' => $rawModelCount,
            'calibrated_predicted_incident_count' => $expectedTotal,
            'observed_fire_incidents_to_date' => $observed,
            'predicted_additional_fire_incidents' => $predictedAdditional,
            'predicted_additional_incident_range' => [
                'lower' => $isCurrentMonth ? max(0, $rangeLower - $observed) : $rangeLower,
                'upper' => $isCurrentMonth ? max(0, $rangeUpper - $observed) : $rangeUpper,
            ],
            'incident_count_range' => ['lower' => $rangeLower, 'upper' => $rangeUpper],
            'expected_month_total_fire_incidents' => $expectedTotal,
            // Keep the legacy field as the expected total so existing charts cannot fall below actuals.
            'predicted_incident_count' => $expectedTotal,
            'predicted_fire_incidents' => $expectedTotal,
            'forecast_count_basis' => $isCurrentMonth ? 'current_month_observed_plus_remaining' : 'next_month_forecast',
        ]);
    }

    private function resolveStationBarangays(int $stationId)
    {
        $coverageBarangays = DB::table('station_barangay_coverage')
            ->join('barangays', 'station_barangay_coverage.barangay_id', '=', 'barangays.barangay_id')
            ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
            ->where('station_barangay_coverage.station_id', $stationId)
            ->where('station_barangay_coverage.is_active', true)
            ->select('barangays.barangay_id', 'barangays.barangay_name', 'cities.city_name', 'cities.city_id')
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
            ->select('barangays.barangay_id', 'barangays.barangay_name', 'cities.city_name', 'cities.city_id')
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
                    ->select('barangays.barangay_id', 'barangays.barangay_name', 'cities.city_name', 'cities.city_id')
                    ->get()
            );
        }

        $riskAreaBarangays = DB::table('risk_areas')
            ->join('barangay_halls', 'risk_areas.barangay_hall_id', '=', 'barangay_halls.barangay_hall_id')
            ->join('barangays', 'barangay_halls.barangay_id', '=', 'barangays.barangay_id')
            ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
            ->where('risk_areas.station_id', $stationId)
            ->select('barangays.barangay_id', 'barangays.barangay_name', 'cities.city_name', 'cities.city_id')
            ->get();

        $barangays = $barangays->merge($riskAreaBarangays);
        $inferredCityIds = $cityIds
            ->merge($riskAreaBarangays->pluck('city_id')->filter())
            ->unique()
            ->values();

        if ($inferredCityIds->isNotEmpty()) {
            $barangays = $barangays->merge(
                DB::table('barangays')
                    ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
                    ->whereIn('barangays.city_id', $inferredCityIds)
                    ->select('barangays.barangay_id', 'barangays.barangay_name', 'cities.city_name', 'cities.city_id')
                    ->get()
            );
        }

        if ($barangays->isEmpty()) {
            $urbiztondoBarangays = DB::table('barangays')
                ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
                ->where('cities.city_name', 'Urbiztondo')
                ->select('barangays.barangay_id', 'barangays.barangay_name', 'cities.city_name', 'cities.city_id')
                ->get();

            $barangays = $urbiztondoBarangays->isNotEmpty()
                ? $urbiztondoBarangays
                : DB::table('barangays')
                    ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
                    ->select('barangays.barangay_id', 'barangays.barangay_name', 'cities.city_name', 'cities.city_id')
                    ->limit(10)
                    ->get();
        }

        return $barangays
            ->unique('barangay_id')
            ->values();
    }

    private function buildStationHistoricalProfile(int $stationId, Carbon $targetMonth): object
    {
        $barangayIds = $this->resolveStationBarangays($stationId)
            ->pluck('barangay_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($barangayIds)) {
            return (object) [
                'average_temperature' => null,
                'season' => $this->deriveSeasonFromMonth($targetMonth->month),
                'peak_hours' => 'evening',
                'type_incident' => 'electrical fire',
                'materials' => 'mixed',
                'occupancy' => 'residential',
                'accesibility' => 'moderate access',
                'near_water' => 0,
            ];
        }

        $historicalRows = DB::table('historical_fire_incidents')
            ->whereIn('barangay_id', $barangayIds)
            ->whereYear('incident_date', '>=', $targetMonth->copy()->subYears(3)->year)
            ->orderByDesc('incident_date')
            ->get(['incident_date', 'incident_time', 'incident_count', 'temperature', 'fire_cause']);

        $sameMonthRows = $historicalRows->filter(function ($row) use ($targetMonth) {
            try {
                return ! empty($row->incident_date) && Carbon::parse($row->incident_date)->month === $targetMonth->month;
            } catch (\Throwable $e) {
                return false;
            }
        });

        $sourceRows = $sameMonthRows->isNotEmpty() ? $sameMonthRows : $historicalRows;

        $nearWater = DB::table('risk_areas')
            ->where('station_id', $stationId)
            ->where(function ($query) {
                $query->where('near_water_source', true)
                    ->orWhere('near_hydrant', true);
            })
            ->exists() ? 1 : 0;

        return (object) [
            'average_temperature' => $this->averageNonNullValues($sourceRows->pluck('temperature')->all()),
            'season' => $this->deriveSeasonFromMonth($targetMonth->month),
            'peak_hours' => $this->deriveBarangayPeakHours($sourceRows, 'evening'),
            'type_incident' => $this->deriveBarangayModeValue($sourceRows, 'fire_cause', 'electrical fire'),
            'materials' => 'mixed',
            'occupancy' => 'residential',
            'accesibility' => 'moderate access',
            'near_water' => $nearWater,
        ];
    }

    private function buildBarangayPredictionPayload(
        int $stationId,
        object $barangay,
        object $stationHistoricalRow,
        Carbon $targetMonth,
        array $weatherContext = [],
        array $recentFireSignal = [],
        array $stationRecentFireSignal = [],
        int $coveredBarangayCount = 1
    ): ?array {
        $barangayIncidents = DB::table('historical_fire_incidents')
            ->where('barangay_id', $barangay->barangay_id)
            ->whereYear('incident_date', '>=', $targetMonth->copy()->subYears(3)->year)
            ->orderByDesc('incident_date')
            ->get();

        $sameMonthIncidents = $barangayIncidents->filter(function ($incident) use ($targetMonth) {
            if (empty($incident->incident_date)) {
                return false;
            }

            try {
                return Carbon::parse($incident->incident_date)->month === $targetMonth->month;
            } catch (\Throwable $e) {
                return false;
            }
        });

        $recentIncidents = $barangayIncidents->filter(function ($incident) use ($targetMonth) {
            if (empty($incident->incident_date)) {
                return false;
            }

            try {
                return Carbon::parse($incident->incident_date)->greaterThanOrEqualTo($targetMonth->copy()->subMonths(12));
            } catch (\Throwable $e) {
                return false;
            }
        });

        $incidentCount = (int) max($sameMonthIncidents->count(), $recentIncidents->count());
        $averageTemperature = isset($weatherContext['avg_temp_c']) && $weatherContext['avg_temp_c'] !== null
            ? (float) $weatherContext['avg_temp_c']
            : ($sameMonthIncidents->avg('temperature') ?: $stationHistoricalRow->average_temperature);
        $averageHumidity = isset($weatherContext['humidity']) && $weatherContext['humidity'] !== null
            ? (float) $weatherContext['humidity']
            : ($sameMonthIncidents->avg('humidity') ?: null);
        $averageWindSpeed = isset($weatherContext['wind_kph']) && $weatherContext['wind_kph'] !== null
            ? (float) $weatherContext['wind_kph']
            : ($sameMonthIncidents->avg('wind_speed') ?: null);
        $weatherCondition = $weatherContext['condition']
            ?? $weatherContext['summary']
            ?? ($sameMonthIncidents->pluck('weather_condition')->filter()->countBy()->sortDesc()->keys()->first() ?: 'unknown');

        $historicalRiskLevel = $incidentCount >= 8
            ? 'high'
            : ($incidentCount >= 4 ? 'medium' : 'low');

        $density = $incidentCount >= 8
            ? 'high'
            : ($incidentCount >= 3 ? 'medium' : 'low');

        $peakHours = $this->deriveBarangayPeakHours($barangayIncidents, $stationHistoricalRow->peak_hours ?? 'evening');
        $typeIncident = $this->deriveBarangayModeValue($barangayIncidents, 'fire_cause', $stationHistoricalRow->type_incident ?? 'electrical fire');
        $materials = $stationHistoricalRow->materials ?: 'mixed';
        $occupancy = $stationHistoricalRow->occupancy ?: 'residential';
        $accessibility = $stationHistoricalRow->accesibility ?: 'moderate access';

        $riskAreaSignals = DB::table('risk_areas')
            ->join('barangay_halls', 'risk_areas.barangay_hall_id', '=', 'barangay_halls.barangay_hall_id')
            ->where('risk_areas.station_id', $stationId)
            ->where('barangay_halls.barangay_id', $barangay->barangay_id)
            ->where(function ($query) {
                $query->where('risk_areas.review_status', 'approved')
                    ->orWhere('risk_areas.status', 'verified');
            })
            ->where(function ($query) {
                $query->whereNull('risk_areas.review_type')
                    ->orWhere('risk_areas.review_type', '!=', 'delete');
            })
            ->selectRaw("
                MAX(CASE WHEN risk_areas.near_water_source IS TRUE OR risk_areas.near_hydrant IS TRUE THEN 1 ELSE 0 END) as near_water,
                COUNT(*) as verified_risk_area_count,
                SUM(CASE WHEN risk_areas.entry_type = 'hazard' THEN 1 ELSE 0 END) as verified_hazard_count,
                SUM(CASE WHEN risk_areas.entry_type = 'risk' OR risk_areas.entry_type IS NULL THEN 1 ELSE 0 END) as verified_risk_count,
                SUM(CASE WHEN risk_areas.assessment_level = 'high' THEN 1 ELSE 0 END) as high_assessment_count,
                SUM(CASE WHEN risk_areas.blocked_hydrant IS TRUE THEN 1 ELSE 0 END) as blocked_hydrant_count,
                SUM(CASE WHEN risk_areas.combustible_materials_present IS TRUE THEN 1 ELSE 0 END) as combustible_material_count,
                SUM(CASE WHEN risk_areas.informal_settlement IS TRUE THEN 1 ELSE 0 END) as informal_settlement_count,
                SUM(CASE WHEN risk_areas.cooking_fire_prone_activity IS TRUE THEN 1 ELSE 0 END) as cooking_fire_activity_count,
                SUM(CASE WHEN risk_areas.poor_waste_disposal IS TRUE THEN 1 ELSE 0 END) as poor_waste_disposal_count,
                SUM(CASE WHEN risk_areas.previous_fire_prone_location IS TRUE THEN 1 ELSE 0 END) as previous_fire_prone_count,
                SUM(CASE WHEN risk_areas.road_accessibility IN ('difficult', 'very_difficult') THEN 1 ELSE 0 END) as poor_access_count,
                COALESCE(SUM(risk_areas.estimated_affected_households), 0) as affected_households
            ")
            ->first();

        $riskAreaProfile = DB::table('risk_areas')
            ->join('barangay_halls', 'risk_areas.barangay_hall_id', '=', 'barangay_halls.barangay_hall_id')
            ->where('risk_areas.station_id', $stationId)
            ->where('barangay_halls.barangay_id', $barangay->barangay_id)
            ->where(function ($query) {
                $query->where('risk_areas.review_status', 'approved')
                    ->orWhere('risk_areas.status', 'verified');
            })
            ->where(function ($query) {
                $query->whereNull('risk_areas.review_type')
                    ->orWhere('risk_areas.review_type', '!=', 'delete');
            })
            ->select(
                'risk_areas.hazard_category',
                'risk_areas.risk_type',
                'risk_areas.condition_label',
                'risk_areas.population_density_level',
                'risk_areas.building_density_level',
                'risk_areas.road_accessibility',
                'risk_areas.near_hydrant',
                'risk_areas.near_water_source'
            )
            ->orderByRaw("
                CASE risk_areas.assessment_level
                    WHEN 'high' THEN 1
                    WHEN 'moderate' THEN 2
                    WHEN 'medium' THEN 2
                    ELSE 3
                END
            ")
            ->orderByDesc('risk_areas.created_at')
            ->first();

        $verifiedRiskAreaCount = (int) ($riskAreaSignals->verified_risk_area_count ?? 0);
        $verifiedHazardCount = (int) ($riskAreaSignals->verified_hazard_count ?? 0);
        $verifiedRiskCount = (int) ($riskAreaSignals->verified_risk_count ?? 0);
        $highAssessmentCount = (int) ($riskAreaSignals->high_assessment_count ?? 0);
        $blockedHydrantCount = (int) ($riskAreaSignals->blocked_hydrant_count ?? 0);
        $combustibleMaterialCount = (int) ($riskAreaSignals->combustible_material_count ?? 0);
        $informalSettlementCount = (int) ($riskAreaSignals->informal_settlement_count ?? 0);
        $cookingFireActivityCount = (int) ($riskAreaSignals->cooking_fire_activity_count ?? 0);
        $poorWasteDisposalCount = (int) ($riskAreaSignals->poor_waste_disposal_count ?? 0);
        $previousFireProneCount = (int) ($riskAreaSignals->previous_fire_prone_count ?? 0);
        $poorAccessCount = (int) ($riskAreaSignals->poor_access_count ?? 0);
        $affectedHouseholds = (int) ($riskAreaSignals->affected_households ?? 0);
        $riskAreaPressureSignal = round(
            ($verifiedRiskAreaCount * 0.8)
            + ($verifiedHazardCount * 1.1)
            + ($highAssessmentCount * 2.5)
            + ($blockedHydrantCount * 1.8)
            + ($combustibleMaterialCount * 1.6)
            + ($informalSettlementCount * 1.3)
            + ($cookingFireActivityCount * 1.2)
            + ($poorWasteDisposalCount * 0.8)
            + ($previousFireProneCount * 1.6)
            + ($poorAccessCount * 1.4)
            + min($affectedHouseholds / 25, 5),
            4
        );

        $nearWater = (int) ($riskAreaSignals->near_water ?? $stationHistoricalRow->near_water ?? 0);
        $feedbackSignals = $this->predictionFeedbackSignalService->snapshotForBarangay(
            (int) $barangay->barangay_id,
            $targetMonth->copy()->endOfMonth()
        );

        $rawStationMomentum = (float) ($stationRecentFireSignal['recent_fire_momentum_signal'] ?? 0);
        $allocatedStationMomentum = round($rawStationMomentum / max(1, $coveredBarangayCount), 3);

        return array_merge([
            'station_id' => $stationId,
            'barangay_id' => (int) $barangay->barangay_id,
            'barangay_name' => $barangay->barangay_name,
            'month' => $targetMonth->format('F'),
            'year' => (int) $targetMonth->year,
            'incident_count' => $incidentCount,
            'current_month_fire_incidents' => (int) ($recentFireSignal['current_month_fire_incidents'] ?? 0),
            'previous_month_fire_incidents' => (int) ($recentFireSignal['previous_month_fire_incidents'] ?? 0),
            'recent_7_day_fire_incidents' => (int) ($recentFireSignal['recent_7_day_fire_incidents'] ?? 0),
            'recent_30_day_fire_incidents' => (int) ($recentFireSignal['recent_30_day_fire_incidents'] ?? 0),
            'recent_fire_momentum_signal' => (float) ($recentFireSignal['recent_fire_momentum_signal'] ?? 0),
            'station_current_month_fire_incidents' => (int) ($stationRecentFireSignal['current_month_fire_incidents'] ?? 0),
            'station_previous_month_fire_incidents' => (int) ($stationRecentFireSignal['previous_month_fire_incidents'] ?? 0),
            'station_recent_7_day_fire_incidents' => (int) ($stationRecentFireSignal['recent_7_day_fire_incidents'] ?? 0),
            // Station momentum is shared context, so allocate it across coverage instead
            // of adding the full signal to every barangay forecast.
            'station_recent_fire_momentum_signal' => $allocatedStationMomentum,
            'station_recent_fire_momentum_signal_raw' => $rawStationMomentum,
            'station_momentum_coverage_count' => max(1, $coveredBarangayCount),
            'risk_level' => $historicalRiskLevel,
            'average_temperature' => (float) $averageTemperature,
            'average_humidity' => $averageHumidity !== null ? (float) $averageHumidity : null,
            'average_wind_speed' => $averageWindSpeed !== null ? (float) $averageWindSpeed : null,
            'weather_condition' => $weatherCondition,
            'season' => $stationHistoricalRow->season,
            'near_water' => $nearWater,
            'near_hydrant' => (int) ($riskAreaProfile->near_hydrant ?? 0),
            'near_water_source' => (int) ($riskAreaProfile->near_water_source ?? 0),
            'verified_risk_area_count' => $verifiedRiskAreaCount,
            'verified_hazard_count' => $verifiedHazardCount,
            'verified_risk_count' => $verifiedRiskCount,
            'high_assessment_count' => $highAssessmentCount,
            'blocked_hydrant_count' => $blockedHydrantCount,
            'combustible_material_count' => $combustibleMaterialCount,
            'informal_settlement_count' => $informalSettlementCount,
            'cooking_fire_activity_count' => $cookingFireActivityCount,
            'poor_waste_disposal_count' => $poorWasteDisposalCount,
            'previous_fire_prone_count' => $previousFireProneCount,
            'poor_access_count' => $poorAccessCount,
            'affected_households' => $affectedHouseholds,
            'risk_area_pressure_signal' => $riskAreaPressureSignal,
            'hazard_category' => $riskAreaProfile->hazard_category ?? 'unknown',
            'risk_type' => $riskAreaProfile->risk_type ?? 'unknown',
            'condition_label' => $riskAreaProfile->condition_label ?? 'unknown',
            'population_density_level' => $riskAreaProfile->population_density_level ?? 'unknown',
            'building_density_level' => $riskAreaProfile->building_density_level ?? 'unknown',
            'road_accessibility' => $riskAreaProfile->road_accessibility ?? 'unknown',
            'density' => $density,
            'materials' => $materials,
            'peak_hours' => $peakHours,
            'accesibility' => $accessibility,
            'type_incident' => $typeIncident,
            'occupancy' => $occupancy,
        ], $feedbackSignals);
    }

    private function buildRecentFireMomentumByBarangay(int $stationId): array
    {
        $teamIds = Team::query()
            ->where('station_id', $stationId)
            ->pluck('team_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();
        $now = Carbon::now();
        $windowStart = $now->copy()->subDays(60)->startOfDay();

        $incidentDateExpression = Schema::hasColumn('incidents', 'occurred_at')
            ? 'COALESCE(occurred_at, created_at)'
            : 'created_at';
        $incidentsQuery = Incident::query()
            ->whereRaw('LOWER(status) IN (?, ?)', ['closed', 'completed'])
            ->whereRaw("{$incidentDateExpression} >= ?", [$windowStart])
            ->where(function ($query) use ($stationId, $teamIds) {
                $query->where('station_id', $stationId);
                if (! empty($teamIds)) {
                    $query->orWhereIn('team_id', $teamIds);
                }
            });

        if (Schema::hasColumn('incidents', 'incident_type_id')) {
            $incidentsQuery->where('incident_type_id', 1);
        }

        $incidentColumns = ['barangay_id', 'created_at'];
        if (Schema::hasColumn('incidents', 'occurred_at')) {
            $incidentColumns[] = 'occurred_at';
        }
        $incidents = $incidentsQuery->get($incidentColumns);

        $signals = $incidents
            ->filter(fn ($incident) => ! empty($incident->barangay_id))
            ->groupBy(fn ($incident) => (int) $incident->barangay_id)
            ->map(fn ($rows) => $this->summarizeRecentFireRows($rows, $now))
            ->all();

        // Key 0 is the station-wide signal. It lets records without a resolved barangay
        // affect every barangay's common baseline without pretending they occurred locally.
        $signals[0] = $this->summarizeRecentFireRows($incidents, $now);

        return $signals;
    }

    private function summarizeRecentFireRows($rows, Carbon $now): array
    {
        $currentMonthStart = $now->copy()->startOfMonth();
        $previousMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();
        $lastSevenStart = $now->copy()->subDays(6)->startOfDay();
        $lastThirtyStart = $now->copy()->subDays(29)->startOfDay();
        $countBetween = fn (Carbon $start, Carbon $end) => $rows->filter(
            fn ($incident) => optional($incident->occurred_at ?: $incident->created_at)?->betweenIncluded($start, $end)
        )->count();
        $currentMonth = $countBetween($currentMonthStart, $now->copy()->endOfDay());
        $previousMonth = $countBetween($previousMonthStart, $previousMonthEnd);
        $lastSeven = $countBetween($lastSevenStart, $now->copy()->endOfDay());
        $lastThirty = $countBetween($lastThirtyStart, $now->copy()->endOfDay());

        return [
            'current_month_fire_incidents' => $currentMonth,
            'previous_month_fire_incidents' => $previousMonth,
            'recent_7_day_fire_incidents' => $lastSeven,
            'recent_30_day_fire_incidents' => $lastThirty,
            'recent_fire_momentum_signal' => round(
                ($lastSeven * 2.0)
                + (max(0, $lastThirty - $lastSeven) * 0.65)
                + ($currentMonth * 0.8)
                - ($previousMonth * 0.35),
                3
            ),
        ];
    }

    private function deriveBarangayPeakHours($barangayIncidents, string $fallback): string
    {
        $buckets = [
            'morning' => 0,
            'afternoon' => 0,
            'evening' => 0,
            'night' => 0,
        ];

        foreach ($barangayIncidents as $incident) {
            if (empty($incident->incident_time)) {
                continue;
            }

            try {
                $hour = (int) Carbon::parse($incident->incident_time)->format('H');
            } catch (\Throwable $e) {
                continue;
            }

            if ($hour >= 5 && $hour < 12) {
                $buckets['morning']++;
            } elseif ($hour >= 12 && $hour < 17) {
                $buckets['afternoon']++;
            } elseif ($hour >= 17 && $hour < 21) {
                $buckets['evening']++;
            } else {
                $buckets['night']++;
            }
        }

        arsort($buckets);
        $peak = array_key_first($buckets);

        return ($peak && ($buckets[$peak] ?? 0) > 0) ? $peak : $fallback;
    }

    private function deriveBarangayModeValue($barangayIncidents, string $field, string $fallback): string
    {
        $counts = [];

        foreach ($barangayIncidents as $incident) {
            $value = trim((string) ($incident->{$field} ?? ''));
            if ($value === '') {
                continue;
            }

            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        if (empty($counts)) {
            return $fallback;
        }

        arsort($counts);

        return (string) array_key_first($counts);
    }

    /**
     * Calculate risk score based on actual incident data
     */
    private function calculateRiskScore($incidents, $fireIncidents, $days)
    {
        $incidentRate = $days > 0 ? ($incidents->count() / $days) : 0;
        $fireRate = $days > 0 ? ($fireIncidents / $days) : 0;

        $score = 0;
        $score += min(30, $incidentRate * 5);
        $score += min(30, $fireRate * 10);
        $score += min(20, ($fireIncidents / max(1, $incidents->count())) * 20);

        // Check for recent spike
        $recentIncidents = 0;
        foreach ($incidents as $incident) {
            $incidentDate = $incident->created_at ?? null;
            if ($incidentDate) {
                try {
                    if (Carbon::parse($incidentDate)->diffInDays(now()) <= 7) {
                        $recentIncidents++;
                    }
                } catch (\Exception $e) {
                }
            }
        }

        if ($recentIncidents > $incidentRate * 7 * 1.5) {
            $score += 20;
        }

        $score = min(100, round($score));

        $level = 'Low';
        if ($score >= 70) {
            $level = 'Critical';
        } elseif ($score >= 50) {
            $level = 'High';
        } elseif ($score >= 30) {
            $level = 'Medium';
        }

        return ['score' => $score, 'level' => $level];
    }

    /**
     * Identify risk factors from data patterns
     */
    private function identifyRiskFactors($incidents, $incidentsByType, $peakHour, $avgResponseTime)
    {
        $factors = [];

        $fireCount = $incidentsByType['fire'] ?? 0;
        if ($fireCount > 5) {
            $factors[] = "High frequency of fire incidents ({$fireCount} in period)";
        }

        if ($avgResponseTime && $avgResponseTime > 15) {
            $factors[] = "Elevated response time ({$avgResponseTime} minutes) exceeds target";
        }

        if ($peakHour !== null) {
            $factors[] = "Incident concentration around {$peakHour}:00 hours";
        }

        if ($incidents->count() > 20) {
            $factors[] = "High incident volume ({$incidents->count()} incidents) requiring resource review";
        }

        if (empty($factors)) {
            $factors[] = 'Incident patterns within normal operational parameters';
        }

        return $factors;
    }

    /**
     * Build AI prompt with real data
     */
    private function buildAIPredictionPrompt($data)
    {
        $prompt = "You are the BFP Chief Data Analyst. Analyze this REAL fire incident data.\n\n";
        $prompt .= "LOCATION: {$data['location_name']}\n";
        $prompt .= "PERIOD: Last {$data['days_analyzed']} days\n\n";
        $prompt .= "INCIDENTS: {$data['total_incidents']} total, {$data['fire_incidents']} fire incidents\n";

        if (! empty($data['incidents_by_type'])) {
            $prompt .= "\nBY TYPE:\n";
            foreach ($data['incidents_by_type'] as $type => $count) {
                $prompt .= "- {$type}: {$count}\n";
            }
        }

        $prompt .= "\nPATTERNS:\n";
        $prompt .= '- Peak Hour: '.($data['peak_hour'] ? $data['peak_hour'].':00' : 'No clear pattern')."\n";
        $prompt .= '- Response Time: '.($data['avg_response_time'] ? $data['avg_response_time'].' minutes' : 'N/A')."\n";
        $prompt .= "- Risk Score: {$data['risk_score']}/100 ({$data['risk_level']})\n";
        $prompt .= "- Total Personnel: {$data['total_personnel']}\n";
        $prompt .= "- Available Personnel: {$data['available_personnel']}\n";
        $prompt .= "- Total Equipment: {$data['total_equipment']}\n";
        $prompt .= "- Available Equipment: {$data['available_equipment']}\n\n";

        if (! empty($data['prediction_context'])) {
            $predictionContext = $data['prediction_context'];
            $prompt .= "MONTHLY PREDICTION TARGET:\n";
            $prompt .= '- Scope: '.($predictionContext['scope_label'] ?? $data['prediction_scope'])."\n";
            $prompt .= '- Target Month: '.($predictionContext['target_month_label'] ?? 'Current Month')."\n";
            $prompt .= '- Calendar Window: '.($predictionContext['target_period_label'] ?? 'N/A')."\n";
            $prompt .= '- Current Month Incidents Counted: '.($predictionContext['current_month_total_incidents'] ?? 0)."\n";
            $prompt .= '- Current Month Fire Incidents: '.($predictionContext['current_month_fire_incidents'] ?? 0)."\n";
            $prompt .= '- Previous Full Month Incidents: '.($predictionContext['previous_month_total_incidents'] ?? 0)."\n";
            $prompt .= '- Previous Full Month Fire Incidents: '.($predictionContext['previous_month_fire_incidents'] ?? 0)."\n";
            $prompt .= '- Observed Peak Hours This Month: '.implode(', ', $predictionContext['likely_peak_hours'] ?? ['None'])."\n";
            $prompt .= '- Current Month Hourly Incident Distribution: '.json_encode($predictionContext['current_month_hourly_data'] ?? [])."\n\n";
        }

        if (! empty($data['weather_context'])) {
            $weather = $data['weather_context'];
            $prompt .= "WEATHER CONTEXT FOR THIS MONTH:\n";
            $prompt .= '- Query Location: '.($weather['query_location'] ?? $data['location_name'])."\n";
            $prompt .= '- Coverage Window: '.($weather['coverage'] ?? 'Current month')."\n";
            $prompt .= '- Average Temperature: '.(($weather['avg_temp_c'] ?? null) !== null ? $weather['avg_temp_c'].'°C' : 'N/A')."\n";
            $prompt .= '- Average Humidity: '.(($weather['avg_humidity'] ?? null) !== null ? $weather['avg_humidity'].'%' : 'N/A')."\n";
            $prompt .= '- Average Wind: '.(($weather['avg_wind_kph'] ?? null) !== null ? $weather['avg_wind_kph'].' kph' : 'N/A')."\n";
            $prompt .= '- Rainy Days in Sample: '.($weather['rainy_days'] ?? 0)."\n";
            $prompt .= '- Hot Days in Sample: '.($weather['hot_days'] ?? 0)."\n";
            $prompt .= '- Dominant Condition: '.($weather['dominant_condition'] ?? 'N/A')."\n";
            $prompt .= '- Fire Weather Risk: '.($weather['weather_risk_label'] ?? 'N/A')."\n";
            $prompt .= '- Weather Summary: '.($weather['summary'] ?? 'N/A')."\n\n";
        }

        if (! empty($data['mitigation_context'])) {
            $mitigation = $data['mitigation_context'];
            $prompt .= "MITIGATION STATUS:\n";
            $prompt .= '- '.($mitigation['summary'] ?? 'No mitigation summary available')."\n";
            $prompt .= '- Estimated risk reduction from confirmed actions: '.($mitigation['risk_reduction_percent'] ?? 0)."%\n\n";
        }

        if (! empty($data['selected_barangay_prediction'])) {
            $selectedInput = $data['selected_barangay_prediction']['input'] ?? [];
            $prompt .= "SELECTED BARANGAY FOCUS:\n";
            $prompt .= '- Barangay: '.($data['selected_barangay_prediction']['barangay_name'] ?? 'Selected barangay')."\n";
            $prompt .= '- Barangay risk probability: '.($data['selected_barangay_prediction']['probability_percent'] ?? 0)."%\n";
            $prompt .= '- Barangay risk level: '.strtoupper($data['selected_barangay_prediction']['risk_level'] ?? 'unknown')."\n";
            $prompt .= '- Completed fires this month: '.($selectedInput['current_month_fire_incidents'] ?? 0)."\n";
            $prompt .= '- Completed fires in the previous month: '.($selectedInput['previous_month_fire_incidents'] ?? 0)."\n";
            $prompt .= '- Completed fires in the latest 7 days: '.($selectedInput['recent_7_day_fire_incidents'] ?? 0)."\n";
            $prompt .= '- Recent fire momentum signal: '.($selectedInput['recent_fire_momentum_signal'] ?? 0)."\n\n";
            $prompt .= '- Station-wide completed fires this month: '.($selectedInput['station_current_month_fire_incidents'] ?? 0)."\n";
            $prompt .= '- Station-wide recent fire momentum: '.($selectedInput['station_recent_fire_momentum_signal'] ?? 0)."\n\n";
        }

        if (! empty($data['barangay_predictions'])) {
            $prompt .= "BARANGAY-LEVEL HOTSPOT FORECASTS:\n";
            foreach (array_slice($data['barangay_predictions'], 0, 5) as $barangayForecast) {
                $barangayInput = $barangayForecast['input'] ?? [];
                $prompt .= '- '.($barangayForecast['barangay_name'] ?? 'Unknown barangay')
                    .': '.strtoupper($barangayForecast['risk_level'] ?? 'unknown')
                    .' risk at '.($barangayForecast['probability_percent'] ?? 0).'% probability'
                    .' | Current-month completed fires: '.($barangayInput['current_month_fire_incidents'] ?? 0)
                    .' | 7-day local fires: '.($barangayInput['recent_7_day_fire_incidents'] ?? 0)
                    .' | Station fires this month: '.($barangayInput['station_current_month_fire_incidents'] ?? 0);

                if (! empty($barangayForecast['drivers'][0]['summary'])) {
                    $prompt .= ' | Driver: '.$barangayForecast['drivers'][0]['summary'];
                }

                $prompt .= "\n";
            }
            $prompt .= "\n";
        }

        if (! empty($data['model_prediction']['output'])) {
            $modelPrediction = $data['model_prediction']['output'];
            $prompt .= "ML MODEL SIGNALS:\n";
            $prompt .= '- Model Target: '.($modelPrediction['target'] ?? 'N/A')."\n";
            $prompt .= '- Probability: '.(($modelPrediction['probability_percent'] ?? null) !== null ? $modelPrediction['probability_percent'].'%' : 'N/A')."\n";
            $prompt .= '- Risk Level: '.strtoupper($modelPrediction['risk_level'] ?? 'unknown')."\n";
            $prompt .= '- Confidence Band: '.strtoupper($modelPrediction['confidence_band'] ?? 'unknown')."\n";
            $prompt .= '- Observed Incidents To Date: '.($modelPrediction['observed_fire_incidents_to_date'] ?? 0)."\n";
            $prompt .= '- Predicted Additional Incidents: '.($modelPrediction['predicted_additional_fire_incidents'] ?? $modelPrediction['predicted_incident_count'] ?? 'N/A')."\n";
            $prompt .= '- Expected Month-End Total: '.($modelPrediction['expected_month_total_fire_incidents'] ?? $modelPrediction['predicted_incident_count'] ?? 'N/A')."\n";
            $prompt .= '- Predicted Damage Exposure: '.(isset($modelPrediction['predicted_estimated_damage']) ? 'PHP '.number_format((float) $modelPrediction['predicted_estimated_damage'], 2) : 'N/A')."\n";
            $prompt .= '- Watch Items: '.implode(', ', $modelPrediction['watch_items'] ?? ['None'])."\n";
            foreach (array_slice($modelPrediction['drivers'] ?? [], 0, 3) as $driver) {
                if (! empty($driver['summary'])) {
                    $prompt .= "- Driver: {$driver['summary']}\n";
                }
            }
            $prompt .= "\nUse these model signals as grounded evidence. For a current-month analysis, never describe the forecast as fewer than incidents already observed. Clearly distinguish observed-to-date, predicted additional, and expected month-end total.\n\n";
        }

        $prompt .= "Only provide prediction content. Do not give action recommendations.\n";
        $prompt .= "Provide your answer using these exact sections:\n";
        $prompt .= "PREDICTION:\n";
        $prompt .= "FORECAST WINDOW:\n";
        $prompt .= "PREDICTED FIRE INCIDENTS:\n";
        $prompt .= "LIKELY PEAK HOURS:\n";
        $prompt .= "RISK OUTLOOK:\n";
        $prompt .= "DRIVERS:\n";
        $prompt .= "- bullet point\n";
        $prompt .= "ANALYSIS SUMMARY:\n";

        return $prompt;
    }

    private function buildAIRecommendationPrompt($data)
    {
        $prompt = "You are the BFP Chief Operations Advisor. Review this REAL station data and provide actionable recommendations.\n\n";
        $prompt .= "LOCATION: {$data['location_name']}\n";
        $prompt .= "ANALYSIS PERIOD: Last {$data['days_analyzed']} days\n";
        $prompt .= "TOTAL INCIDENTS: {$data['total_incidents']}\n";
        $prompt .= "FIRE INCIDENTS: {$data['fire_incidents']}\n";
        $prompt .= "RISK SCORE: {$data['risk_score']}/100 ({$data['risk_level']})\n";
        $prompt .= 'PEAK HOUR: '.($data['peak_hour'] ? $data['peak_hour'].':00' : 'No clear pattern')."\n";
        $prompt .= 'AVG RESPONSE TIME: '.($data['avg_response_time'] ? $data['avg_response_time'].' minutes' : 'N/A')."\n";
        $prompt .= "AVAILABLE PERSONNEL: {$data['available_personnel']} / {$data['total_personnel']}\n";
        $prompt .= "AVAILABLE EQUIPMENT: {$data['available_equipment']} / {$data['total_equipment']}\n";

        if (! empty($data['prediction_context'])) {
            $prompt .= "MONTHLY CONTEXT:\n";
            $prompt .= '- Current month fire incidents: '.($data['prediction_context']['current_month_fire_incidents'] ?? 0)."\n";
            $prompt .= '- Likely peak hours: '.implode(', ', $data['prediction_context']['likely_peak_hours'] ?? ['None'])."\n";
            $prompt .= '- Target month: '.($data['prediction_context']['target_month_label'] ?? 'Current Month')."\n";
        }

        if (! empty($data['weather_context'])) {
            $prompt .= 'WEATHER SUMMARY: '.($data['weather_context']['summary'] ?? 'N/A')."\n";
        }

        if (! empty($data['mitigation_context'])) {
            $prompt .= 'MITIGATION SUMMARY: '.($data['mitigation_context']['summary'] ?? 'N/A')."\n";
            $prompt .= 'MITIGATION RISK REDUCTION: '.($data['mitigation_context']['risk_reduction_percent'] ?? 0)."%\n";
        }

        if (! empty($data['recommendation_context']['focus_findings'])) {
            $prompt .= "LOCALIZED FIELD FINDINGS:\n";
            foreach (array_slice($data['recommendation_context']['focus_findings'], 0, 8) as $finding) {
                $prompt .= '- '.($finding['summary'] ?? 'Localized finding')."\n";
                if (! empty($finding['notes'])) {
                    $prompt .= '  Notes: '.$finding['notes']."\n";
                }
            }
        }

        if (! empty($data['selected_barangay_prediction'])) {
            $selectedBarangay = $data['selected_barangay_prediction'];
            $prompt .= "SELECTED BARANGAY PRIORITY:\n";
            $prompt .= '- Barangay: '.($selectedBarangay['barangay_name'] ?? 'Selected barangay')."\n";
            $prompt .= '- Probability: '.($selectedBarangay['probability_percent'] ?? 0)."%\n";
            $prompt .= '- Risk level: '.strtoupper($selectedBarangay['risk_level'] ?? 'unknown')."\n";
            if (! empty($selectedBarangay['drivers'][0]['summary'])) {
                $prompt .= '- Leading driver: '.$selectedBarangay['drivers'][0]['summary']."\n";
            }
        }

        if (! empty($data['barangay_predictions'])) {
            $prompt .= "TOP BARANGAY HOTSPOTS:\n";
            foreach (array_slice($data['barangay_predictions'], 0, 5) as $barangayForecast) {
                $prompt .= '- '.($barangayForecast['barangay_name'] ?? 'Unknown barangay')
                    .' | probability: '.($barangayForecast['probability_percent'] ?? 0).'%'
                    .' | risk: '.strtoupper($barangayForecast['risk_level'] ?? 'unknown')."\n";
            }
        }

        if (! empty($data['model_prediction']['output'])) {
            $modelPrediction = $data['model_prediction']['output'];
            $prompt .= "ML MODEL RISK BRIEF:\n";
            $prompt .= '- Risk level: '.strtoupper($modelPrediction['risk_level'] ?? 'unknown')."\n";
            $prompt .= '- Probability: '.(($modelPrediction['probability_percent'] ?? null) !== null ? $modelPrediction['probability_percent'].'%' : 'N/A')."\n";
            $prompt .= '- Confidence: '.strtoupper($modelPrediction['confidence_band'] ?? 'unknown')."\n";
            $prompt .= '- Predicted incidents: '.($modelPrediction['predicted_incident_count'] ?? 'N/A')."\n";
            $prompt .= '- Predicted damage: '.(isset($modelPrediction['predicted_estimated_damage']) ? 'PHP '.number_format((float) $modelPrediction['predicted_estimated_damage'], 2) : 'N/A')."\n";
            $prompt .= '- Watch items: '.implode(', ', $modelPrediction['watch_items'] ?? ['None'])."\n";
            foreach (array_slice($modelPrediction['drivers'] ?? [], 0, 3) as $driver) {
                if (! empty($driver['summary'])) {
                    $prompt .= "- Evidence: {$driver['summary']}\n";
                }
            }
        }

        $prompt .= "\nProvide station recommendations only. Do not repeat the monthly prediction.\n";
        $prompt .= "Recommendations must be specific, localized, and operational.\n";
        $prompt .= "When a barangay, sitio, street, hazard type, wiring condition, hydrant issue, density issue, or access problem is available, mention it explicitly.\n";
        $prompt .= "Avoid vague wording such as 'check high risk areas' or 'improve safety awareness' unless you also name the exact area and condition driving the action.\n";
        $prompt .= "Each recommendation title must follow this style: [Action] + [Specific condition or hazard] + [Barangay / Street or Sitio].\n";
        $prompt .= "Example titles:\n";
        $prompt .= "- Inspect octopus wiring near Purok 3, Angatel | High\n";
        $prompt .= "- Clear blocked hydrant access on Rizal Street, Poblacion | High\n";
        $prompt .= "- Check LPG hose installation in Sitio Valdez, Dalanguiring | Medium\n";
        $prompt .= "Format exactly as:\n";
        $prompt .= "RECOMMENDATIONS:\n";
        $prompt .= "1. [Title] | [Priority]\n";
        $prompt .= "Description: [Actionable recommendation description]\n";
        $prompt .= "Repeat the numbered recommendation format for up to 10 recommendation items.\n";
        $prompt .= "SUMMARY:\n";

        return $prompt;
    }

    private function buildAIActionPlanPrompt(array $analysis, array $data): string
    {
        $prompt = "You are the BFP implementation planner. Convert the fire-risk analysis into a checklist-style action plan that BFP can monitor and confirm.\n\n";
        $prompt .= "STATION: {$data['location_name']}\n";
        $prompt .= "RISK LEVEL: {$analysis['risk_level']}\n";
        $prompt .= 'CONFIDENCE SCORE: '.($analysis['confidence_score'] ?? 'N/A')."\n";

        if (! empty($analysis['predictions']['summary'])) {
            $prompt .= "PREDICTION SUMMARY: {$analysis['predictions']['summary']}\n";
        }

        if (! empty($data['model_prediction']['output'])) {
            $modelPrediction = $data['model_prediction']['output'];
            $prompt .= 'MODEL PROBABILITY: '.($modelPrediction['probability_percent'] ?? 'N/A')."%\n";
            $prompt .= 'MODEL RISK LEVEL: '.strtoupper($modelPrediction['risk_level'] ?? 'unknown')."\n";
            $prompt .= 'MODEL PREDICTED INCIDENT COUNT: '.($modelPrediction['predicted_incident_count'] ?? 'N/A')."\n";
            $prompt .= 'MODEL PREDICTED DAMAGE: '.(isset($modelPrediction['predicted_estimated_damage']) ? 'PHP '.number_format((float) $modelPrediction['predicted_estimated_damage'], 2) : 'N/A')."\n";
            foreach (array_slice($modelPrediction['drivers'] ?? [], 0, 3) as $driver) {
                if (! empty($driver['summary'])) {
                    $prompt .= "MODEL DRIVER: {$driver['summary']}\n";
                }
            }
        }

        $prompt .= "\nRECOMMENDATIONS TO CONVERT INTO A CHECKLIST:\n";
        foreach (array_slice($analysis['recommendations'] ?? [], 0, 10) as $index => $recommendation) {
            $prompt .= ($index + 1).". {$recommendation['title']} | {$recommendation['priority']}\n";
            $prompt .= "Description: {$recommendation['description']}\n";
        }

        $prompt .= "\nReturn JSON only with this exact shape:\n";
        $prompt .= "{\n";
        $prompt .= "  \"plans\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"source_title\": \"Recommendation title\",\n";
        $prompt .= "      \"plan_title\": \"Short action plan title\",\n";
        $prompt .= "      \"plan_description\": \"Short summary for BFP\",\n";
        $prompt .= "      \"priority_level\": \"low|medium|high|critical\",\n";
        $prompt .= "      \"success_indicator\": \"What BFP should confirm\",\n";
        $prompt .= "      \"target_days\": 14,\n";
        $prompt .= "      \"checklist_items\": [\"item 1\", \"item 2\", \"item 3\"]\n";
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
        $prompt .= "}\n";

        return $prompt;
    }

    /**
     * Call AI API
     */
    /**
     * Call AI API
     */
    private function callAIApi($prompt, $systemPrompt = 'You are a fire safety expert. Provide concise analysis with clear bullet points.')
    {
        if (! $this->apiKey) {
            Log::error('No API key available for AI call');
            throw new \Exception('No API key configured');
        }

        Log::info('Calling OpenRouter API.');

        $errors = [];
        $connectTimeout = max(3, (int) config('services.openrouter.connect_timeout', 8));
        $timeout = max($connectTimeout + 2, (int) config('services.openrouter.timeout', 20));
        $maxTokens = max(150, (int) config('services.openrouter.max_tokens', 450));
        $prompt = $this->trimAiPrompt($prompt);

        foreach ($this->getOpenRouterModelCandidates() as $model) {
            if (isset($this->unavailableAiModels[$model])) {
                continue;
            }

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => url('/'),
                    'X-Title' => 'PyroSense Analytics',
                ])->connectTimeout($connectTimeout)->timeout($timeout)->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => $maxTokens,
                ]);
            } catch (\Throwable $e) {
                $this->unavailableAiModels[$model] = true;
                $errors[] = "{$model}: {$e->getMessage()}";
                Log::warning('OpenRouter API request threw an exception for model '.$model, [
                    'message' => $e->getMessage(),
                    'timeout' => $timeout,
                    'connect_timeout' => $connectTimeout,
                ]);

                continue;
            }

            if (! $response->successful()) {
                $this->unavailableAiModels[$model] = true;
                $errorBody = $response->body();
                $errors[] = "{$model}: HTTP {$response->status()}";
                Log::warning('OpenRouter API call failed for model '.$model.': '.$response->status().' - '.$errorBody);

                continue;
            }

            $json = $response->json();
            $content = $json['choices'][0]['message']['content'] ?? '';

            if (empty($content)) {
                $this->unavailableAiModels[$model] = true;
                $errors[] = "{$model}: empty response";
                Log::warning('OpenRouter API returned empty content for model '.$model);

                continue;
            }

            $this->lastAiModelUsed = $model;
            Log::info('AI API call successful with model '.$model.', response length: '.strlen($content));

            return $content;
        }

        Log::error('OpenRouter API call failed for all configured models: '.implode(' | ', $errors));
        throw new \Exception('AI call failed for all configured models');
    }

    private function getOpenRouterModelCandidates(): array
    {
        $configuredModel = config('services.openrouter.model', 'deepseek/deepseek-r1-distill-llama-70b');
        $fallbackModels = config('services.openrouter.fallback_models', []);

        return array_values(array_unique(array_filter([
            $configuredModel,
            ...$fallbackModels,
        ])));
    }

    private function trimAiPrompt(string $prompt): string
    {
        $maxLength = 12000;
        if (mb_strlen($prompt) <= $maxLength) {
            return $prompt;
        }

        $headLength = 8000;
        $tailLength = 3500;

        return mb_substr($prompt, 0, $headLength)
            ."\n\n[Prompt shortened to reduce API latency while preserving key analytics context.]\n\n"
            .mb_substr($prompt, -$tailLength);
    }

    private function generateAndStoreActionPlanChecklists(int $analysisId, array $analysis, array $rawData): array
    {
        $recommendations = array_slice($analysis['recommendations'] ?? [], 0, 10);
        if (empty($recommendations)) {
            return [
                'plans' => [],
                'debug' => [
                    'analysis_id' => $analysisId,
                    'recommendation_count' => 0,
                    'persisted_checklist_rows' => 0,
                    'persisted_checklist_items' => 0,
                    'used_ai_generation' => false,
                    'used_fallback_generation' => false,
                    'warning' => 'No recommendations were available to convert into checklist rows.',
                ],
            ];
        }

        $plans = [];
        $aiGenerated = false;
        $usedFallbackGeneration = false;
        $shouldGenerateAiActionPlans = filter_var(
            env('AI_GENERATE_ACTION_PLAN_CHECKLISTS', false),
            FILTER_VALIDATE_BOOL
        );
        $generationWarning = null;

        if ($shouldGenerateAiActionPlans) {
            try {
                $aiResponse = $this->callAIApi(
                    $this->buildAIActionPlanPrompt($analysis, $rawData),
                    'You are a fire safety compliance planner. Return JSON only, with concrete checklist items that BFP can review and confirm.'
                );
                $plans = $this->parseActionPlanChecklistResponse($aiResponse);
                $aiGenerated = ! empty($plans);
            } catch (\Throwable $e) {
                Log::warning('Action-plan checklist AI generation failed: '.$e->getMessage(), [
                    'analysis_id' => $analysisId,
                    'exception' => $e,
                ]);
                $generationWarning = 'AI checklist generation failed; fallback checklist rows were used instead.';
            }
        } else {
            Log::info('Skipping checklist AI generation during analytics request and using recommendation-backed checklist rows.');
            $generationWarning = 'AI checklist generation is disabled; fallback checklist rows were used.';
        }

        $usedFallbackGeneration = empty($plans);
        $plans = $this->normalizeActionPlanCount($plans, $recommendations, $rawData);

        $targetBarangayIds = $this->resolveChecklistTargetBarangayIds($rawData);
        if (count($targetBarangayIds) > 1) {
            // Station-wide runs still cover every barangay, but use the highest-priority
            // recommendation once per barangay instead of creating a full duplicate set.
            $plans = array_slice($plans, 0, 1);
            $recommendations = array_slice($recommendations, 0, 1);
        }

        $analysisRevision = max(1, (int) (Analysis::query()->find($analysisId)?->revision_number ?? 1));
        $existingPlans = AiActionPlanChecklist::withoutGlobalScope('current_revision')
            ->where('analysis_id', $analysisId)
            ->where('is_superseded', false)
            ->with(['items', 'mitigationActions'])
            ->get();
        $reconciledExistingIds = [];

        $savedPlans = [];
        $newPlansByBarangay = [];

        foreach ($plans as $index => $plan) {
            $sourceRecommendation = $recommendations[$index] ?? $recommendations[0];

            foreach ($targetBarangayIds as $targetBarangayId) {
                $effectivePlan = (array) $plan;
                $effectiveRecommendation = (array) $sourceRecommendation;

                if (count($targetBarangayIds) > 1) {
                    [$effectivePlan, $effectiveRecommendation] = $this->localizeStationWideActionPlan(
                        $effectivePlan,
                        $effectiveRecommendation,
                        (int) $targetBarangayId,
                        $rawData
                    );
                }

                $riskAreaId = $this->resolveChecklistRiskAreaId($rawData, $effectiveRecommendation, $index, $targetBarangayId);
                $matchedRiskArea = $this->resolveChecklistRiskAreaSummary($rawData, $riskAreaId);
                $planPayload = array_merge($effectivePlan, [
                    'workflow_type' => 'analysis_action_plan',
                    'analysis_id' => $analysisId,
                    'barangay_id' => $targetBarangayId,
                    'risk_area_id' => $riskAreaId,
                    'matched_risk_area' => $matchedRiskArea,
                    'source_recommendation' => [
                        'title' => $effectiveRecommendation['title'] ?? null,
                        'description' => $effectiveRecommendation['description'] ?? null,
                        'priority' => $effectiveRecommendation['priority'] ?? null,
                    ],
                ]);

                $sourceTitle = $effectivePlan['source_title'] ?? ($effectiveRecommendation['title'] ?? 'AI Action Plan');
                $planTitle = $effectivePlan['plan_title'] ?? ($effectiveRecommendation['title'] ?? 'AI Action Plan');
                $sourceSignature = $this->buildActionPlanRevisionSignature(
                    (int) ($rawData['station_id'] ?? 0),
                    (int) $targetBarangayId,
                    $riskAreaId ? (int) $riskAreaId : null,
                    (string) $sourceTitle
                );
                $checklistAttributes = [
                    'analysis_id' => $analysisId,
                    'station_id' => $rawData['station_id'] ?? null,
                    'barangay_id' => $targetBarangayId,
                    'risk_area_id' => $riskAreaId,
                    'source_type' => 'action_plan',
                    'source_title' => $sourceTitle,
                    'source_description' => $effectiveRecommendation['description'] ?? null,
                    'plan_title' => $planTitle,
                    'plan_description' => $this->decoratePlanDescriptionForBarangay(
                        $effectivePlan['plan_description'] ?? ($effectiveRecommendation['description'] ?? null),
                        $targetBarangayId,
                        $rawData,
                        $matchedRiskArea
                    ),
                    'checklist_items' => array_values(array_filter($effectivePlan['checklist_items'] ?? [])),
                    'success_indicator' => $effectivePlan['success_indicator'] ?? null,
                    'priority_level' => strtolower($effectivePlan['priority_level'] ?? ($effectiveRecommendation['priority'] ?? 'medium')),
                    'status' => 'pending',
                    'target_date' => isset($effectivePlan['target_days']) ? now()->addDays((int) $effectivePlan['target_days'])->toDateString() : null,
                    'ai_payload' => $planPayload,
                    'is_ai_generated' => $aiGenerated,
                    'source_signature' => $sourceSignature,
                    'analysis_revision' => $analysisRevision,
                    'is_superseded' => false,
                    'requires_revision_review' => false,
                    'superseded_at' => null,
                ];

                $matchingPlan = $existingPlans->first(function (AiActionPlanChecklist $existing) use ($sourceSignature, $targetBarangayId, $riskAreaId, $sourceTitle) {
                    if (filled($existing->source_signature)) {
                        return hash_equals((string) $existing->source_signature, $sourceSignature);
                    }

                    return (int) $existing->barangay_id === (int) $targetBarangayId
                        && (int) ($existing->risk_area_id ?? 0) === (int) ($riskAreaId ?? 0)
                        && Str::lower(trim((string) $existing->source_title)) === Str::lower(trim((string) $sourceTitle));
                });

                $checklistItems = array_values(array_filter($effectivePlan['checklist_items'] ?? []));
                $itemsChanged = true;
                $wasCreated = false;

                if ($matchingPlan && ! $this->isActionPlanRevisionProtected($matchingPlan)) {
                    $itemsChanged = array_values($matchingPlan->checklist_items ?? []) !== $checklistItems;
                    $matchingPlan->update($checklistAttributes);
                    $saved = $matchingPlan;
                    $reconciledExistingIds[] = (int) $matchingPlan->ai_action_plan_checklist_id;
                } else {
                    if ($matchingPlan) {
                        $this->markActionPlanForRevisionReview($matchingPlan);
                    }
                    $saved = AiActionPlanChecklist::create($checklistAttributes);
                    $wasCreated = true;
                }

                if ($itemsChanged) {
                    $this->syncChecklistItems($saved, $checklistItems);
                }

                if ($wasCreated && ! isset($newPlansByBarangay[$targetBarangayId])) {
                    $newPlansByBarangay[$targetBarangayId] = $saved;
                }

                $savedPlans[] = $saved;
            }
        }

        foreach ($newPlansByBarangay as $newPlan) {
            BarangayNotificationService::notifyActionPlanChecklistPublished($newPlan);
        }

        $existingPlans
            ->reject(fn (AiActionPlanChecklist $existing) => in_array((int) $existing->ai_action_plan_checklist_id, $reconciledExistingIds, true))
            ->each(function (AiActionPlanChecklist $existing) {
                if ($this->isActionPlanRevisionProtected($existing)) {
                    $this->markActionPlanForRevisionReview($existing);
                } else {
                    $this->supersedeActionPlanRevision($existing);
                }
            });

        $persistedChecklistRows = AiActionPlanChecklist::query()
            ->where('analysis_id', $analysisId)
            ->count();
        $persistedChecklistItems = AiActionPlanChecklistItem::query()
            ->whereIn(
                'ai_action_plan_checklist_id',
                collect($savedPlans)->pluck('ai_action_plan_checklist_id')->filter()->all()
            )
            ->count();

        if (! empty($plans) && ($persistedChecklistRows === 0 || $persistedChecklistItems === 0)) {
            throw new \RuntimeException('Checklist rows were generated in memory but were not fully persisted.');
        }

        return [
            'plans' => $savedPlans,
            'debug' => [
                'analysis_id' => $analysisId,
                'recommendation_count' => count($recommendations),
                'persisted_checklist_rows' => $persistedChecklistRows,
                'persisted_checklist_items' => $persistedChecklistItems,
                'used_ai_generation' => $aiGenerated,
                'used_fallback_generation' => $usedFallbackGeneration,
                'warning' => $generationWarning,
            ],
        ];
    }

    private function buildActionPlanRevisionSignature(int $stationId, int $barangayId, ?int $riskAreaId, string $sourceTitle): string
    {
        return hash('sha256', implode('|', [
            $stationId,
            $barangayId,
            $riskAreaId ?: 0,
            Str::lower(preg_replace('/\s+/', ' ', trim($sourceTitle)) ?: 'ai action plan'),
        ]));
    }

    private function isActionPlanRevisionProtected(AiActionPlanChecklist $checklist): bool
    {
        $checklist->loadMissing(['items', 'mitigationActions']);

        if (strtolower((string) $checklist->status) !== 'pending'
            || filled($checklist->assigned_to)
            || filled($checklist->confirmed_by)
            || filled($checklist->confirmed_at)
            || $checklist->items->contains(fn ($item) => strtolower((string) $item->status) !== 'pending')) {
            return true;
        }

        return $checklist->mitigationActions->contains(function (MitigationAction $action) {
            return strtolower((string) $action->status) !== 'draft'
                || $action->is_manually_edited
                || filled($action->responsible_team_id)
                || filled($action->responsible_user_id)
                || filled($action->reviewed_at)
                || filled($action->approved_at)
                || filled($action->implementation_notes)
                || filled($action->completion_notes)
                || filled($action->outcome_result);
        });
    }

    private function markActionPlanForRevisionReview(AiActionPlanChecklist $checklist): void
    {
        $checklist->update(['requires_revision_review' => true]);

        MitigationAction::withoutGlobalScope('current_revision')
            ->where('ai_action_plan_checklist_id', $checklist->ai_action_plan_checklist_id)
            ->where('is_superseded', false)
            ->update(['requires_revision_review' => true]);
    }

    private function supersedeActionPlanRevision(AiActionPlanChecklist $checklist): void
    {
        $checklist->update([
            'is_superseded' => true,
            'superseded_at' => now(),
            'requires_revision_review' => false,
        ]);

        $actions = MitigationAction::withoutGlobalScope('current_revision')
            ->where('ai_action_plan_checklist_id', $checklist->ai_action_plan_checklist_id)
            ->where('is_superseded', false)
            ->get();

        foreach ($actions as $action) {
            if (strtolower((string) $action->status) === 'draft' && ! $action->is_manually_edited) {
                $action->update([
                    'is_superseded' => true,
                    'superseded_at' => now(),
                    'requires_revision_review' => false,
                ]);
            } else {
                $action->update(['requires_revision_review' => true]);
            }
        }
    }

    private function resolveChecklistTargetBarangayIds(array $rawData): array
    {
        $selectedBarangayId = $this->normalizeSelectedBarangayId(
            $rawData['barangay_id']
            ?? data_get($rawData, 'selected_barangay_prediction.barangay_id')
            ?? null
        );

        if ($selectedBarangayId) {
            return [$selectedBarangayId];
        }

        $barangayIds = collect($rawData['barangay_predictions'] ?? [])
            ->pluck('barangay_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (! empty($barangayIds)) {
            return $barangayIds;
        }

        $fallbackBarangayId = $this->normalizeSelectedBarangayId(data_get($rawData, 'barangay_predictions.0.barangay_id'));

        if ($fallbackBarangayId) {
            return [$fallbackBarangayId];
        }

        return $this->resolveStationBarangays((int) ($rawData['station_id'] ?? 0))
            ->pluck('barangay_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function decoratePlanDescriptionForBarangay(
        ?string $description,
        int $barangayId,
        array $rawData,
        ?array $matchedRiskArea = null
    ): ?string {
        $base = trim((string) $description);
        $barangayPrediction = collect($rawData['barangay_predictions'] ?? [])
            ->first(fn ($prediction) => (int) ($prediction['barangay_id'] ?? 0) === $barangayId);
        $barangayName = $barangayPrediction['barangay_name']
            ?? $matchedRiskArea['barangay_name']
            ?? null;

        $contextParts = [];
        if ($barangayName) {
            $contextParts[] = "Focus barangay: {$barangayName}.";
        }

        $streetOrSitio = $matchedRiskArea['street_or_sitio'] ?? null;
        if ($streetOrSitio) {
            $contextParts[] = "Matched risk area: {$streetOrSitio}.";
        }

        if (! empty($contextParts)) {
            $contextText = implode(' ', $contextParts);

            return trim($base !== '' ? ($base.' '.$contextText) : $contextText);
        }

        return $base !== '' ? $base : null;
    }

    /**
     * Convert the single station-wide recommendation into a barangay-specific plan.
     * This prevents every barangay from receiving the same title, priority and steps.
     */
    private function localizeStationWideActionPlan(
        array $plan,
        array $recommendation,
        int $barangayId,
        array $rawData
    ): array {
        $prediction = collect($rawData['barangay_predictions'] ?? [])
            ->first(fn ($item) => (int) ($item['barangay_id'] ?? 0) === $barangayId) ?? [];
        $barangayFindings = collect(data_get($rawData, 'recommendation_context.focus_findings', []))
            ->filter(fn ($item) => (int) ($item['barangay_id'] ?? 0) === $barangayId)
            ->sortBy(function ($item) {
                return match (strtolower((string) ($item['assessment_level'] ?? ''))) {
                    'critical' => 0,
                    'high' => 1,
                    'moderate', 'medium' => 2,
                    default => 3,
                };
            })
            ->values();
        $finding = $barangayFindings->first() ?? [];

        $barangayName = trim((string) ($prediction['barangay_name'] ?? $finding['barangay_name'] ?? "Barangay {$barangayId}"));
        $predictionInput = is_array($prediction['input'] ?? null) ? $prediction['input'] : [];
        $probability = is_numeric($prediction['probability_percent'] ?? null)
            ? round((float) $prediction['probability_percent'], 1)
            : null;
        $riskLevel = strtolower((string) ($prediction['risk_level'] ?? $finding['assessment_level'] ?? 'medium'));
        $priority = $this->barangayActionPlanPriority($riskLevel, $probability);
        $verifiedRiskCount = max(0, (int) ($predictionInput['verified_risk_count'] ?? 0));
        $verifiedHazardCount = max(0, (int) ($predictionInput['verified_hazard_count'] ?? 0));
        $historicalIncidentCount = max(0, (int) ($predictionInput['incident_count'] ?? 0));
        $currentMonthFireCount = max(0, (int) ($predictionInput['current_month_fire_incidents'] ?? 0));
        $condition = trim((string) ($finding['condition_label'] ?? $finding['type_label'] ?? ''));
        if ($condition === '') {
            $condition = match (true) {
                $verifiedHazardCount > 0 => 'verified hazard conditions',
                $verifiedRiskCount > 0 => 'verified fire-risk conditions',
                $historicalIncidentCount > 0 || $currentMonthFireCount > 0 => 'repeat fire history',
                default => 'fire-prevention readiness',
            };
        }
        $location = trim((string) ($finding['street_or_sitio'] ?? 'barangay-wide priority areas'));
        $actionVerb = $barangayFindings->isNotEmpty() ? 'Inspect' : 'Review';
        $title = Str::limit("{$actionVerb} {$condition} in {$location}, {$barangayName}", 180, '');

        $evidenceSummary = [
            'barangay_id' => $barangayId,
            'barangay_name' => $barangayName,
            'probability_percent' => $probability,
            'risk_level' => $riskLevel,
            'priority_level' => $priority,
            'verified_risk_count' => $verifiedRiskCount,
            'verified_hazard_count' => $verifiedHazardCount,
            'matched_finding_count' => $barangayFindings->count(),
            'historical_incident_count' => $historicalIncidentCount,
            'current_month_fire_incidents' => $currentMonthFireCount,
            'risk_area_pressure_signal' => round((float) ($predictionInput['risk_area_pressure_signal'] ?? 0), 2),
            'leading_condition' => $condition,
            'leading_location' => $location,
            'source_risk_area_ids' => $barangayFindings
                ->pluck('risk_area_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all(),
        ];

        $context = [
            "{$barangayName} is assessed at ".strtoupper($riskLevel).' fire risk'.($probability !== null ? " ({$probability}% probability)." : '.'),
            ! empty($finding['summary']) ? 'Local focus: '.trim((string) $finding['summary']).'.' : null,
            "Evidence used: {$verifiedRiskCount} verified risk(s), {$verifiedHazardCount} verified hazard(s), {$historicalIncidentCount} historical incident record(s), and {$currentMonthFireCount} completed fire(s) this month.",
        ];
        $driver = collect($prediction['drivers'] ?? [])->first();
        $driverSummary = is_array($driver) ? ($driver['summary'] ?? null) : $driver;
        if (filled($driverSummary)) {
            $context[] = 'Leading signal: '.trim((string) $driverSummary).'.';
        }
        $description = implode(' ', array_filter($context));

        $targetDays = match ($priority) {
            'critical' => 7,
            'high' => 14,
            'medium' => 30,
            default => 45,
        };
        $items = [
            "Validate the {$condition} evidence in {$location}, {$barangayName}.",
            "Assign a barangay focal person and response team for {$barangayName}.",
            "Apply and document the required control for {$condition}.",
            "Verify completion with dated field evidence and update the {$barangayName} risk record.",
        ];

        $localizedRecommendation = array_merge($recommendation, [
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
        ]);
        $localizedPlan = array_merge($plan, [
            'source_title' => $title,
            'plan_title' => $title,
            'plan_description' => $description,
            'priority_level' => $priority,
            'target_days' => $targetDays,
            'checklist_items' => $items,
            'success_indicator' => "The {$condition} finding in {$barangayName} is verified, controlled, and supported by field evidence.",
            'evidence_summary' => $evidenceSummary,
        ]);

        return [$localizedPlan, $localizedRecommendation];
    }

    private function barangayActionPlanPriority(string $riskLevel, ?float $probability): string
    {
        if ($riskLevel === 'critical' || ($probability !== null && $probability >= 80)) {
            return 'critical';
        }

        if ($riskLevel === 'high' || ($probability !== null && $probability >= 60)) {
            return 'high';
        }

        if (in_array($riskLevel, ['moderate', 'medium'], true) || ($probability !== null && $probability >= 35)) {
            return 'medium';
        }

        return 'low';
    }

    private function generateAndStoreRecommendationSuggestions(int $analysisId, array $recommendations, array $savedPlans): array
    {
        $recommendations = array_values(array_filter($recommendations, fn ($recommendation) => is_array($recommendation)));
        if (empty($recommendations) || empty($savedPlans)) {
            return [
                'suggestions' => [],
                'debug' => [
                    'analysis_id' => $analysisId,
                    'persisted_suggestion_rows' => 0,
                    'warning' => empty($recommendations)
                        ? 'No recommendations were available to convert into implementation suggestions.'
                        : 'No saved action-plan checklist rows were available for suggestion linkage.',
                ],
            ];
        }

        $analysis = Analysis::query()->find($analysisId);
        AiRecommendationSuggestion::query()->where('analysis_id', $analysisId)->delete();

        $savedSuggestions = [];

        foreach ($savedPlans as $index => $actionPlanChecklist) {
            if (! $actionPlanChecklist instanceof AiActionPlanChecklist) {
                continue;
            }

            $matchedRecommendation = collect($recommendations)->first(function ($recommendation) use ($actionPlanChecklist) {
                return is_array($recommendation)
                    && strcasecmp(
                        (string) ($recommendation['title'] ?? ''),
                        (string) ($actionPlanChecklist->source_title ?? $actionPlanChecklist->plan_title ?? '')
                    ) === 0;
            });

            $recommendation = is_array($matchedRecommendation)
                ? $matchedRecommendation
                : [
                    'title' => $actionPlanChecklist->source_title ?? $actionPlanChecklist->plan_title,
                    'description' => $actionPlanChecklist->source_description ?? $actionPlanChecklist->plan_description,
                    'priority' => $actionPlanChecklist->priority_level ?? 'medium',
                ];

            $recommendationTitle = $this->normalizeRecommendationTitle($recommendation['title'] ?? $actionPlanChecklist->source_title ?? null);
            $recommendationDescription = $recommendation['description'] ?? $actionPlanChecklist->plan_description;
            $priority = strtolower((string) ($recommendation['priority'] ?? $actionPlanChecklist->priority_level ?? 'medium'));
            $contextPayload = $this->buildExploreRecommendationContext($analysis, $actionPlanChecklist, [
                'recommendation_title' => $recommendationTitle,
                'recommendation_description' => $recommendationDescription,
                'priority' => $priority,
                'barangay_name' => $actionPlanChecklist->barangay?->barangay_name,
                'street_or_sitio' => data_get($actionPlanChecklist->ai_payload, 'matched_risk_area.street_or_sitio'),
            ]);
            $parsedData = $this->getFallbackExploreData(
                $recommendationTitle,
                ucfirst($priority),
                $recommendationDescription,
                $contextPayload
            );

            $savedSuggestion = $this->storeRecommendationSuggestion(
                $analysis,
                $actionPlanChecklist,
                $recommendationTitle,
                $recommendationDescription,
                $priority,
                $contextPayload,
                $parsedData,
                'auto_generated_from_analysis',
                false
            );

            if ($savedSuggestion) {
                $savedSuggestions[] = $savedSuggestion->fresh();
            }
        }

        return [
            'suggestions' => $savedSuggestions,
            'debug' => [
                'analysis_id' => $analysisId,
                'persisted_suggestion_rows' => count($savedSuggestions),
                'warning' => count($savedSuggestions) === 0
                    ? 'Implementation suggestions were not persisted for this analysis.'
                    : null,
            ],
        ];
    }

    private function resolveChecklistRiskAreaId(array $rawData, array $sourceRecommendation = [], int $index = 0, ?int $targetBarangayId = null): ?int
    {
        $focusFindings = collect($rawData['recommendation_context']['focus_findings'] ?? [])
            ->filter(fn ($finding) => ! empty($finding['risk_area_id']));

        if ($focusFindings->isEmpty()) {
            return null;
        }

        $selectedBarangayId = $targetBarangayId ?: (int) ($rawData['barangay_id']
            ?? data_get($rawData, 'selected_barangay_prediction.barangay_id')
            ?? 0);

        $candidateFindings = $selectedBarangayId > 0
            ? $focusFindings->filter(fn ($finding) => (int) ($finding['barangay_id'] ?? 0) === $selectedBarangayId)->values()
            : $focusFindings->values();

        if ($candidateFindings->isEmpty()) {
            $candidateFindings = $focusFindings->values();
        }

        $haystack = Str::lower(trim(implode(' ', array_filter([
            $sourceRecommendation['title'] ?? null,
            $sourceRecommendation['description'] ?? null,
        ]))));

        if ($haystack !== '') {
            $matched = $candidateFindings->first(function ($finding) use ($haystack) {
                $needles = array_filter([
                    Str::lower((string) ($finding['type_label'] ?? '')),
                    Str::lower((string) ($finding['condition_label'] ?? '')),
                    Str::lower((string) ($finding['street_or_sitio'] ?? '')),
                ]);

                foreach ($needles as $needle) {
                    if ($needle !== '' && str_contains($haystack, $needle)) {
                        return true;
                    }
                }

                return false;
            });

            if ($matched) {
                return (int) $matched['risk_area_id'];
            }
        }

        $fallback = $candidateFindings->get($index) ?? $candidateFindings->first();

        return $fallback ? (int) ($fallback['risk_area_id'] ?? 0) : null;
    }

    private function resolveChecklistRiskAreaSummary(array $rawData, ?int $riskAreaId): ?array
    {
        if (! $riskAreaId) {
            return null;
        }

        $matched = collect($rawData['recommendation_context']['focus_findings'] ?? [])
            ->first(fn ($finding) => (int) ($finding['risk_area_id'] ?? 0) === (int) $riskAreaId);

        return is_array($matched) ? $matched : null;
    }

    private function syncMitigationDraftActionsForAnalysis(array $savedPlans): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($savedPlans as $savedPlan) {
            if (! $savedPlan instanceof AiActionPlanChecklist) {
                continue;
            }

            $payload = $this->buildMitigationDraftPayloadFromChecklist($savedPlan);
            if (! $payload) {
                $skipped++;

                continue;
            }

            $existingAction = MitigationAction::query()
                ->where('ai_action_plan_checklist_id', $savedPlan->ai_action_plan_checklist_id)
                ->first();

            if ($existingAction) {
                if ($existingAction->status === 'draft' && $this->resolveMitigationSourceTypeForAnalysis($existingAction->toArray()) === 'ai_action_plan') {
                    $hasManualEdits = (bool) $existingAction->is_manually_edited
                        || filled($existingAction->responsible_team_id)
                        || filled($existingAction->responsible_user_id)
                        || filled($existingAction->implementation_notes)
                        || filled($existingAction->completion_notes)
                        || filled($existingAction->outcome_result)
                        || filled($existingAction->follow_up_notes)
                        || filled($existingAction->reviewed_at)
                        || filled($existingAction->approved_at);

                    if ($hasManualEdits) {
                        $skipped++;
                    } else {
                        $existingAction->update(array_merge($payload, [
                            'responsible_team_id' => $existingAction->responsible_team_id,
                            'responsible_user_id' => $existingAction->responsible_user_id,
                            'created_by' => $existingAction->created_by,
                        ]));
                        $updated++;
                    }
                } else {
                    $existingAction->update(['requires_revision_review' => true]);
                    $skipped++;
                }

                continue;
            }

            MitigationAction::query()->create($payload);
            $created++;
        }

        return [
            'created_draft_rows' => $created,
            'updated_draft_rows' => $updated,
            'skipped_existing_rows' => $skipped,
            'warning' => null,
        ];
    }

    private function buildMitigationDraftPayloadFromChecklist(AiActionPlanChecklist $checklist): ?array
    {
        if (! $checklist->barangay_id) {
            return null;
        }

        $checklist->loadMissing(['suggestions', 'riskArea.marker']);
        $firstSuggestion = $checklist->suggestions->sortByDesc('created_at')->first();
        $steps = collect($firstSuggestion?->steps ?: $checklist->checklist_items ?: [])
            ->filter()
            ->map(fn ($step) => '- '.trim((string) $step))
            ->values()
            ->all();
        $resources = collect($firstSuggestion?->resources_needed ?: [])
            ->filter()
            ->map(fn ($resource) => '- '.trim((string) $resource))
            ->values()
            ->all();

        $descriptionParts = array_filter([
            $checklist->plan_description ?: $checklist->source_description,
            ! empty($steps) ? "Suggested steps:\n".implode("\n", $steps) : null,
            ! empty($resources) ? "Resources needed:\n".implode("\n", $resources) : null,
            $firstSuggestion?->estimated_timeline ? "Suggested timeline: {$firstSuggestion->estimated_timeline}" : null,
        ]);

        return [
            'barangay_id' => (int) $checklist->barangay_id,
            'risk_area_id' => $checklist->risk_area_id ? (int) $checklist->risk_area_id : null,
            'barangay_prediction_checklist_id' => null,
            'ai_action_plan_checklist_id' => (int) $checklist->ai_action_plan_checklist_id,
            'responsible_team_id' => null,
            'responsible_user_id' => null,
            'title' => $checklist->plan_title ?: ($checklist->source_title ?: 'AI Mitigation Draft'),
            'category' => $this->inferMitigationCategoryFromChecklistForAnalysis($checklist),
            'priority_level' => strtolower((string) ($checklist->priority_level ?: 'medium')),
            'status' => 'draft',
            'source_type' => 'ai_action_plan',
            'target_date' => optional($checklist->target_date)->format('Y-m-d'),
            'completed_at' => null,
            'description' => implode("\n\n", $descriptionParts),
            'remarks' => $firstSuggestion?->estimated_timeline
                ? 'Auto-generated from AI action-plan checklist. Review before approval. Timeline suggestion: '.$firstSuggestion->estimated_timeline
                : 'Auto-generated from AI action-plan checklist. Review before approval.',
            'implementation_notes' => null,
            'completion_notes' => null,
            'outcome_result' => null,
            'effectiveness' => 'pending_evaluation',
            'follow_up_required' => false,
            'follow_up_notes' => null,
            'created_by' => Auth::user()?->user_id,
            'approved_by' => null,
            'approved_at' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'analysis_revision' => (int) ($checklist->analysis_revision ?? 1),
            'is_superseded' => false,
            'requires_revision_review' => false,
            'is_manually_edited' => false,
            'superseded_at' => null,
        ];
    }

    private function inferMitigationCategoryFromChecklistForAnalysis(AiActionPlanChecklist $checklist): string
    {
        $payloadCategory = data_get($checklist->ai_payload, 'recommended_category');
        $allowedCategories = [
            'electrical_safety',
            'water_supply',
            'response_readiness',
            'access_routing',
            'inspection_enforcement',
            'community_prevention',
            'evacuation_life_safety',
            'high_risk_area_monitoring',
            'environmental_preparedness',
            'other',
        ];

        if (is_string($payloadCategory) && in_array($payloadCategory, $allowedCategories, true)) {
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

    private function resolveMitigationSourceTypeForAnalysis(array $attributes): string
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

    private function parseActionPlanChecklistResponse(string $content): array
    {
        $cleaned = trim($content);
        $cleaned = preg_replace('/^```json\s*/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/^```\s*/', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\s*```$/', '', $cleaned) ?? $cleaned;

        $decoded = json_decode($cleaned, true);
        if (! is_array($decoded)) {
            return [];
        }

        $plans = $decoded['plans'] ?? null;

        return is_array($plans) ? $plans : [];
    }

    private function buildFallbackActionPlanChecklists(array $recommendations, array $rawData = []): array
    {
        return array_map(function ($recommendation) use ($rawData) {
            $priority = strtolower($recommendation['priority'] ?? 'medium');
            $contextPayload = [
                'recommendation' => $recommendation,
                'prediction_summary' => data_get($rawData, 'predictions.summary')
                    ?? data_get($rawData, 'model_prediction.output.summary')
                    ?? data_get($rawData, 'recommendation_context.summary')
                    ?? null,
                'action_plan_checklist' => [
                    'barangay_name' => data_get($rawData, 'selected_barangay_prediction.barangay_name')
                        ?? data_get($rawData, 'barangay_predictions.0.barangay_name')
                        ?? null,
                ],
            ];
            $fallbackData = $this->getFallbackExploreData(
                $recommendation['title'] ?? 'AI Recommendation',
                $recommendation['priority'] ?? 'Medium',
                $recommendation['description'] ?? null,
                $contextPayload
            );
            $steps = array_values(array_filter(array_map(
                fn ($step) => is_string($step) ? trim($step) : null,
                $fallbackData['steps'] ?? []
            )));

            return [
                'source_title' => $recommendation['title'] ?? 'AI Recommendation',
                'plan_title' => $recommendation['title'] ?? 'AI Action Plan',
                'plan_description' => $recommendation['description'] ?? null,
                'priority_level' => $priority,
                'success_indicator' => 'BFP confirms the action was completed and documented.',
                'target_days' => $priority === 'critical' ? 7 : ($priority === 'high' ? 14 : 30),
                'checklist_items' => ! empty($steps) ? array_slice($steps, 0, 6) : [
                    'Validate the recommendation in the field and confirm the exact risk condition.',
                    'Assign the responding officer or coordination lead for this recommendation.',
                    'Implement the corrective action described in the recommendation.',
                    'Recheck the affected area and record proof of completion for BFP review.',
                ],
            ];
        }, $recommendations);
    }

    private function normalizeActionPlanCount(array $plans, array $recommendations, array $rawData = []): array
    {
        if (empty($plans)) {
            return $this->buildFallbackActionPlanChecklists($recommendations, $rawData);
        }

        $normalizedPlans = [];
        foreach ($recommendations as $index => $recommendation) {
            $existingPlan = $plans[$index] ?? null;

            if (is_array($existingPlan) && ! empty($existingPlan['plan_title'])) {
                $normalizedPlans[] = $existingPlan;

                continue;
            }

            $fallbackPlan = $this->buildFallbackActionPlanChecklists([$recommendation], $rawData)[0];
            $normalizedPlans[] = $fallbackPlan;
        }

        return array_slice($normalizedPlans, 0, 10);
    }

    private function attachChecklistIdsToRecommendations(array $recommendations, array $savedPlans): array
    {
        return array_map(function ($recommendation) use ($savedPlans) {
            foreach ($savedPlans as $plan) {
                $sourceTitle = is_array($plan) ? ($plan['source_title'] ?? null) : ($plan->source_title ?? null);
                $planId = is_array($plan)
                    ? ($plan['ai_action_plan_checklist_id'] ?? null)
                    : ($plan->ai_action_plan_checklist_id ?? null);

                if ($sourceTitle && strcasecmp($sourceTitle, $recommendation['title'] ?? '') === 0) {
                    $recommendation['action_plan_checklist_id'] = $planId;
                    break;
                }
            }

            return $recommendation;
        }, $recommendations);
    }

    private function buildPersistableRecommendations(array $recommendations): array
    {
        return array_map(function ($rec) {
            return [
                'title' => $rec['title'] ?? null,
                'description' => $rec['description'] ?? null,
                'priority' => $rec['priority'] ?? null,
                'action_plan_checklist_id' => $rec['action_plan_checklist_id'] ?? null,
            ];
        }, $recommendations);
    }

    private function updateStoredAnalysisArtifacts(int $analysisId, array $analysis, array $rawData): void
    {
        $analysisRow = Analysis::query()->find($analysisId);
        if (! $analysisRow) {
            return;
        }

        $normalizedBarangayId = $this->scopeBarangayIdFromRawData($rawData);

        $analysisData = [
            'analysis_summary' => $analysis,
            'raw_data' => [
                'hourly_data' => $rawData['hourly_data'] ?? [],
                'daily_data' => $rawData['daily_data'] ?? [],
                'days_analyzed' => max(1, (int) ($rawData['days_analyzed'] ?? 30)),
                'peak_hour' => $rawData['peak_hour'] ?? null,
                'avg_response_time' => $rawData['avg_response_time'] ?? null,
                'total_personnel' => $rawData['total_personnel'] ?? 0,
                'available_personnel' => $rawData['available_personnel'] ?? 0,
                'total_equipment' => $rawData['total_equipment'] ?? 0,
                'available_equipment' => $rawData['available_equipment'] ?? 0,
                'risk_factors' => $rawData['risk_factors'] ?? [],
                'weather_context' => $rawData['weather_context'] ?? null,
                'mitigation_context' => $rawData['mitigation_context'] ?? null,
                'recommendation_context' => $rawData['recommendation_context'] ?? null,
                'model_prediction' => $rawData['model_prediction'] ?? null,
                'model_metadata' => $analysis['model_metadata'] ?? null,
                'workflow_context' => $analysis['workflow_context'] ?? null,
                'barangay_predictions' => $rawData['barangay_predictions'] ?? [],
                'barangay_id' => $normalizedBarangayId,
                'selected_barangay_prediction' => $rawData['selected_barangay_prediction'] ?? null,
                'prediction_context' => $rawData['prediction_context'] ?? null,
                'prediction_scope' => $rawData['prediction_scope'] ?? 'current_month',
                'analysis_kind' => $rawData['analysis_kind'] ?? (($rawData['prediction_scope'] ?? null) === 'next_month' ? 'forecast' : 'actual_analysis'),
                'historical_window' => $rawData['historical_window'] ?? '3',
                'chart_context' => $rawData['chart_context'] ?? null,
                'checklist_debug' => $analysis['checklist_debug'] ?? null,
            ],
            'ai_analysis' => $analysis['ai_explanation'] ?? null,
            'generated_at' => now()->toISOString(),
            'ai_source' => $analysis['ai_source'] ?? false,
        ];

        $analysisRow->update([
            'analysis_data' => $analysisData,
            'recommendations' => $this->buildPersistableRecommendations($analysis['recommendations'] ?? []),
            'predictions' => $analysis['predictions'] ?? null,
            'sector' => $rawData['location_name'] ?? $analysisRow->sector,
            'period' => 'Last '.max(1, (int) ($rawData['days_analyzed'] ?? 30)).' days',
            'risk_level' => $analysis['risk_level'] ?? $analysisRow->risk_level,
            'confidence_data' => isset($analysis['confidence_score']) ? (string) $analysis['confidence_score'] : $analysisRow->confidence_data,
            'incident_count' => (int) ($rawData['total_incidents'] ?? $analysisRow->incident_count ?? 0),
        ]);
    }

    private function syncChecklistItems(AiActionPlanChecklist $checklist, array $items): void
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

    /**
     * Structure AI analysis into usable format with proper priority sorting
     */
    private function structureAIAnalysis($aiContent, $data)
    {
        $recommendations = [];
        $lines = explode("\n", $aiContent);
        $predictions = $this->extractPredictionData($aiContent, $data);
        $confidenceContext = $this->calculateAnalysisConfidence($data, true);

        // Parse AI response for recommendations
        foreach ($lines as $line) {
            $line = trim($line);

            // Look for numbered or bullet points
            if (preg_match('/^(\d+)[\.\)]\s+(.+)/', $line, $matches) ||
                preg_match('/^[•\-*]\s+(.+)/', $line, $matches)) {

                $content = isset($matches[2]) ? $matches[2] : $matches[1];

                // Determine priority based on content keywords
                $priority = $this->determinePriority($content, $data);

                $recommendations[] = [
                    'title' => substr($content, 0, 80),
                    'description' => $content,
                    'priority' => $priority,
                ];
            }
        }

        // Sort recommendations by priority
        usort($recommendations, function ($a, $b) {
            $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
            $priorityA = strtolower($a['priority']);
            $priorityB = strtolower($b['priority']);

            return ($priorityOrder[$priorityA] ?? 3) - ($priorityOrder[$priorityB] ?? 3);
        });

        // If no recommendations parsed, generate data-driven ones
        if (empty($recommendations)) {
            Log::warning('No recommendations parsed from AI, using data-driven');
            $recommendations = $this->generateDataDrivenRecommendations($data);
            usort($recommendations, function ($a, $b) {
                $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
                $priorityA = strtolower($a['priority']);
                $priorityB = strtolower($b['priority']);

                return ($priorityOrder[$priorityA] ?? 3) - ($priorityOrder[$priorityB] ?? 3);
            });
        }

        return [
            'risk_score' => $data['risk_score'],
            'risk_level' => $data['risk_level'],
            'confidence_score' => $confidenceContext['score'],
            'confidence_context' => $confidenceContext,
            'risk_factors' => $data['risk_factors'],
            'total_incidents' => $data['total_incidents'],
            'fire_incidents' => $data['fire_incidents'],
            'incidents_by_type' => $data['incidents_by_type'],
            'hourly_data' => $data['hourly_data'],
            'hourly_labels' => range(0, 23),
            'daily_data' => $data['daily_data'],
            'peak_hour' => $data['peak_hour'],
            'avg_response_time' => $data['avg_response_time'],
            'total_personnel' => $data['total_personnel'],
            'available_personnel' => $data['available_personnel'],
            'total_equipment' => $data['total_equipment'],
            'available_equipment' => $data['available_equipment'],
            'weather_context' => $data['weather_context'] ?? null,
            'prediction_scope' => $data['prediction_scope'] ?? 'current_month',
            'prediction_context' => $data['prediction_context'] ?? null,
            'predictions' => $predictions,
            'recommendations' => array_slice($recommendations, 0, 6),
            'ai_explanation' => substr($aiContent, 0, 500),
        ];
    }

    /**
     * Determine priority based on content
     */
    private function determinePriority($text, $data)
    {
        $textLower = strtolower($text);

        if (strpos($textLower, 'critical') !== false ||
            strpos($textLower, 'emergency') !== false ||
            strpos($textLower, 'immediate') !== false) {
            return 'Critical';
        }

        if (strpos($textLower, 'high') !== false ||
            strpos($textLower, 'urgent') !== false) {
            return 'High';
        }

        if ($data['risk_score'] >= 70) {
            return 'High';
        }
        if ($data['risk_score'] >= 50) {
            return 'Medium';
        }

        return 'Low';
    }

    /**
     * Generate data-driven recommendations
     */
    private function generateDataDrivenRecommendations($data)
    {
        $recommendations = [];
        $localizedFindings = collect($data['recommendation_context']['focus_findings'] ?? []);
        $topLocalizedFinding = $localizedFindings->first();
        $selectedBarangayName = $data['selected_barangay_prediction']['barangay_name'] ?? null;
        $topHotspotBarangay = collect($data['barangay_predictions'] ?? [])
            ->sortByDesc(fn ($item) => (float) ($item['probability_percent'] ?? 0))
            ->pluck('barangay_name')
            ->filter()
            ->first();
        $forecastFocusBarangay = $selectedBarangayName ?: $topHotspotBarangay;
        $topFindingPlace = $this->formatRecommendationFindingPlace($topLocalizedFinding);
        $topFindingCondition = $this->formatRecommendationFindingCondition($topLocalizedFinding);
        $topFindingType = $this->formatRecommendationFindingType($topLocalizedFinding);

        foreach ($localizedFindings->take(4) as $finding) {
            $typeLabel = strtolower((string) ($finding['type_label'] ?? 'risk'));
            $condition = $finding['condition_label'] ?? $typeLabel;
            $barangayName = $finding['barangay_name'] ?? 'target barangay';
            $placeLabel = $finding['street_or_sitio']
                ? "{$finding['street_or_sitio']}, {$barangayName}"
                : $barangayName;
            $location = $finding['street_or_sitio'] ? " at {$finding['street_or_sitio']}" : '';
            $priority = in_array(strtolower((string) ($finding['assessment_level'] ?? '')), ['high', 'critical'], true) ? 'High' : 'Medium';

            if (str_contains($typeLabel, 'electrical')) {
                $recommendations[] = [
                    'title' => "Inspect {$condition} in {$placeLabel}",
                    'description' => "Verify and correct {$condition}{$location} in {$barangayName}, with emphasis on unsafe wiring runs, overloaded taps, and exposed connections documented in field findings.",
                    'priority' => $priority,
                ];

                continue;
            }

            if (str_contains($typeLabel, 'lpg') || str_contains($typeLabel, 'gas') || str_contains($typeLabel, 'cooking')) {
                $recommendations[] = [
                    'title' => "Check {$condition} in {$placeLabel}",
                    'description' => "Inspect {$condition}{$location} in {$barangayName}, confirm regulator and hose safety, and remove fire-prone cooking setups near homes or heat sources.",
                    'priority' => $priority,
                ];

                continue;
            }

            if (str_contains($typeLabel, 'hydrant') || str_contains($typeLabel, 'response capability')) {
                $recommendations[] = [
                    'title' => "Restore hydrant or water access in {$placeLabel}",
                    'description' => "Resolve {$condition}{$location} in {$barangayName}, especially blocked or unavailable hydrant access that can slow suppression during the forecast window.",
                    'priority' => 'High',
                ];

                continue;
            }

            if (str_contains($typeLabel, 'structural') || str_contains($typeLabel, 'housing') || str_contains($typeLabel, 'occupancy')) {
                $recommendations[] = [
                    'title' => "Inspect {$condition} in {$placeLabel}",
                    'description' => "Prioritize {$condition}{$location} in {$barangayName} and check housing spacing, exit access, and combustible building conditions affecting fire spread.",
                    'priority' => $priority,
                ];

                continue;
            }

            $recommendations[] = [
                'title' => "Address {$condition} in {$placeLabel}",
                'description' => "Follow up on {$condition}{$location} in {$barangayName} and assign a field team to verify, document, and mitigate the specific condition captured in the latest risk assessment.",
                'priority' => $priority,
            ];
        }

        if ($data['peak_hour']) {
            $peakHourLabel = sprintf('%02d:00', (int) $data['peak_hour']);
            $peakPatrolTarget = $topFindingPlace ?: ($forecastFocusBarangay ?: 'the highest-risk coverage area');
            $recommendations[] = [
                'title' => "Increase patrol visibility in {$peakPatrolTarget}",
                'description' => "Deploy added patrol or standby presence around {$peakHourLabel} in {$peakPatrolTarget}, when current incident timing data shows the strongest likelihood of fire activity.",
                'priority' => 'Medium',
            ];
        }

        if ($data['fire_incidents'] > 5) {
            $inspectionTarget = $topFindingPlace ?: ($forecastFocusBarangay ?: 'priority barangays under station coverage');
            $inspectionType = $topFindingType ?: 'fire-risk';
            $recommendations[] = [
                'title' => "Conduct {$inspectionType} inspections in {$inspectionTarget}",
                'description' => "Schedule focused inspection work in {$inspectionTarget} after {$data['fire_incidents']} recorded fire incidents, with attention to the conditions most often linked to current hotspot forecasts.",
                'priority' => 'High',
            ];
        }

        if ($data['avg_response_time'] && $data['avg_response_time'] > 15) {
            $routeTarget = $topFindingPlace ?: ($forecastFocusBarangay ?: 'the station coverage area');
            $recommendations[] = [
                'title' => "Optimize access routes toward {$routeTarget}",
                'description' => "Review responder approach routes toward {$routeTarget} to reduce the current {$data['avg_response_time']}-minute average response time and identify road or access bottlenecks affecting dispatch speed.",
                'priority' => 'High',
            ];
        }

        if ($data['available_personnel'] < $data['total_personnel'] * 0.6) {
            $recommendations[] = [
                'title' => 'Address personnel availability',
                'description' => "Only {$data['available_personnel']} of {$data['total_personnel']} personnel available",
                'priority' => 'Critical',
            ];
        }

        if ($topFindingCondition || $forecastFocusBarangay) {
            $awarenessTarget = $topFindingPlace ?: ($forecastFocusBarangay ?: 'priority hotspot barangays');
            $awarenessCondition = $topFindingCondition ?: ($topFindingType ?: 'documented fire-risk conditions');
            $recommendations[] = [
                'title' => "Brief households on {$awarenessCondition} in {$awarenessTarget}",
                'description' => "Launch a focused prevention briefing in {$awarenessTarget} that explains the exact local fire driver now contributing to the forecast, especially {$awarenessCondition}, instead of using a general awareness campaign.",
                'priority' => 'Medium',
            ];
        }

        if (! empty($data['prediction_context']['likely_peak_hours']) || ($data['avg_response_time'] && $data['avg_response_time'] > 15)) {
            $protocolTarget = $forecastFocusBarangay ?: 'priority forecast areas';
            $peakHours = implode(', ', array_slice($data['prediction_context']['likely_peak_hours'] ?? [], 0, 3));
            $protocolSuffix = $peakHours !== '' ? " during likely peak windows ({$peakHours})" : '';
            $recommendations[] = [
                'title' => "Update dispatch protocol for {$protocolTarget}",
                'description' => "Refine SOPs, dispatch notes, and pre-arrival guidance for {$protocolTarget}{$protocolSuffix}, using the latest forecast drivers and response performance data.",
                'priority' => 'Low',
            ];
        }

        $recommendations = collect($recommendations)
            ->unique(fn ($item) => strtolower(($item['title'] ?? '').'|'.($item['description'] ?? '')))
            ->values()
            ->all();

        return $recommendations;
    }

    private function formatRecommendationFindingPlace($finding): ?string
    {
        if (! $finding) {
            return null;
        }

        $barangayName = $finding['barangay_name'] ?? null;
        $streetOrSitio = $finding['street_or_sitio'] ?? null;

        if ($streetOrSitio && $barangayName) {
            return "{$streetOrSitio}, {$barangayName}";
        }

        return $streetOrSitio ?: $barangayName;
    }

    private function formatRecommendationFindingCondition($finding): ?string
    {
        if (! $finding) {
            return null;
        }

        return $finding['condition_label']
            ?? $finding['type_label']
            ?? $finding['risk_type']
            ?? $finding['hazard_category']
            ?? null;
    }

    private function formatRecommendationFindingType($finding): ?string
    {
        if (! $finding) {
            return null;
        }

        $type = $finding['type_label']
            ?? $finding['risk_type']
            ?? $finding['hazard_category']
            ?? $finding['entry_type']
            ?? null;

        return $type ? Str::lower((string) $type) : null;
    }

    /**
     * Generate fallback analysis
     */
    private function generateDataDrivenAnalysis($data)
    {
        $recommendations = $this->generateDataDrivenRecommendations($data);
        $predictions = $this->generateDataDrivenPrediction($data);
        $confidenceContext = $this->calculateAnalysisConfidence($data, false);

        $explanation = "Based on {$data['total_incidents']} incidents over {$data['days_analyzed']} days, ".
                      "risk level is {$data['risk_level']} with {$data['fire_incidents']} fire incidents. ".
                      'Peak time: '.($data['peak_hour'] ? $data['peak_hour'].':00' : 'not determined');

        return [
            'risk_score' => $data['risk_score'],
            'risk_level' => $data['risk_level'],
            'confidence_score' => $confidenceContext['score'],
            'confidence_context' => $confidenceContext,
            'risk_factors' => $data['risk_factors'],
            'total_incidents' => $data['total_incidents'],
            'fire_incidents' => $data['fire_incidents'],
            'incidents_by_type' => $data['incidents_by_type'],
            'hourly_data' => $data['hourly_data'],
            'hourly_labels' => range(0, 23),
            'daily_data' => $data['daily_data'],
            'peak_hour' => $data['peak_hour'],
            'avg_response_time' => $data['avg_response_time'],
            'total_personnel' => $data['total_personnel'],
            'available_personnel' => $data['available_personnel'],
            'total_equipment' => $data['total_equipment'],
            'available_equipment' => $data['available_equipment'],
            'weather_context' => $data['weather_context'] ?? null,
            'mitigation_context' => $data['mitigation_context'] ?? null,
            'recommendation_context' => $data['recommendation_context'] ?? null,
            'barangay_predictions' => $data['barangay_predictions'] ?? [],
            'prediction_scope' => $data['prediction_scope'] ?? 'current_month',
            'prediction_context' => $data['prediction_context'] ?? null,
            'chart_context' => $data['chart_context'] ?? null,
            'predictions' => $predictions,
            'recommendations' => $recommendations,
            'ai_explanation' => $explanation,
            'ai_source' => false,
        ];
    }

    private function structureAIAnalysisSeparated($predictionAiContent, $recommendationAiContent, $data)
    {
        $recommendations = $this->extractRecommendationsFromAi($recommendationAiContent, $data);
        $predictions = $this->extractPredictionData($predictionAiContent, $data);
        $confidenceContext = $this->calculateAnalysisConfidence($data, true);

        usort($recommendations, function ($a, $b) {
            $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
            $priorityA = strtolower($a['priority']);
            $priorityB = strtolower($b['priority']);

            return ($priorityOrder[$priorityA] ?? 3) - ($priorityOrder[$priorityB] ?? 3);
        });

        if (empty($recommendations)) {
            $recommendations = $this->generateDataDrivenRecommendations($data);
            usort($recommendations, function ($a, $b) {
                $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
                $priorityA = strtolower($a['priority']);
                $priorityB = strtolower($b['priority']);

                return ($priorityOrder[$priorityA] ?? 3) - ($priorityOrder[$priorityB] ?? 3);
            });
        }

        return [
            'risk_score' => $data['risk_score'],
            'risk_level' => $data['risk_level'],
            'confidence_score' => $confidenceContext['score'],
            'confidence_context' => $confidenceContext,
            'risk_factors' => $data['risk_factors'],
            'total_incidents' => $data['total_incidents'],
            'fire_incidents' => $data['fire_incidents'],
            'incidents_by_type' => $data['incidents_by_type'],
            'hourly_data' => $data['hourly_data'],
            'hourly_labels' => range(0, 23),
            'daily_data' => $data['daily_data'],
            'peak_hour' => $data['peak_hour'],
            'avg_response_time' => $data['avg_response_time'],
            'total_personnel' => $data['total_personnel'],
            'available_personnel' => $data['available_personnel'],
            'total_equipment' => $data['total_equipment'],
            'available_equipment' => $data['available_equipment'],
            'weather_context' => $data['weather_context'] ?? null,
            'mitigation_context' => $data['mitigation_context'] ?? null,
            'recommendation_context' => $data['recommendation_context'] ?? null,
            'barangay_predictions' => $data['barangay_predictions'] ?? [],
            'prediction_scope' => $data['prediction_scope'] ?? 'current_month',
            'prediction_context' => $data['prediction_context'] ?? null,
            'chart_context' => $data['chart_context'] ?? null,
            'predictions' => $predictions,
            'recommendations' => array_slice($recommendations, 0, 6),
            'ai_explanation' => substr($predictionAiContent, 0, 300)."\n\n".substr($recommendationAiContent, 0, 300),
        ];
    }

    private function calculateAnalysisConfidence(array $data, bool $aiResponseAvailable): array
    {
        $score = 20;
        $factors = [];
        $historicalYears = (int) data_get($data, 'chart_context.historical_window_meta.year_count', 0);
        if ($historicalYears > 0) {
            $points = min(15, $historicalYears * 5);
            $score += $points;
            $factors[] = "{$historicalYears} historical comparison year(s) (+{$points})";
        } else {
            $factors[] = 'No historical comparison years (+0)';
        }

        $incidentCount = (int) ($data['total_incidents'] ?? 0);
        $incidentPoints = $incidentCount >= 10 ? 10 : ($incidentCount >= 3 ? 7 : ($incidentCount >= 1 ? 4 : 0));
        $score += $incidentPoints;
        $factors[] = "{$incidentCount} scoped actual incident record(s) (+{$incidentPoints})";

        $predictions = collect($data['barangay_predictions'] ?? []);
        $selectedBarangayId = $this->scopeBarangayIdFromRawData($data);
        $expectedBarangays = $selectedBarangayId
            ? 1
            : max(1, $this->resolveStationBarangays((int) ($data['station_id'] ?? 0))->count());
        $coverageRatio = min(1, $predictions->count() / $expectedBarangays);
        $coveragePoints = (int) round($coverageRatio * 12);
        $score += $coveragePoints;
        $factors[] = $predictions->count()."/{$expectedBarangays} barangay forecast(s) available (+{$coveragePoints})";

        $trainedPredictionCount = $predictions->where('prediction_source', 'trained_model')->count();
        if ($trainedPredictionCount > 0) {
            $score += 8;
            $factors[] = "{$trainedPredictionCount} trained-model barangay forecast(s) (+8)";
        } elseif ($predictions->isNotEmpty()) {
            $factors[] = 'Barangay forecasts use deterministic fallback (+0 model points)';
        }

        if (! empty($data['weather_context'])) {
            $score += 6;
            $factors[] = 'Weather context available (+6)';
        }

        $riskFindingCount = count(data_get($data, 'recommendation_context.focus_findings', []));
        if ($riskFindingCount > 0) {
            $points = min(6, $riskFindingCount * 2);
            $score += $points;
            $factors[] = "{$riskFindingCount} verified risk finding(s) (+{$points})";
        }

        $currentFireCount = (int) data_get($data, 'prediction_context.current_month_fire_incidents', 0);
        $previousFireCount = (int) data_get($data, 'prediction_context.previous_month_fire_incidents', 0);
        if (($currentFireCount + $previousFireCount) > 0) {
            $score += 5;
            $factors[] = "Current/previous fire trend evidence {$currentFireCount}/{$previousFireCount} (+5)";
        }

        if ($aiResponseAvailable) {
            $score += 3;
            $factors[] = 'AI prediction and recommendation responses parsed (+3)';
        }

        $score = max(30, min(95, $score));

        return [
            'score' => $score,
            'method' => 'evidence_coverage_v1',
            'meaning' => 'Confidence measures evidence coverage and model availability; it is separate from fire-risk probability.',
            'factors' => $factors,
            'historical_year_count' => $historicalYears,
            'scoped_incident_count' => $incidentCount,
            'barangay_coverage_ratio' => round($coverageRatio, 3),
            'trained_prediction_count' => $trainedPredictionCount,
            'current_month_fire_incidents' => $currentFireCount,
            'previous_month_fire_incidents' => $previousFireCount,
        ];
    }

    private function extractRecommendationsFromAi($recommendationAiContent, $data)
    {
        $recommendations = [];
        $lines = preg_split("/\r\n|\n|\r/", $recommendationAiContent);
        $currentRecommendation = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || stripos($line, 'RECOMMENDATIONS:') === 0 || stripos($line, 'SUMMARY:') === 0) {
                continue;
            }

            if (preg_match('/^\d+[\.\)]\s*(.+?)\s*\|\s*(Critical|High|Medium|Low)\s*$/i', $line, $matches)) {
                if ($currentRecommendation) {
                    $recommendations[] = $currentRecommendation;
                }

                $currentRecommendation = [
                    'title' => trim($matches[1]),
                    'description' => '',
                    'priority' => ucfirst(strtolower($matches[2])),
                ];

                continue;
            }

            if (preg_match('/^Description:\s*(.+)$/i', $line, $matches)) {
                if ($currentRecommendation) {
                    $currentRecommendation['description'] = trim($matches[1]);
                }

                continue;
            }

            if ((preg_match('/^(\d+)[\.\)]\s+(.+)/', $line, $matches) || preg_match('/^[•\-*]\s+(.+)/', $line, $matches)) && ! $currentRecommendation) {
                $content = isset($matches[2]) ? $matches[2] : $matches[1];
                $recommendations[] = [
                    'title' => substr($content, 0, 80),
                    'description' => $content,
                    'priority' => $this->determinePriority($content, $data),
                ];
            } elseif ($currentRecommendation && $currentRecommendation['description'] === '') {
                $currentRecommendation['description'] = $line;
            }
        }

        if ($currentRecommendation) {
            $recommendations[] = $currentRecommendation;
        }

        return array_values(array_filter($recommendations, function ($recommendation) {
            return ! empty($recommendation['title']) && ! empty($recommendation['description']);
        }));
    }

    /**
     * Store analysis in database
     */
    private function storeAnalysis($locationId, $days, $analysis, $rawData)
    {
        try {
            // Get station ID from raw data
            $stationId = $rawData['station_id'] ?? null;

            if (! $stationId) {
                Log::warning('No station ID found in raw data, skipping storage');

                return null;
            }

            // Get incident count from raw data
            $incidentCount = $rawData['total_incidents'] ?? 0;

            // Prepare sector name
            $sectorName = $rawData['location_name'];

            $normalizedBarangayId = $this->scopeBarangayIdFromRawData($rawData);

            // Prepare analysis_data JSON
            $analysisPayload = [
                'analysis_summary' => $analysis,
                'raw_data' => [
                    'hourly_data' => $rawData['hourly_data'],
                    'daily_data' => $rawData['daily_data'],
                    'days_analyzed' => max(1, (int) ($rawData['days_analyzed'] ?? $days)),
                    'peak_hour' => $rawData['peak_hour'],
                    'avg_response_time' => $rawData['avg_response_time'],
                    'total_personnel' => $rawData['total_personnel'],
                    'available_personnel' => $rawData['available_personnel'],
                    'total_equipment' => $rawData['total_equipment'],
                    'available_equipment' => $rawData['available_equipment'],
                    'risk_factors' => $rawData['risk_factors'],
                    'weather_context' => $rawData['weather_context'] ?? null,
                    'mitigation_context' => $rawData['mitigation_context'] ?? null,
                    'recommendation_context' => $rawData['recommendation_context'] ?? null,
                    'model_prediction' => $rawData['model_prediction'] ?? null,
                    'model_metadata' => $analysis['model_metadata'] ?? null,
                    'workflow_context' => $analysis['workflow_context'] ?? null,
                    'barangay_predictions' => $rawData['barangay_predictions'] ?? [],
                    'barangay_id' => $normalizedBarangayId,
                    'selected_barangay_prediction' => $rawData['selected_barangay_prediction'] ?? null,
                    'prediction_context' => $rawData['prediction_context'] ?? null,
                    'prediction_scope' => $rawData['prediction_scope'] ?? 'current_month',
                    'analysis_kind' => $rawData['analysis_kind'] ?? 'actual_analysis',
                    'historical_window' => $rawData['historical_window'] ?? '3',
                    'chart_context' => $rawData['chart_context'] ?? null,
                    'checklist_debug' => $analysis['checklist_debug'] ?? null,
                ],
                'ai_analysis' => $analysis['ai_explanation'] ?? null,
                'generated_at' => now()->toISOString(),
                'ai_source' => $analysis['ai_source'] ?? false,
            ];

            $analysisDataJson = json_encode($analysisPayload);

            // Prepare recommendations JSON
            $recommendationsJson = json_encode($this->buildPersistableRecommendations($analysis['recommendations'] ?? []));

            $predictionsJson = json_encode($analysis['predictions'] ?? null);

            $existingAnalysis = $this->findExistingScopedAnalysis((int) $stationId, $rawData);
            $currentRevision = $existingAnalysis
                ? max(1, (int) ($existingAnalysis->revision_number ?? 1))
                : 0;
            $nextRevision = $currentRevision + 1;
            $payload = [
                'sector' => $sectorName,
                'period' => "Last {$days} days",
                'risk_level' => $analysis['risk_level'],
                'confidence_data' => isset($analysis['confidence_score']) ? (string) $analysis['confidence_score'] : null,
                'analysis_data' => $analysisDataJson,
                'recommendations' => $recommendationsJson,
                'predictions' => $predictionsJson,
                'incident_count' => $incidentCount,
                'station_id' => $stationId,
                'revision_number' => $nextRevision,
                'updated_at' => now(),
            ];

            if ($existingAnalysis && ! ($rawData['replace_existing'] ?? false)) {
                throw new \RuntimeException('A saved result already exists for this station, barangay scope, month, and run type. Confirm replacement before running again.');
            }

            if ($existingAnalysis) {
                DB::table('analysis_revision_snapshots')->updateOrInsert(
                    [
                        'analysis_id' => $existingAnalysis->analysis_id,
                        'revision_number' => $currentRevision,
                    ],
                    [
                        'snapshot_data' => json_encode([
                            'sector' => $existingAnalysis->sector,
                            'period' => $existingAnalysis->period,
                            'risk_level' => $existingAnalysis->risk_level,
                            'confidence_data' => $existingAnalysis->confidence_data,
                            'analysis_data' => $existingAnalysis->analysis_data,
                            'recommendations' => $existingAnalysis->recommendations,
                            'predictions' => $existingAnalysis->predictions,
                            'incident_count' => $existingAnalysis->incident_count,
                            'probability' => $existingAnalysis->probability,
                            'station_id' => $existingAnalysis->station_id,
                            'created_at' => optional($existingAnalysis->created_at)?->toISOString(),
                            'updated_at' => optional($existingAnalysis->updated_at)?->toISOString(),
                        ]),
                        'replaced_by' => Auth::user()?->user_id,
                        'replaced_at' => now(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                DB::table('analysis')
                    ->where('analysis_id', $existingAnalysis->analysis_id)
                    ->update($payload);

                $analysisId = (int) $existingAnalysis->analysis_id;
            } else {
                $payload['created_at'] = now();
                $analysisId = DB::table('analysis')->insertGetId($payload, 'analysis_id');
            }

            Log::info('Analysis stored successfully', [
                'analysis_id' => $analysisId,
                'station_id' => $stationId,
                'incident_count' => $incidentCount,
                'prediction_scope' => $rawData['prediction_scope'] ?? 'current_month',
                'barangay_id' => $normalizedBarangayId,
                'overwrote_existing_scope' => (bool) $existingAnalysis,
                'revision_number' => $nextRevision,
            ]);

            return $analysisId;

        } catch (\Exception $e) {
            Log::error('Failed to store analysis: '.$e->getMessage());

            // Don't throw, just log
            return null;
        }
    }

    /**
     * Get historical analyses from database
     */
    public function getHistoricalAnalyses(Request $request)
    {
        try {
            $user = Auth::user();
            $stationOfficer = StationOfficer::where('user_id', $user->user_id)->first();

            if (! $stationOfficer) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $requestedScope = $this->scopeSignatureFromRequest($request);

            $analyses = Analysis::query()
                ->where('station_id', $stationOfficer->station_id)
                ->latest('created_at')
                ->get()
                ->filter(function (Analysis $analysis) use ($stationOfficer, $requestedScope) {
                    $candidateData = is_array($analysis->analysis_data) ? $analysis->analysis_data : [];
                    $candidateRaw = $candidateData['raw_data'] ?? [];
                    $candidate = $this->scopeSignatureFromRawData($candidateRaw);

                    return (int) ($analysis->station_id ?? 0) === (int) $stationOfficer->station_id
                        && $candidate['prediction_scope'] === $requestedScope['prediction_scope']
                        && $candidate['barangay_id'] === $requestedScope['barangay_id']
                        && $candidate['target_month_label'] === $requestedScope['target_month_label'];
                })
                ->take(10)
                ->map(function (Analysis $analysis) {
                    $analysisData = is_array($analysis->analysis_data) ? $analysis->analysis_data : [];
                    $rawData = $analysisData['raw_data'] ?? [];

                    return (object) [
                        'analysis_id' => $analysis->analysis_id,
                        'analysis_revision' => max(1, (int) ($analysis->revision_number ?? 1)),
                        'sector' => $analysis->sector,
                        'period' => $analysis->period,
                        'risk_level' => $analysis->risk_level,
                        'confidence_data' => $analysis->confidence_data,
                        'incident_count' => $analysis->incident_count,
                        'station_id' => $analysis->station_id,
                        'created_at' => optional($analysis->created_at)?->toISOString(),
                        'prediction_scope' => $rawData['prediction_scope'] ?? 'current_month',
                        'analysis_kind' => $rawData['analysis_kind'] ?? (($rawData['prediction_scope'] ?? null) === 'next_month' ? 'forecast' : 'actual_analysis'),
                        'barangay_id' => $this->scopeBarangayIdFromRawData($rawData),
                        'selected_barangay_name' => data_get($rawData, 'selected_barangay_prediction.barangay_name'),
                        'target_month_label' => data_get($rawData, 'prediction_context.target_month_label'),
                    ];
                });

            $analyses = collect($analyses)->map(function ($analysis) {
                $analysis->confidence_score = is_numeric($analysis->confidence_data)
                    ? (int) $analysis->confidence_data
                    : $analysis->confidence_data;

                return $analysis;
            })->values();

            return response()->json([
                'success' => true,
                'data' => $analyses,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching historical analyses: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'data' => [],
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function getLatestAnalysisSnapshot(Request $request)
    {
        try {
            $user = Auth::user();
            $stationOfficer = StationOfficer::where('user_id', $user->user_id)->first();

            if (! $stationOfficer) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                ]);
            }

            $requestedScope = $this->scopeSignatureFromRequest($request);

            $stationAnalyses = Analysis::query()
                ->where('station_id', $stationOfficer->station_id)
                ->latest('updated_at')
                ->get();

            $latestAnalysis = $stationAnalyses->first(function (Analysis $analysis) use ($requestedScope, $stationOfficer) {
                $candidateData = is_array($analysis->analysis_data) ? $analysis->analysis_data : [];
                $candidateRaw = $candidateData['raw_data'] ?? [];
                $candidate = $this->scopeSignatureFromRawData($candidateRaw);

                return (int) ($analysis->station_id ?? 0) === (int) $stationOfficer->station_id
                    && $candidate['prediction_scope'] === $requestedScope['prediction_scope']
                    && $candidate['barangay_id'] === $requestedScope['barangay_id']
                    && $candidate['target_month_label'] === $requestedScope['target_month_label'];
            });

            if (! $latestAnalysis) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                ]);
            }

            $recentAnalyses = $stationAnalyses->sortByDesc('created_at')->take(20)->values();

            $latestPerBarangay = [];

            foreach ($recentAnalyses as $analysis) {
                $analysisData = is_array($analysis->analysis_data) ? $analysis->analysis_data : [];
                $rawData = $analysisData['raw_data'] ?? [];
                $predictionContext = $rawData['prediction_context'] ?? [];
                $predictionLabel = $predictionContext['target_period_label'] ?? null;
                $barangayPredictions = $rawData['barangay_predictions'] ?? [];

                if (! is_array($barangayPredictions)) {
                    continue;
                }

                foreach ($barangayPredictions as $prediction) {
                    $barangayId = (int) ($prediction['barangay_id'] ?? 0);

                    if (! $barangayId || isset($latestPerBarangay[$barangayId])) {
                        continue;
                    }

                    $prediction['analysis_id'] = $analysis->analysis_id;
                    $prediction['analysis_created_at'] = optional($analysis->created_at)?->toISOString();
                    $prediction['prediction_target_label'] = $predictionLabel;
                    $latestPerBarangay[$barangayId] = $prediction;
                }
            }

            $analysisData = is_array($latestAnalysis->analysis_data) ? $latestAnalysis->analysis_data : [];
            $analysisSummary = $analysisData['analysis_summary'] ?? [];
            $rawData = $analysisData['raw_data'] ?? [];
            $selectedBarangayId = $requestedScope['barangay_id']
                ?? $this->scopeBarangayIdFromRawData($rawData);
            $daysAnalyzed = max(1, (int) ($rawData['days_analyzed'] ?? 30));
            $historicalWindow = (string) ($request->input('historical_window') ?: ($rawData['historical_window'] ?? '3'));
            $liveScopedRawData = $this->fetchRealTimeData(
                (int) $stationOfficer->station_id,
                $daysAnalyzed,
                $requestedScope['prediction_scope'],
                $selectedBarangayId,
                $historicalWindow,
                false
            );
            $liveChartContext = $this->mergeLiveChartContext(
                (int) $stationOfficer->station_id,
                $liveScopedRawData['chart_context'] ?? ($rawData['chart_context'] ?? null),
                $selectedBarangayId
            );

            $savedBarangayPredictions = is_array($rawData['barangay_predictions'] ?? null)
                ? array_values($rawData['barangay_predictions'])
                : [];
            $fallbackBarangayPredictions = ! empty($savedBarangayPredictions)
                ? $savedBarangayPredictions
                : array_values($latestPerBarangay);
            $liveBarangayPredictionSet = is_array($liveScopedRawData['barangay_predictions'] ?? null)
                ? array_values($liveScopedRawData['barangay_predictions'])
                : [];

            $liveSelectedPrediction = $liveScopedRawData['selected_barangay_prediction']
                ?? ($selectedBarangayId ? ($latestPerBarangay[$selectedBarangayId] ?? null) : null);
            $liveBarangayPredictions = $selectedBarangayId && $liveSelectedPrediction
                ? [$liveSelectedPrediction]
                : (! empty($liveBarangayPredictionSet) ? $liveBarangayPredictionSet : $fallbackBarangayPredictions);
            $liveModelPrediction = $liveScopedRawData['model_prediction'] ?? ($rawData['model_prediction'] ?? null);
            $livePredictionContext = $liveScopedRawData['prediction_context'] ?? ($rawData['prediction_context'] ?? null);
            $locationName = $liveScopedRawData['location_name'] ?? $latestAnalysis->sector;

            $payload = array_merge($analysisSummary, [
                'analysis_id' => $latestAnalysis->analysis_id,
                'analysis_revision' => max(1, (int) ($latestAnalysis->revision_number ?? 1)),
                'location_name' => $locationName,
                'hourly_data' => $liveScopedRawData['hourly_data'] ?? ($rawData['hourly_data'] ?? []),
                'daily_data' => $liveScopedRawData['daily_data'] ?? ($rawData['daily_data'] ?? []),
                'peak_hour' => $liveScopedRawData['peak_hour'] ?? ($rawData['peak_hour'] ?? null),
                'avg_response_time' => $liveScopedRawData['avg_response_time'] ?? ($rawData['avg_response_time'] ?? null),
                'total_personnel' => $liveScopedRawData['total_personnel'] ?? ($rawData['total_personnel'] ?? 0),
                'available_personnel' => $liveScopedRawData['available_personnel'] ?? ($rawData['available_personnel'] ?? 0),
                'total_equipment' => $liveScopedRawData['total_equipment'] ?? ($rawData['total_equipment'] ?? 0),
                'available_equipment' => $liveScopedRawData['available_equipment'] ?? ($rawData['available_equipment'] ?? 0),
                'risk_score' => $liveScopedRawData['risk_score'] ?? ($analysisSummary['risk_score'] ?? null),
                'risk_factors' => $liveScopedRawData['risk_factors'] ?? ($rawData['risk_factors'] ?? ($analysisSummary['risk_factors'] ?? [])),
                'weather_context' => $liveScopedRawData['weather_context'] ?? ($rawData['weather_context'] ?? null),
                'mitigation_context' => $liveScopedRawData['mitigation_context'] ?? ($rawData['mitigation_context'] ?? null),
                'model_metadata' => $this->buildScopedModelMetadataContext(
                    (int) $stationOfficer->station_id,
                    $selectedBarangayId,
                    $rawData['model_metadata'] ?? ($analysisSummary['model_metadata'] ?? null)
                ),
                'workflow_context' => $rawData['workflow_context'] ?? ($analysisSummary['workflow_context'] ?? null),
                'model_prediction' => $liveModelPrediction,
                'barangay_predictions' => array_values($liveBarangayPredictions),
                'selected_barangay_prediction' => $liveSelectedPrediction,
                'prediction_context' => $livePredictionContext,
                'prediction_scope' => $requestedScope['prediction_scope'],
                'barangay_id' => $selectedBarangayId,
                'chart_context' => $liveChartContext,
                'checklist_debug' => $rawData['checklist_debug'] ?? ($analysisSummary['checklist_debug'] ?? null),
                'recommendations' => is_array($latestAnalysis->recommendations) ? $latestAnalysis->recommendations : [],
                'predictions' => is_array($latestAnalysis->predictions) ? $latestAnalysis->predictions : null,
                'risk_level' => $liveScopedRawData['risk_level'] ?? ($latestAnalysis->risk_level ?? ($analysisSummary['risk_level'] ?? null)),
                'confidence_score' => is_numeric($latestAnalysis->confidence_data)
                    ? (int) $latestAnalysis->confidence_data
                    : $latestAnalysis->confidence_data,
                'total_incidents' => $liveScopedRawData['total_incidents'] ?? ($latestAnalysis->incident_count ?? ($analysisSummary['total_incidents'] ?? 0)),
                'fire_incidents' => $liveScopedRawData['fire_incidents'] ?? ($analysisSummary['fire_incidents'] ?? 0),
                'incidents_by_type' => $liveScopedRawData['incidents_by_type'] ?? ($analysisSummary['incidents_by_type'] ?? []),
                'days_analyzed' => $liveScopedRawData['days_analyzed'] ?? $daysAnalyzed,
                'historical_window' => $historicalWindow,
                'is_dynamic_scope_fallback' => ! $this->scopeBarangayIdFromRawData($rawData) && (bool) $selectedBarangayId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $payload,
                'location_name' => $locationName,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error fetching latest analysis snapshot: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAnalysisRevisions(int $id)
    {
        try {
            $analysis = $this->resolveStationAnalysis($id);
            $current = $this->formatAnalysisRevisionEntry(
                $this->snapshotPayloadFromAnalysis($analysis),
                (int) ($analysis->revision_number ?? 1),
                true,
                optional($analysis->updated_at)?->toISOString()
            );

            $history = DB::table('analysis_revision_snapshots')
                ->where('analysis_id', $analysis->analysis_id)
                ->orderByDesc('revision_number')
                ->get()
                ->map(function ($snapshot) {
                    return $this->formatAnalysisRevisionEntry(
                        json_decode((string) $snapshot->snapshot_data, true) ?: [],
                        (int) $snapshot->revision_number,
                        false,
                        Carbon::parse($snapshot->replaced_at)->toISOString(),
                        (int) $snapshot->analysis_revision_snapshot_id
                    );
                });

            return response()->json([
                'success' => true,
                'data' => collect([$current])->concat($history)->values(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Unable to load analysis revision history: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    public function rollbackAnalysisRevision(Request $request, int $id)
    {
        $validated = $request->validate([
            'revision_number' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $analysis = $this->resolveStationAnalysis($id);
            $target = DB::table('analysis_revision_snapshots')
                ->where('analysis_id', $analysis->analysis_id)
                ->where('revision_number', $validated['revision_number'])
                ->first();

            if (! $target) {
                return response()->json(['success' => false, 'message' => 'The selected revision is unavailable.'], 404);
            }

            $targetPayload = json_decode((string) $target->snapshot_data, true) ?: [];
            $currentRevision = max(1, (int) ($analysis->revision_number ?? 1));
            $nextRevision = $currentRevision + 1;

            DB::transaction(function () use ($analysis, $targetPayload, $currentRevision, $nextRevision) {
                DB::table('analysis_revision_snapshots')->updateOrInsert(
                    ['analysis_id' => $analysis->analysis_id, 'revision_number' => $currentRevision],
                    [
                        'snapshot_data' => json_encode($this->snapshotPayloadFromAnalysis($analysis)),
                        'replaced_by' => Auth::user()?->user_id,
                        'replaced_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                DB::table('analysis')->where('analysis_id', $analysis->analysis_id)->update([
                    'sector' => $targetPayload['sector'] ?? $analysis->sector,
                    'period' => $targetPayload['period'] ?? $analysis->period,
                    'risk_level' => $targetPayload['risk_level'] ?? $analysis->risk_level,
                    'confidence_data' => $targetPayload['confidence_data'] ?? $analysis->confidence_data,
                    'analysis_data' => $this->normalizeStoredJsonValue($targetPayload['analysis_data'] ?? []),
                    'recommendations' => $this->normalizeStoredJsonValue($targetPayload['recommendations'] ?? []),
                    'predictions' => $this->normalizeStoredJsonValue($targetPayload['predictions'] ?? null),
                    'incident_count' => (int) ($targetPayload['incident_count'] ?? 0),
                    'probability' => $targetPayload['probability'] ?? null,
                    'revision_number' => $nextRevision,
                    'updated_at' => now(),
                ]);
            });

            $restored = Analysis::query()->findOrFail($analysis->analysis_id);
            $storedData = is_array($restored->analysis_data) ? $restored->analysis_data : [];
            $rawData = is_array($storedData['raw_data'] ?? null) ? $storedData['raw_data'] : [];
            $structured = is_array($storedData['analysis_summary'] ?? null) ? $storedData['analysis_summary'] : [];
            $structured['risk_level'] = $restored->risk_level;
            $structured['confidence_score'] = $restored->confidence_data;
            $structured['recommendations'] = is_array($restored->recommendations) ? $restored->recommendations : [];
            $structured['predictions'] = is_array($restored->predictions) ? $restored->predictions : null;
            $structured['analysis_id'] = $restored->analysis_id;
            $structured['analysis_revision'] = $nextRevision;

            $checklistGeneration = $this->generateAndStoreActionPlanChecklists($restored->analysis_id, $structured, $rawData);
            $structured['action_plan_checklists'] = $checklistGeneration['plans'];
            $structured['recommendations'] = $this->attachChecklistIdsToRecommendations(
                $structured['recommendations'],
                $structured['action_plan_checklists']
            );
            $suggestionGeneration = $this->generateAndStoreRecommendationSuggestions(
                $restored->analysis_id,
                $structured['recommendations'],
                $structured['action_plan_checklists']
            );
            $structured['recommendation_suggestions'] = $suggestionGeneration['suggestions'];
            $this->syncMitigationDraftActionsForAnalysis($structured['action_plan_checklists']);
            $this->updateStoredAnalysisArtifacts($restored->analysis_id, $structured, $rawData);

            return response()->json([
                'success' => true,
                'message' => "Revision {$validated['revision_number']} was restored as revision {$nextRevision}.",
                'data' => ['analysis_id' => $restored->analysis_id, 'analysis_revision' => $nextRevision],
            ]);
        } catch (\Throwable $e) {
            Log::error('Analysis revision rollback failed: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Revision rollback failed: '.$e->getMessage()], 500);
        }
    }

    private function resolveStationAnalysis(int $analysisId): Analysis
    {
        $stationId = StationOfficer::query()
            ->where('user_id', Auth::user()?->user_id)
            ->value('station_id');

        abort_unless($stationId, 403, 'No station assignment was found for this account.');

        return Analysis::query()->where('station_id', $stationId)->findOrFail($analysisId);
    }

    private function snapshotPayloadFromAnalysis(Analysis $analysis): array
    {
        return [
            'sector' => $analysis->sector,
            'period' => $analysis->period,
            'risk_level' => $analysis->risk_level,
            'confidence_data' => $analysis->confidence_data,
            'analysis_data' => $analysis->analysis_data,
            'recommendations' => $analysis->recommendations,
            'predictions' => $analysis->predictions,
            'incident_count' => $analysis->incident_count,
            'probability' => $analysis->probability,
            'station_id' => $analysis->station_id,
            'created_at' => optional($analysis->created_at)?->toISOString(),
            'updated_at' => optional($analysis->updated_at)?->toISOString(),
        ];
    }

    private function normalizeStoredJsonValue(mixed $value): string
    {
        if (is_string($value)) {
            json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $value;
            }
        }

        return json_encode($value);
    }

    private function formatAnalysisRevisionEntry(
        array $payload,
        int $revisionNumber,
        bool $isCurrent,
        ?string $recordedAt,
        ?int $snapshotId = null
    ): array {
        $analysisData = $payload['analysis_data'] ?? [];
        if (is_string($analysisData)) {
            $analysisData = json_decode($analysisData, true) ?: [];
        }
        $predictions = $payload['predictions'] ?? [];
        if (is_string($predictions)) {
            $predictions = json_decode($predictions, true) ?: [];
        }
        $rawData = is_array($analysisData['raw_data'] ?? null) ? $analysisData['raw_data'] : [];

        return [
            'snapshot_id' => $snapshotId,
            'revision_number' => $revisionNumber,
            'is_current' => $isCurrent,
            'recorded_at' => $recordedAt,
            'risk_level' => $payload['risk_level'] ?? null,
            'confidence' => $payload['confidence_data'] ?? null,
            'incident_count' => (int) ($payload['incident_count'] ?? 0),
            'prediction_scope' => $rawData['prediction_scope'] ?? 'current_month',
            'analysis_kind' => $rawData['analysis_kind'] ?? (($rawData['prediction_scope'] ?? null) === 'next_month' ? 'forecast' : 'actual_analysis'),
            'barangay_id' => $this->scopeBarangayIdFromRawData($rawData),
            'barangay_name' => data_get($rawData, 'selected_barangay_prediction.barangay_name'),
            'target_month_label' => data_get($rawData, 'prediction_context.target_month_label'),
            'predicted_fire_incidents' => data_get($predictions, 'predicted_fire_incidents')
                ?? data_get($predictions, 'predicted_incident_count')
                ?? data_get($rawData, 'model_prediction.output.predicted_incident_count'),
        ];
    }

    public function getActionPlanChecklists(Request $request)
    {
        try {
            $stationOfficer = StationOfficer::where('user_id', Auth::id())->first();
            $stationId = $stationOfficer?->station_id;

            $query = AiActionPlanChecklist::query()
                ->with(['analysis', 'barangay', 'assignee', 'confirmer', 'suggestions', 'items.reviewer'])
                ->when($stationId, fn ($builder) => $builder->where('station_id', $stationId))
                ->orderByRaw("
                    CASE priority_level
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        ELSE 4
                    END
                ")
                ->orderByDesc('created_at');

            if ($request->filled('analysis_id')) {
                $query->where('analysis_id', $request->integer('analysis_id'));
            }

            $checklists = $query->get();
            $linkedMitigationCounts = MitigationAction::query()
                ->selectRaw('ai_action_plan_checklist_id, COUNT(*) as aggregate_count, MAX(mitigation_action_id) as mitigation_action_id')
                ->whereNotNull('ai_action_plan_checklist_id')
                ->whereIn('ai_action_plan_checklist_id', $checklists->pluck('ai_action_plan_checklist_id')->all())
                ->groupBy('ai_action_plan_checklist_id')
                ->get()
                ->keyBy('ai_action_plan_checklist_id');

            return response()->json([
                'success' => true,
                'data' => $checklists->map(function (AiActionPlanChecklist $item) use ($linkedMitigationCounts) {
                    $payload = $item->toArray();
                    $linkedMitigation = $linkedMitigationCounts->get($item->ai_action_plan_checklist_id);
                    $payload['checklist_progress'] = $item->checklist_progress;
                    $payload['linked_mitigation_count'] = (int) ($linkedMitigation->aggregate_count ?? 0);
                    $payload['linked_mitigation_id'] = $linkedMitigation?->mitigation_action_id ? (int) $linkedMitigation->mitigation_action_id : null;
                    $payload['linked_mitigation_url'] = $payload['linked_mitigation_id']
                        ? route('station-admin.mitigations', [
                            'mitigation_action_id' => $payload['linked_mitigation_id'],
                        ]).'#mitigation-row-'.$payload['linked_mitigation_id']
                        : null;
                    $payload['mitigation_draft_url'] = $payload['linked_mitigation_id']
                        ? null
                        : route('station-admin.mitigations', [
                            'draft_ai_action_plan_checklist_id' => $item->ai_action_plan_checklist_id,
                            'analysis_id' => $item->analysis_id,
                        ]);
                    $payload['workflow_labels'] = [
                        'prediction' => 'Prediction',
                        'recommendation' => 'Recommendation',
                        'mitigation' => 'Mitigation Action Plan',
                    ];
                    $payload['effectiveness_hint'] = $payload['linked_mitigation_count'] > 0
                        ? $payload['linked_mitigation_count'].' mitigation action(s) already linked to this checklist.'
                        : 'No mitigation action linked yet. Convert this checklist into an operational mitigation draft next.';

                    return $payload;
                })->values(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error fetching action plan checklists: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getRecommendationSuggestions(Request $request)
    {
        try {
            $stationOfficer = StationOfficer::where('user_id', Auth::id())->first();
            $stationId = $stationOfficer?->station_id;

            $query = AiRecommendationSuggestion::query()
                ->when($stationId, fn ($builder) => $builder->where('station_id', $stationId))
                ->latest();

            if ($request->filled('analysis_id')) {
                $query->where('analysis_id', $request->integer('analysis_id'));
            }

            if ($request->filled('action_plan_checklist_id')) {
                $query->where('ai_action_plan_checklist_id', $request->integer('action_plan_checklist_id'));
            }

            return response()->json([
                'success' => true,
                'data' => $query->get(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error fetching recommendation suggestions: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function confirmActionPlanChecklist(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:confirmed,rejected,in_progress,submitted',
            'confirmation_notes' => 'nullable|string|max:2000',
        ]);

        try {
            $stationOfficer = StationOfficer::where('user_id', Auth::id())->first();
            $stationId = $stationOfficer?->station_id;

            $checklist = AiActionPlanChecklist::query()
                ->when($stationId, fn ($builder) => $builder->where('station_id', $stationId))
                ->findOrFail($id);

            $oldStatus = (string) $checklist->status;

            $checklist->update([
                'status' => $validated['status'],
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
                'confirmation_notes' => $validated['confirmation_notes'] ?? null,
            ]);
            $this->recordChecklistActivity($checklist, 'status_changed', $oldStatus, $validated['status'], $validated['confirmation_notes'] ?? null);
            UserActivityLogger::log('reviewed AI checklist', Auth::id(), $request);

            return response()->json([
                'success' => true,
                'data' => $checklist->fresh(['analysis', 'barangay', 'assignee', 'confirmer', 'suggestions', 'items.reviewer']),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error confirming action plan checklist: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateChecklistItemReview(Request $request, int $id)
    {
        $validated = $request->validate([
            'item_id' => 'required|integer|min:1',
            'status' => 'required|in:pending,followed,not_followed',
            'review_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $stationOfficer = StationOfficer::where('user_id', Auth::id())->first();
            $stationId = $stationOfficer?->station_id;

            $checklist = AiActionPlanChecklist::query()
                ->when($stationId, fn ($builder) => $builder->where('station_id', $stationId))
                ->findOrFail($id);

            $item = $checklist->items()
                ->where('ai_action_plan_checklist_item_id', $validated['item_id'])
                ->first();

            if (! $item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Checklist item not found.',
                ], 422);
            }

            $itemStatus = $validated['status'];
            $oldItemStatus = (string) $item->status;
            $item->update([
                'status' => $itemStatus,
                'reviewed_by' => $itemStatus === 'pending' ? null : Auth::id(),
                'reviewed_at' => $itemStatus === 'pending' ? null : now(),
                'review_notes' => $validated['review_notes'] ?? null,
            ]);

            $items = $checklist->items()->get();
            $followedCount = $items->where('status', 'followed')->count();
            $reviewedCount = $items->whereIn('status', ['followed', 'not_followed'])->count();
            $totalCount = max(1, $items->count());

            $status = $checklist->status;
            if ($status !== 'confirmed' && $status !== 'rejected') {
                if ($reviewedCount === 0) {
                    $status = 'pending';
                } elseif ($followedCount >= $totalCount) {
                    $status = 'submitted';
                } else {
                    $status = 'in_progress';
                }
            }

            $checklist->update([
                'status' => $status,
            ]);
            $this->recordChecklistActivity(
                $checklist,
                'step_reviewed',
                $oldItemStatus,
                $itemStatus,
                $validated['review_notes'] ?? null,
                $item->ai_action_plan_checklist_item_id,
                ['item_order' => $item->item_order, 'item_text' => $item->item_text]
            );
            UserActivityLogger::log('reviewed AI checklist step', Auth::id(), $request);

            return response()->json([
                'success' => true,
                'data' => $checklist->fresh(['analysis', 'barangay', 'assignee', 'confirmer', 'suggestions', 'items.reviewer']),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error updating checklist item review: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getChecklistHistory(Request $request, int $id)
    {
        $checklist = $this->scopedChecklistQuery()->with(['items.reviewer', 'confirmer', 'assignee'])->findOrFail($id);
        $perPage = max(5, min(20, $request->integer('per_page', 6)));

        if (Schema::hasTable('ai_action_plan_checklist_activities')) {
            $history = AiActionPlanChecklistActivity::query()
                ->with('user')
                ->where('ai_action_plan_checklist_id', $checklist->ai_action_plan_checklist_id)
                ->latest('created_at')
                ->paginate($perPage);

            if ($history->total() > 0) {
                return response()->json([
                    'success' => true,
                    'data' => collect($history->items())->map(fn ($event) => $this->formatChecklistActivity($event))->values(),
                    'pagination' => [
                        'current_page' => $history->currentPage(),
                        'last_page' => max(1, $history->lastPage()),
                        'total' => $history->total(),
                    ],
                ]);
            }
        }

        $fallback = collect([[
            'id' => 'created-'.$checklist->ai_action_plan_checklist_id,
            'event_type' => 'created',
            'label' => 'Checklist created',
            'actor' => 'AI forecast workflow',
            'old_value' => null,
            'new_value' => $checklist->status,
            'notes' => $checklist->source_title,
            'created_at' => optional($checklist->created_at)?->toIso8601String(),
        ]])->concat($checklist->items->filter->reviewed_at->map(fn ($item) => [
            'id' => 'item-'.$item->ai_action_plan_checklist_item_id,
            'event_type' => 'step_reviewed',
            'label' => 'Step '.$item->item_order.' marked '.str_replace('_', ' ', $item->status),
            'actor' => trim(($item->reviewer?->first_name ?? '').' '.($item->reviewer?->last_name ?? '')) ?: 'Station reviewer',
            'old_value' => null,
            'new_value' => $item->status,
            'notes' => $item->review_notes ?: $item->item_text,
            'created_at' => optional($item->reviewed_at)?->toIso8601String(),
        ]))->when($checklist->confirmed_at, fn ($events) => $events->push([
            'id' => 'review-'.$checklist->ai_action_plan_checklist_id,
            'event_type' => 'status_changed',
            'label' => 'Checklist marked '.str_replace('_', ' ', $checklist->status),
            'actor' => trim(($checklist->confirmer?->first_name ?? '').' '.($checklist->confirmer?->last_name ?? '')) ?: 'Station reviewer',
            'old_value' => null,
            'new_value' => $checklist->status,
            'notes' => $checklist->confirmation_notes,
            'created_at' => optional($checklist->confirmed_at)?->toIso8601String(),
        ]))->sortByDesc('created_at')->values();

        $page = max(1, $request->integer('page', 1));

        return response()->json([
            'success' => true,
            'data' => $fallback->forPage($page, $perPage)->values(),
            'pagination' => ['current_page' => $page, 'last_page' => max(1, (int) ceil($fallback->count() / $perPage)), 'total' => $fallback->count()],
        ]);
    }

    public function bulkUpdateActionPlanChecklists(Request $request)
    {
        $validated = $request->validate([
            'checklist_ids' => 'required|array|min:1|max:100',
            'checklist_ids.*' => 'required|integer|min:1',
            'assigned_to' => 'nullable|integer|exists:users,user_id',
            'status' => 'nullable|in:pending,in_progress,submitted,confirmed,rejected',
        ]);
        abort_if(! $request->exists('assigned_to') && ! $request->filled('status'), 422, 'Choose an assignee or status to update.');

        $checklists = $this->scopedChecklistQuery()
            ->whereIn('ai_action_plan_checklist_id', array_unique($validated['checklist_ids']))
            ->get();
        abort_if($checklists->count() !== count(array_unique($validated['checklist_ids'])), 403, 'One or more checklists are outside your station scope.');

        $stationOfficer = StationOfficer::where('user_id', Auth::id())->first();
        if (! empty($validated['assigned_to'])) {
            $assigneeQuery = StationOfficer::where('user_id', $validated['assigned_to'])
                ->when($stationOfficer?->station_id, fn ($query) => $query->where('station_id', $stationOfficer->station_id));
            abort_unless($assigneeQuery->exists(), 422, 'The assignee must be an eligible station officer.');
        }

        DB::transaction(function () use ($checklists, $validated) {
            foreach ($checklists as $checklist) {
                if (array_key_exists('assigned_to', $validated)) {
                    $oldAssignee = $checklist->assigned_to;
                    $checklist->assigned_to = $validated['assigned_to'];
                    $this->recordChecklistActivity($checklist, 'assignment_changed', (string) ($oldAssignee ?? ''), (string) ($validated['assigned_to'] ?? ''));
                }
                if (! empty($validated['status'])) {
                    $oldStatus = (string) $checklist->status;
                    $checklist->status = $validated['status'];
                    if (in_array($validated['status'], ['confirmed', 'rejected'], true)) {
                        $checklist->confirmed_by = Auth::id();
                        $checklist->confirmed_at = now();
                    }
                    $this->recordChecklistActivity($checklist, 'status_changed', $oldStatus, $validated['status'], 'Bulk checklist update');
                }
                $checklist->save();
            }
        });

        UserActivityLogger::log('bulk updated AI checklists', Auth::id(), $request);

        return response()->json(['success' => true, 'updated' => $checklists->count()]);
    }

    private function scopedChecklistQuery()
    {
        $stationId = StationOfficer::where('user_id', Auth::id())->value('station_id');
        $isProvincialAdmin = strtolower(trim((string) (Auth::user()?->role?->role ?? ''))) === 'provincial admin';

        return AiActionPlanChecklist::query()
            ->when($stationId, fn ($query) => $query->where('station_id', $stationId))
            ->when(! $stationId && ! $isProvincialAdmin, fn ($query) => $query->whereRaw('1 = 0'));
    }

    private function recordChecklistActivity(AiActionPlanChecklist $checklist, string $eventType, ?string $oldValue, ?string $newValue, ?string $notes = null, ?int $itemId = null, array $metadata = []): void
    {
        if (! Schema::hasTable('ai_action_plan_checklist_activities')) {
            return;
        }

        if (! AiActionPlanChecklistActivity::where('ai_action_plan_checklist_id', $checklist->ai_action_plan_checklist_id)->exists()) {
            AiActionPlanChecklistActivity::create([
                'ai_action_plan_checklist_id' => $checklist->ai_action_plan_checklist_id,
                'event_type' => 'created',
                'new_value' => 'pending',
                'notes' => $checklist->source_title,
                'created_at' => $checklist->created_at ?? now(),
            ]);

            $checklist->items()->whereNotNull('reviewed_at')
                ->when($itemId, fn ($query) => $query->where('ai_action_plan_checklist_item_id', '!=', $itemId))
                ->get()
                ->each(fn ($item) => AiActionPlanChecklistActivity::create([
                    'ai_action_plan_checklist_id' => $checklist->ai_action_plan_checklist_id,
                    'ai_action_plan_checklist_item_id' => $item->ai_action_plan_checklist_item_id,
                    'user_id' => $item->reviewed_by,
                    'event_type' => 'step_reviewed',
                    'new_value' => $item->status,
                    'notes' => $item->review_notes,
                    'metadata' => ['item_order' => $item->item_order, 'item_text' => $item->item_text],
                    'created_at' => $item->reviewed_at,
                ]));

            if ($eventType !== 'status_changed' && $checklist->confirmed_at) {
                AiActionPlanChecklistActivity::create([
                    'ai_action_plan_checklist_id' => $checklist->ai_action_plan_checklist_id,
                    'user_id' => $checklist->confirmed_by,
                    'event_type' => 'status_changed',
                    'new_value' => $checklist->status,
                    'notes' => $checklist->confirmation_notes,
                    'created_at' => $checklist->confirmed_at,
                ]);
            }
        }

        AiActionPlanChecklistActivity::create([
            'ai_action_plan_checklist_id' => $checklist->ai_action_plan_checklist_id,
            'ai_action_plan_checklist_item_id' => $itemId,
            'user_id' => Auth::id(),
            'event_type' => $eventType,
            'old_value' => $oldValue ?: null,
            'new_value' => $newValue ?: null,
            'notes' => $notes,
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }

    private function formatChecklistActivity(AiActionPlanChecklistActivity $event): array
    {
        $labels = [
            'created' => 'Checklist created',
            'status_changed' => 'Checklist status changed',
            'step_reviewed' => 'Checklist step reviewed',
            'assignment_changed' => 'Checklist assignment changed',
        ];
        if ($event->event_type === 'step_reviewed' && data_get($event->metadata, 'item_order')) {
            $labels['step_reviewed'] = 'Step '.data_get($event->metadata, 'item_order').' reviewed';
        }

        return [
            'id' => $event->ai_action_plan_checklist_activity_id,
            'event_type' => $event->event_type,
            'label' => $labels[$event->event_type] ?? ucfirst(str_replace('_', ' ', $event->event_type)),
            'actor' => trim(($event->user?->first_name ?? '').' '.($event->user?->last_name ?? '')) ?: 'System',
            'old_value' => $event->old_value,
            'new_value' => $event->new_value,
            'notes' => $event->notes,
            'created_at' => optional($event->created_at)?->toIso8601String(),
        ];
    }

    /**
     * Get a specific analysis by ID
     */
    public function getAnalysis($id)
    {
        try {
            $analysis = DB::table('analysis')
                ->where('analysis_id', $id)
                ->first();

            if (! $analysis) {
                return response()->json([
                    'success' => false,
                    'message' => 'Analysis not found',
                ], 404);
            }

            // Decode JSON fields
            $analysis->analysis_data = json_decode($analysis->analysis_data, true);
            $analysis->recommendations = json_decode($analysis->recommendations, true);
            $analysis->predictions = json_decode($analysis->predictions, true);
            $analysis->confidence_score = is_numeric($analysis->confidence_data)
                ? (int) $analysis->confidence_data
                : $analysis->confidence_data;

            return response()->json([
                'success' => true,
                'data' => $analysis,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching analysis: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Explore a recommendation in detail using AI
     */
    // public function exploreRecommendation(Request $request)
    // {
    //     try {
    //         $recommendationTitle = $request->input('recommendation_title');
    //         $recommendationDescription = $request->input('recommendation_description');
    //         $priority = $request->input('priority', 'Medium');

    //         Log::info('Explore recommendation called', [
    //             'title' => $recommendationTitle,
    //             'priority' => $priority
    //         ]);

    //         $prompt = "You are a fire safety implementation expert. Provide a detailed implementation plan for this recommendation:\n\n";
    //         $prompt .= "RECOMMENDATION: {$recommendationTitle}\n";
    //         $prompt .= "DESCRIPTION: {$recommendationDescription}\n";
    //         $prompt .= "PRIORITY: {$priority}\n\n";
    //         $prompt .= "Please provide:\n";
    //         $prompt .= "1. 5-7 specific actionable steps to implement this recommendation (numbered list)\n";
    //         $prompt .= "2. Resources needed (as a bullet list)\n";
    //         $prompt .= "3. Estimated timeline for implementation\n\n";

    //         try {
    //             $response = Http::withHeaders([
    //                 'Authorization' => 'Bearer ' . $this->apiKey,
    //                 'Content-Type' => 'application/json',
    //             ])->timeout(60)->post('https://openrouter.ai/api/v1/chat/completions', [
    //                 'model' => 'openai/gpt-3.5-turbo',
    //                 'messages' => [
    //                     ['role' => 'system', 'content' => 'You are a fire safety implementation expert. Provide detailed, actionable implementation plans.'],
    //                     ['role' => 'user', 'content' => $prompt]
    //                 ],
    //                 'temperature' => 0.5,
    //                 'max_tokens' => 1000
    //             ]);

    //             if ($response->successful()) {
    //                 $json = $response->json();
    //                 $content = $json['choices'][0]['message']['content'] ?? '';

    //                 // Parse the response
    //                 $parsedData = $this->parseExploreResponse($content);

    //                 return response()->json([
    //                     'success' => true,
    //                     'data' => $parsedData
    //                 ]);
    //             } else {
    //                 throw new \Exception('AI API call failed: ' . $response->status());
    //             }

    //         } catch (\Exception $aiError) {
    //             Log::warning('AI API failed for explore, using fallback: ' . $aiError->getMessage());

    //             // Return fallback data
    //             return response()->json([
    //                 'success' => true,
    //                 'data' => $this->getFallbackExploreData($recommendationTitle, $priority)
    //             ]);
    //         }

    //     } catch (\Exception $e) {
    //         Log::error('Explore recommendation error: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => true,
    //             'data' => $this->getFallbackExploreData($request->input('recommendation_title', 'this recommendation'), $request->input('priority', 'Medium'))
    //         ]);
    //     }
    // }
    /**
     * Explore a recommendation in detail using AI
     */
    public function exploreRecommendation(Request $request)
    {
        $recommendationTitle = $this->normalizeRecommendationTitle($request->input('recommendation_title'));
        $recommendationDescription = $request->input('recommendation_description');
        $priority = $request->input('priority', 'Medium') ?: 'Medium';
        $analysisId = $request->input('analysis_id');
        $actionPlanChecklistId = $request->input('action_plan_checklist_id');

        try {
            Log::info('=== EXPLORE RECOMMENDATION START ===');
            Log::info('Title: '.$recommendationTitle);

            if (! $this->apiKey) {
                Log::error('No API key available for explore');
                throw new \Exception('API key not configured');
            }

            $analysis = $analysisId ? Analysis::query()->find($analysisId) : null;
            $actionPlanChecklist = $actionPlanChecklistId
                ? AiActionPlanChecklist::query()->find($actionPlanChecklistId)
                : null;

            $existingSuggestion = $this->findExistingRecommendationSuggestion(
                $analysis,
                $actionPlanChecklist,
                $recommendationTitle,
                $recommendationDescription,
                $priority
            );

            if ($existingSuggestion) {
                return response()->json([
                    'success' => true,
                    'data' => $this->formatStoredRecommendationSuggestion($existingSuggestion),
                    'source' => 'saved_suggestion',
                    'model_used' => null,
                    'saved_suggestion_id' => $existingSuggestion->ai_recommendation_suggestion_id,
                    'note' => 'Showing the previously generated implementation plan for this recommendation.',
                ]);
            }

            $contextPayload = $this->buildExploreRecommendationContext($analysis, $actionPlanChecklist, [
                'recommendation_title' => $recommendationTitle,
                'recommendation_description' => $recommendationDescription,
                'priority' => $priority,
            ]);

            $contextualFallback = $this->getFallbackExploreData(
                $recommendationTitle,
                $priority,
                $recommendationDescription,
                $contextPayload
            );

            $prompt = "You are a fire safety implementation expert. Provide a detailed implementation plan for this recommendation.\n\n";
            $prompt .= "RECOMMENDATION: {$recommendationTitle}\n";
            $prompt .= "DESCRIPTION: {$recommendationDescription}\n";
            $prompt .= "PRIORITY: {$priority}\n\n";
            $prompt .= "GROUNDING CONTEXT:\n";
            $prompt .= json_encode($contextPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n\n";
            $prompt .= "Answer these three planning questions clearly: How will it be done, who will do it, and when will it be done.\n\n";
            $prompt .= "Provide EXACTLY this format:\n\n";
            $prompt .= "HOW: [Short execution approach tied to the actual area and condition]\n";
            $prompt .= "WHO: [Specific responsible team or person]\n";
            $prompt .= "WHEN: [Specific schedule or timing]\n\n";
            $prompt .= "STEPS:\n";
            $prompt .= "1. [First actionable step]\n";
            $prompt .= "2. [Second actionable step]\n";
            $prompt .= "3. [Third actionable step]\n";
            $prompt .= "4. [Fourth actionable step]\n";
            $prompt .= "5. [Fifth actionable step]\n\n";
            $prompt .= "RESOURCES NEEDED:\n";
            $prompt .= "- [Resource 1]\n";
            $prompt .= "- [Resource 2]\n";
            $prompt .= "- [Resource 3]\n\n";
            $prompt .= 'TIMELINE: [Estimated timeline]';

            Log::info('Calling AI API for explore, prompt length: '.strlen($prompt));
            $content = $this->callAIApi(
                $prompt,
                'You are a fire safety implementation expert. Provide detailed, actionable implementation plans.'
            );
            $exploreAiModel = $this->lastAiModelUsed;
            Log::info('AI Response received, length: '.strlen($content));

            $parsedData = $this->parseExploreResponse($content, $contextualFallback);
            $savedSuggestion = $this->storeRecommendationSuggestion(
                $analysis,
                $actionPlanChecklist,
                $recommendationTitle,
                $recommendationDescription,
                $priority,
                $contextPayload,
                $parsedData,
                $content
            );

            return response()->json([
                'success' => true,
                'data' => $parsedData,
                'source' => 'ai_api',
                'model_used' => $exploreAiModel,
                'saved_suggestion_id' => $savedSuggestion?->ai_recommendation_suggestion_id,
            ]);

        } catch (\Exception $e) {
            Log::error('AI API failed, using fallback: '.$e->getMessage());

            $analysis = $analysisId ? Analysis::query()->find($analysisId) : null;
            $actionPlanChecklist = $actionPlanChecklistId
                ? AiActionPlanChecklist::query()->find($actionPlanChecklistId)
                : null;
            $contextPayload = $this->buildExploreRecommendationContext($analysis, $actionPlanChecklist, [
                'recommendation_title' => $recommendationTitle,
                'recommendation_description' => $recommendationDescription,
                'priority' => $priority,
            ]);
            $fallbackData = $this->getFallbackExploreData(
                $recommendationTitle,
                $priority,
                $recommendationDescription,
                $contextPayload
            );
            $savedSuggestion = $this->storeRecommendationSuggestion(
                $analysis,
                $actionPlanChecklist,
                $recommendationTitle,
                $recommendationDescription,
                $priority,
                $contextPayload,
                $fallbackData,
                'fallback_response'
            );

            return response()->json([
                'success' => true,
                'data' => $fallbackData,
                'source' => 'fallback',
                'model_used' => null,
                'note' => 'AI service temporarily unavailable, using standard recommendations',
                'saved_suggestion_id' => $savedSuggestion?->ai_recommendation_suggestion_id,
            ]);
        }
    }

    private function normalizeRecommendationTitle($title): string
    {
        $normalizedTitle = trim((string) $title);

        return $normalizedTitle !== '' ? $normalizedTitle : 'Priority fire-risk recommendation';
    }

    private function buildExploreRecommendationContext(?Analysis $analysis, ?AiActionPlanChecklist $actionPlanChecklist, array $recommendation): array
    {
        $analysisData = $analysis?->analysis_data ?? [];
        $analysisSummary = $analysisData['analysis_summary'] ?? [];
        $predictions = $analysis?->predictions ?? [];

        return [
            'analysis_id' => $analysis?->analysis_id,
            'station_id' => $analysis?->station_id,
            'risk_level' => $analysis?->risk_level,
            'confidence_score' => $analysis?->confidence_data,
            'recommendation' => $recommendation,
            'prediction_summary' => $predictions['summary'] ?? null,
            'prediction_drivers' => $predictions['drivers'] ?? [],
            'weather_context' => $analysisSummary['weather_context'] ?? ($predictions['weather_context'] ?? null),
            'mitigation_context' => $analysisSummary['mitigation_context'] ?? ($predictions['mitigation_context'] ?? null),
            'risk_factors' => $analysisSummary['risk_factors'] ?? [],
            'available_personnel' => $analysisSummary['available_personnel'] ?? null,
            'available_equipment' => $analysisSummary['available_equipment'] ?? null,
            'action_plan_checklist' => $actionPlanChecklist?->only([
                'ai_action_plan_checklist_id',
                'plan_title',
                'plan_description',
                'priority_level',
                'status',
                'success_indicator',
                'checklist_items',
            ]),
        ];
    }

    private function storeRecommendationSuggestion(
        ?Analysis $analysis,
        ?AiActionPlanChecklist $actionPlanChecklist,
        string $recommendationTitle,
        ?string $recommendationDescription,
        string $priority,
        array $contextPayload,
        array $parsedData,
        string $aiResponse,
        bool $isAiGenerated = true
    ): ?AiRecommendationSuggestion {
        try {
            return AiRecommendationSuggestion::create([
                'analysis_id' => $analysis?->analysis_id,
                'ai_action_plan_checklist_id' => $actionPlanChecklist?->ai_action_plan_checklist_id,
                'station_id' => $analysis?->station_id ?? $actionPlanChecklist?->station_id,
                'barangay_id' => $actionPlanChecklist?->barangay_id,
                'recommendation_title' => $recommendationTitle,
                'recommendation_description' => $recommendationDescription,
                'priority_level' => strtolower($priority),
                'request_context' => $contextPayload,
                'suggestions' => $parsedData['suggestions'] ?? [],
                'steps' => $parsedData['steps'] ?? [],
                'resources_needed' => $parsedData['resources_needed'] ?? [],
                'estimated_timeline' => $parsedData['estimated_timeline'] ?? null,
                'ai_response' => $aiResponse,
                'is_ai_generated' => $isAiGenerated,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to store AI recommendation suggestion: '.$e->getMessage());

            return null;
        }
    }

    private function findExistingRecommendationSuggestion(
        ?Analysis $analysis,
        ?AiActionPlanChecklist $actionPlanChecklist,
        string $recommendationTitle,
        ?string $recommendationDescription,
        string $priority
    ): ?AiRecommendationSuggestion {
        return AiRecommendationSuggestion::query()
            ->when($analysis?->analysis_id, fn ($query) => $query->where('analysis_id', $analysis->analysis_id))
            ->when(
                $actionPlanChecklist?->ai_action_plan_checklist_id,
                fn ($query) => $query->where('ai_action_plan_checklist_id', $actionPlanChecklist->ai_action_plan_checklist_id),
                fn ($query) => $query->whereNull('ai_action_plan_checklist_id')
            )
            ->where('recommendation_title', $recommendationTitle)
            ->where('recommendation_description', $recommendationDescription)
            ->where('priority_level', strtolower($priority))
            ->latest('created_at')
            ->first();
    }

    private function formatStoredRecommendationSuggestion(AiRecommendationSuggestion $suggestion): array
    {
        $context = is_array($suggestion->request_context) ? $suggestion->request_context : [];
        $rawResponse = trim((string) ($suggestion->ai_response ?? ''));
        $cleanResponse = preg_replace('/^```[a-z]*\s*/i', '', $rawResponse) ?? $rawResponse;
        $cleanResponse = preg_replace('/\s*```$/', '', $cleanResponse) ?? $cleanResponse;

        return [
            'how' => $context['action_plan_checklist']['plan_description'] ?? null,
            'who' => data_get($context, 'action_plan_checklist.assigned_to'),
            'when' => null,
            'suggestions' => is_array($suggestion->suggestions) ? $suggestion->suggestions : [],
            'steps' => is_array($suggestion->steps) ? $suggestion->steps : [],
            'resources_needed' => is_array($suggestion->resources_needed) ? $suggestion->resources_needed : [],
            'estimated_timeline' => $suggestion->estimated_timeline,
            'explanation' => trim($cleanResponse),
        ];
    }

    /**
     * Build a weather summary from the station's city or marker for the current month.
     */
    private function fetchMonthlyWeatherContext($station, $days, $fallbackLocationName)
    {
        $apiKey = env('WEATHER_API_KEY', '1432615e15a14140963113518261203');

        if (! $apiKey || ! $station) {
            return null;
        }

        try {
            $locationQuery = $this->resolveWeatherLocationQuery($station, $fallbackLocationName);
            $forecastDays = max(1, min((int) $days, 14));

            $response = Http::timeout(20)->get('http://api.weatherapi.com/v1/forecast.json', [
                'key' => $apiKey,
                'q' => $locationQuery,
                'days' => $forecastDays,
                'aqi' => 'no',
                'alerts' => 'no',
            ]);

            if (! $response->successful()) {
                Log::warning('Weather forecast request failed for analytics', [
                    'status' => $response->status(),
                    'location' => $locationQuery,
                ]);

                return null;
            }

            $payload = $response->json();
            $forecastDaysData = $payload['forecast']['forecastday'] ?? [];
            $current = $payload['current'] ?? [];

            $dayCount = count($forecastDaysData);
            $avgTemp = $dayCount > 0 ? round(collect($forecastDaysData)->avg(fn ($day) => $day['day']['avgtemp_c'] ?? null), 1) : ($current['temp_c'] ?? null);
            $avgHumidity = $dayCount > 0 ? round(collect($forecastDaysData)->avg(fn ($day) => $day['day']['avghumidity'] ?? null)) : ($current['humidity'] ?? null);
            $avgWind = $dayCount > 0 ? round(collect($forecastDaysData)->avg(fn ($day) => $day['day']['maxwind_kph'] ?? null), 1) : ($current['wind_kph'] ?? null);
            $rainyDays = collect($forecastDaysData)->filter(fn ($day) => ($day['day']['daily_chance_of_rain'] ?? 0) >= 50 || ($day['day']['totalprecip_mm'] ?? 0) > 0)->count();
            $hotDays = collect($forecastDaysData)->filter(fn ($day) => ($day['day']['maxtemp_c'] ?? 0) >= 32)->count();
            $dominantCondition = collect($forecastDaysData)
                ->map(fn ($day) => $day['day']['condition']['text'] ?? null)
                ->filter()
                ->countBy()
                ->sortDesc()
                ->keys()
                ->first() ?? ($current['condition']['text'] ?? 'Unknown');

            $weatherRiskScore = 0;
            if (($avgTemp ?? 0) >= 31) {
                $weatherRiskScore += 2;
            }
            if (($avgHumidity ?? 100) <= 55) {
                $weatherRiskScore += 2;
            }
            if (($avgWind ?? 0) >= 20) {
                $weatherRiskScore += 1;
            }
            if ($rainyDays === 0 && $dayCount > 0) {
                $weatherRiskScore += 1;
            }

            $weatherRiskLabel = 'Low';
            if ($weatherRiskScore >= 5) {
                $weatherRiskLabel = 'High';
            } elseif ($weatherRiskScore >= 3) {
                $weatherRiskLabel = 'Moderate';
            }

            return [
                'query_location' => $locationQuery,
                'coverage' => 'Current month context using available '.max($dayCount, 1).'-day weather sample',
                'avg_temp_c' => $avgTemp,
                'avg_humidity' => $avgHumidity,
                'avg_wind_kph' => $avgWind,
                'rainy_days' => $rainyDays,
                'hot_days' => $hotDays,
                'dominant_condition' => $dominantCondition,
                'weather_risk_score' => $weatherRiskScore,
                'weather_risk_label' => $weatherRiskLabel,
                'summary' => $this->buildWeatherSummary($avgTemp, $avgHumidity, $avgWind, $rainyDays, $hotDays, $dominantCondition, $weatherRiskLabel),
            ];
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch weather context for analytics: '.$e->getMessage());

            return null;
        }
    }

    private function resolveWeatherLocationQuery($station, $fallbackLocationName)
    {
        $cityName = null;
        if (! empty($station->address_id)) {
            $address = DB::table('full_addresses')
                ->leftJoin('barangays', 'full_addresses.barangay_id', '=', 'barangays.barangay_id')
                ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
                ->where('full_addresses.address_id', $station->address_id)
                ->select('cities.city_name')
                ->first();

            $cityName = $address->city_name ?? null;
        }

        if ($cityName) {
            return $cityName.', Pangasinan, Philippines';
        }

        if (! empty($station->marker_id)) {
            $markerTitle = DB::table('markers')->where('marker_id', $station->marker_id)->value('title');
            if ($markerTitle) {
                return $markerTitle.', Philippines';
            }
        }

        return $fallbackLocationName.', Philippines';
    }

    private function buildWeatherSummary($avgTemp, $avgHumidity, $avgWind, $rainyDays, $hotDays, $dominantCondition, $weatherRiskLabel)
    {
        $parts = [];

        if ($avgTemp !== null) {
            $parts[] = "average temperature around {$avgTemp}°C";
        }
        if ($avgHumidity !== null) {
            $parts[] = "humidity near {$avgHumidity}%";
        }
        if ($avgWind !== null) {
            $parts[] = "wind averaging {$avgWind} kph";
        }

        $parts[] = "{$rainyDays} rainy day(s)";
        $parts[] = "{$hotDays} hot day(s)";
        $parts[] = "dominant condition: {$dominantCondition}";

        return ucfirst(implode(', ', $parts)).". Weather fire-risk outlook is {$weatherRiskLabel}.";
    }

    private function extractPredictionData($aiContent, $data)
    {
        $fallback = $this->generateDataDrivenPrediction($data);
        $prediction = $fallback;
        $lines = preg_split("/\r\n|\n|\r/", $aiContent);
        $currentSection = null;
        $drivers = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^PREDICTION\s*:/i', $trimmed)) {
                $currentSection = 'prediction';
                $value = trim(substr($trimmed, strpos($trimmed, ':') + 1));
                if ($value !== '') {
                    $prediction['summary'] = $value;
                }

                continue;
            }

            if (preg_match('/^FORECAST WINDOW\s*:/i', $trimmed)) {
                $currentSection = 'forecast_window';
                $value = trim(substr($trimmed, strpos($trimmed, ':') + 1));
                if ($value !== '') {
                    $prediction['forecast_window'] = $value;
                }

                continue;
            }

            if (preg_match('/^PREDICTED FIRE INCIDENTS\s*:/i', $trimmed)) {
                $currentSection = 'predicted_fire_incidents';
                $value = trim(substr($trimmed, strpos($trimmed, ':') + 1));
                if ($value !== '' && preg_match('/\d+/', $value, $matches)) {
                    $prediction['predicted_fire_incidents'] = (int) $matches[0];
                }

                continue;
            }

            if (preg_match('/^LIKELY PEAK HOURS\s*:/i', $trimmed)) {
                $currentSection = 'peak_hours';
                $value = trim(substr($trimmed, strpos($trimmed, ':') + 1));
                if ($value !== '') {
                    $prediction['likely_peak_hours'] = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', $value))));
                }

                continue;
            }

            if (preg_match('/^RISK OUTLOOK\s*:/i', $trimmed)) {
                $currentSection = 'outlook';
                $value = trim(substr($trimmed, strpos($trimmed, ':') + 1));
                if ($value !== '') {
                    $prediction['outlook'] = $value;
                }

                continue;
            }

            if (preg_match('/^DRIVERS\s*:/i', $trimmed)) {
                $currentSection = 'drivers';
                $value = trim(substr($trimmed, strpos($trimmed, ':') + 1));
                if ($value !== '') {
                    $drivers[] = $value;
                }

                continue;
            }

            if (preg_match('/^RECOMMENDATIONS\s*:/i', $trimmed) || preg_match('/^ANALYSIS SUMMARY\s*:/i', $trimmed)) {
                $currentSection = null;

                continue;
            }

            if ($currentSection === 'prediction' && empty($prediction['summary'])) {
                $prediction['summary'] = $trimmed;
            } elseif ($currentSection === 'forecast_window' && empty($prediction['forecast_window'])) {
                $prediction['forecast_window'] = $trimmed;
            } elseif ($currentSection === 'predicted_fire_incidents' && empty($prediction['predicted_fire_incidents']) && preg_match('/\d+/', $trimmed, $matches)) {
                $prediction['predicted_fire_incidents'] = (int) $matches[0];
            } elseif ($currentSection === 'peak_hours') {
                $prediction['likely_peak_hours'] = array_values(array_unique(array_filter(array_map('trim', preg_split('/[,;]+/', ltrim($trimmed, "-•* \t"))))));
            } elseif ($currentSection === 'outlook' && empty($prediction['outlook'])) {
                $prediction['outlook'] = $trimmed;
            } elseif ($currentSection === 'drivers') {
                $drivers[] = ltrim($trimmed, "-•* \t");
            }
        }

        if (! empty($drivers)) {
            $prediction['drivers'] = array_values(array_unique(array_filter($drivers)));
        }

        $prediction['weather_context'] = $data['weather_context'] ?? null;
        $prediction['prediction_scope'] = $data['prediction_scope'] ?? 'current_month';
        $prediction['prediction_context'] = $data['prediction_context'] ?? null;

        return $prediction;
    }

    private function generateDataDrivenPrediction($data)
    {
        $weather = $data['weather_context'] ?? [];
        $predictionContext = $data['prediction_context'] ?? [];
        $modelPrediction = $data['model_prediction']['output'] ?? null;
        $drivers = [];

        if (($predictionContext['current_month_fire_incidents'] ?? 0) >= 1) {
            $drivers[] = "{$predictionContext['current_month_fire_incidents']} fire incidents were logged this month";
        }

        if (($data['total_personnel'] ?? 0) > 0) {
            $availabilityRatio = ($data['available_personnel'] ?? 0) / max(1, $data['total_personnel']);
            if ($availabilityRatio < 0.6) {
                $drivers[] = "station manpower is constrained with only {$data['available_personnel']} of {$data['total_personnel']} officers available";
            } else {
                $drivers[] = "officer availability remains stable at {$data['available_personnel']} of {$data['total_personnel']}";
            }
        }

        if (! empty($weather['summary'])) {
            $drivers[] = $weather['summary'];
        }

        if (! empty($predictionContext['likely_peak_hours'])) {
            $drivers[] = 'observed monthly peak hours: '.implode(', ', $predictionContext['likely_peak_hours']);
        }

        if (! empty($data['mitigation_context']['summary'])) {
            $drivers[] = $data['mitigation_context']['summary'];
        }

        $outlook = $data['risk_level'] ?? 'Medium';
        if (! empty($modelPrediction['risk_level'])) {
            $outlook = ucfirst($modelPrediction['risk_level']);
        }
        if (($weather['weather_risk_label'] ?? null) === 'High' && ($data['risk_score'] ?? 0) >= 50) {
            $outlook = 'High';
        }

        $baseFireCount = $predictionContext['current_month_fire_incidents'] ?? 0;
        $previousFireCount = $predictionContext['previous_month_fire_incidents'] ?? $baseFireCount;
        $predictedFireCount = (int) round((($baseFireCount + $previousFireCount) / max(1, ($previousFireCount > 0 ? 2 : 1))) +
            (($weather['weather_risk_label'] ?? null) === 'High' ? 2 : (($weather['weather_risk_label'] ?? null) === 'Moderate' ? 1 : 0)) +
            ((($data['available_personnel'] ?? 0) / max(1, ($data['total_personnel'] ?? 1))) < 0.6 ? 1 : 0));
        $predictedFireCount = max($baseFireCount, $predictedFireCount);
        $predictedDamage = null;

        $likelyPeakHours = $predictionContext['likely_peak_hours'] ?? [];
        if (empty($likelyPeakHours)
            && ! isset($predictionContext['peak_hour_evidence'])
            && isset($data['peak_hour'])
            && $data['peak_hour'] !== null) {
            $likelyPeakHours = [str_pad((string) $data['peak_hour'], 2, '0', STR_PAD_LEFT).':00'];
        }

        $forecastWindow = $predictionContext['target_period_label'] ?? 'Current month';
        $summary = "Projected {$outlook} fire risk for {$forecastWindow}, with around {$predictedFireCount} possible fire incident(s) if current incident, staffing, and weather conditions persist.";

        if ($modelPrediction) {
            $predictedProbability = $modelPrediction['probability_percent'] ?? null;
            $predictedFireCount = (int) round($modelPrediction['predicted_incident_count'] ?? $predictedFireCount);
            $predictedDamage = isset($modelPrediction['predicted_estimated_damage'])
                ? (float) $modelPrediction['predicted_estimated_damage']
                : null;
            if ($predictedProbability !== null) {
                $summary .= " The Random Forest model currently estimates a {$predictedProbability}% probability for {$modelPrediction['target']}.";
            }

            foreach (array_slice($modelPrediction['drivers'] ?? [], 0, 3) as $driver) {
                if (! empty($driver['summary'])) {
                    $drivers[] = $driver['summary'];
                }
            }
        }

        if (! empty($data['mitigation_context']['risk_reduction_percent'])) {
            $summary .= " Confirmed mitigation actions are currently reducing the projected risk context by about {$data['mitigation_context']['risk_reduction_percent']}%.";
        }

        if (! is_null($predictedDamage)) {
            $summary .= ' Estimated possible damage exposure for the forecast window is around PHP '.number_format($predictedDamage, 2).'.';
        }

        return [
            'summary' => $summary,
            'forecast_window' => $forecastWindow,
            'outlook' => $outlook,
            'predicted_fire_incidents' => $predictedFireCount,
            'predicted_damage' => $predictedDamage,
            'likely_peak_hours' => $likelyPeakHours,
            'likely_peak_hours_label' => $predictionContext['peak_hour_evidence']['label']
                ?? (empty($likelyPeakHours) ? 'No clear peak yet' : implode(', ', $likelyPeakHours)),
            'peak_hour_evidence' => $predictionContext['peak_hour_evidence'] ?? null,
            'drivers' => array_values(array_unique(array_filter($drivers))),
            'weather_context' => $weather ?: null,
            'model_prediction' => $modelPrediction,
            'mitigation_context' => $data['mitigation_context'] ?? null,
            'prediction_scope' => $data['prediction_scope'] ?? 'current_month',
            'prediction_context' => $predictionContext ?: null,
        ];
    }

    private function buildPredictionContext(int $stationId, array $teamIds, string $predictionScope): array
    {
        $now = Carbon::now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $previousMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();
        $targetMonthStart = $predictionScope === 'next_month'
            ? $now->copy()->addMonthNoOverflow()->startOfMonth()
            : $currentMonthStart->copy();
        $targetMonthEnd = $predictionScope === 'next_month'
            ? $now->copy()->addMonthNoOverflow()->endOfMonth()
            : $currentMonthEnd->copy();

        $currentMonthIncidents = $this->fetchIncidentsForPredictionWindow($stationId, $teamIds, $currentMonthStart, $now->copy()->endOfDay());
        $previousMonthIncidents = $this->fetchIncidentsForPredictionWindow($stationId, $teamIds, $previousMonthStart, $previousMonthEnd);
        $currentMonthHourlyData = array_fill(0, 24, 0);
        $previousMonthHourlyData = array_fill(0, 24, 0);

        foreach ($currentMonthIncidents as $incident) {
            try {
                $hour = (int) Carbon::parse($incident->occurred_at ?: $incident->created_at)->format('H');
                $currentMonthHourlyData[$hour]++;
            } catch (\Throwable $e) {
            }
        }

        foreach ($previousMonthIncidents as $incident) {
            try {
                $hour = (int) Carbon::parse($incident->occurred_at ?: $incident->created_at)->format('H');
                $previousMonthHourlyData[$hour]++;
            } catch (\Throwable $e) {
            }
        }

        $peakHourEvidence = $this->estimateStationPeakHours(
            $stationId,
            $currentMonthIncidents,
            $previousMonthIncidents,
            $targetMonthStart
        );
        $likelyPeakHours = $peakHourEvidence['hours'];

        return [
            'scope' => $predictionScope,
            'scope_label' => $predictionScope === 'next_month' ? 'Predict Next Month' : 'Predict Current Month',
            'target_month_label' => $targetMonthStart->format('F Y'),
            'target_period_label' => $targetMonthStart->format('M j').' - '.$targetMonthEnd->format('M j, Y'),
            'current_month_total_incidents' => $currentMonthIncidents->count(),
            'current_month_fire_incidents' => $this->countFireIncidents($currentMonthIncidents),
            'previous_month_total_incidents' => $previousMonthIncidents->count(),
            'previous_month_fire_incidents' => $this->countFireIncidents($previousMonthIncidents),
            'current_month_hourly_data' => array_values($currentMonthHourlyData),
            'previous_month_hourly_data' => array_values($previousMonthHourlyData),
            'likely_peak_hours' => $likelyPeakHours,
            'peak_hour_evidence' => $peakHourEvidence,
        ];
    }

    private function estimateStationPeakHours(
        int $stationId,
        $currentMonthIncidents,
        $previousMonthIncidents,
        Carbon $targetMonth
    ): array {
        $observations = [];

        $appendLiveIncidents = function ($incidents, float $verifiedWeight, float $fallbackWeight) use (&$observations) {
            foreach ($incidents as $incident) {
                try {
                    $occurredAt = $incident->occurred_at ?: $incident->created_at;
                    if (! $occurredAt) {
                        continue;
                    }
                    $observations[] = [
                        'hour' => (int) Carbon::parse($occurredAt)->format('H'),
                        'weight' => $incident->occurred_at_verified_at ? $verifiedWeight : $fallbackWeight,
                    ];
                } catch (\Throwable $e) {
                }
            }
        };

        // Verified operational timestamps carry more weight than report-time fallbacks.
        $appendLiveIncidents($currentMonthIncidents, 3.0, 0.75);
        $appendLiveIncidents($previousMonthIncidents, 2.0, 0.5);

        $barangayIds = $this->resolveStationBarangays($stationId)
            ->pluck('barangay_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $liveIncidentIds = $currentMonthIncidents->concat($previousMonthIncidents)
            ->pluck('incident_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        if (! empty($barangayIds) && Schema::hasTable('historical_fire_incidents')) {
            $historicalQuery = DB::table('historical_fire_incidents')
                ->whereIn('barangay_id', $barangayIds)
                ->whereNotNull('incident_time')
                ->whereDate('incident_date', '>=', $targetMonth->copy()->subYears(3)->startOfYear());

            if (! empty($liveIncidentIds)) {
                $historicalQuery->where(function ($query) use ($liveIncidentIds) {
                    $query->whereNull('incident_id')->orWhereNotIn('incident_id', $liveIncidentIds);
                });
            }

            foreach ($historicalQuery->get(['incident_date', 'incident_time', 'incident_count', 'is_verified']) as $row) {
                try {
                    $hour = (int) Carbon::parse($row->incident_time)->format('H');
                    $isTargetMonth = Carbon::parse($row->incident_date)->month === $targetMonth->month;
                    $weight = $isTargetMonth ? 1.5 : 0.5;
                    if ((bool) $row->is_verified) {
                        $weight *= 1.25;
                    }
                    foreach (range(1, min(5, max(1, (int) ($row->incident_count ?? 1)))) as $_) {
                        $observations[] = ['hour' => $hour, 'weight' => $weight];
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        return PeakHourEstimator::estimate($observations);
    }

    private function buildAnalyticsChartContext(
        int $stationId,
        array $teamIds,
        array $predictionContext,
        ?array $modelPrediction,
        ?array $selectedBarangayPrediction,
        array $barangayPredictions,
        ?int $selectedBarangayId = null,
        $historicalWindow = '3'
    ): array {
        $now = Carbon::now();
        $currentStart = $now->copy()->subDays(6)->startOfDay();
        $currentEnd = $now->copy()->endOfDay();
        $previousStart = $currentStart->copy()->subDays(7)->startOfDay();
        $previousEnd = $currentStart->copy()->subDay()->endOfDay();

        $currentIncidents = $this->fetchIncidentsForPredictionWindow($stationId, $teamIds, $currentStart, $currentEnd);
        $previousIncidents = $this->fetchIncidentsForPredictionWindow($stationId, $teamIds, $previousStart, $previousEnd);

        $dailyLabels = [];
        $currentDailySeries = [];
        $previousDailySeries = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $currentDay = $currentStart->copy()->addDays($offset);
            $previousDay = $previousStart->copy()->addDays($offset);

            $dailyLabels[] = $currentDay->format('M j');
            $currentDailySeries[] = $currentIncidents->filter(fn ($incident) => optional($incident->created_at)->isSameDay($currentDay))->count();
            $previousDailySeries[] = $previousIncidents->filter(fn ($incident) => optional($incident->created_at)->isSameDay($previousDay))->count();
        }

        $predictedProbability = null;
        if (is_array($selectedBarangayPrediction) && isset($selectedBarangayPrediction['probability_percent'])) {
            $predictedProbability = (float) $selectedBarangayPrediction['probability_percent'];
        } elseif (is_array($modelPrediction) && isset($modelPrediction['output']['probability_percent'])) {
            $predictedProbability = (float) $modelPrediction['output']['probability_percent'];
        } else {
            $topHotspot = collect($barangayPredictions)->sortByDesc(fn ($item) => (float) ($item['probability_percent'] ?? 0))->first();
            if (! empty($topHotspot['probability_percent'])) {
                $predictedProbability = (float) $topHotspot['probability_percent'];
            }
        }

        $predictedDailyProbabilitySeries = $predictedProbability !== null
            ? array_fill(0, 7, round($predictedProbability, 2))
            : [];

        $predictedPeakHours = collect($selectedBarangayPrediction['likely_peak_hours'] ?? ($modelPrediction['output']['likely_peak_hours'] ?? []))
            ->filter()
            ->values()
            ->all();

        $insights = [];
        $currentTotal = array_sum($currentDailySeries);
        $previousTotal = array_sum($previousDailySeries);
        if ($previousTotal > 0) {
            $deltaPercent = round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1);
            $insights[] = $deltaPercent >= 0
                ? "Current seven-day incident volume is {$deltaPercent}% higher than the previous seven-day period."
                : 'Current seven-day incident volume is '.abs($deltaPercent).'% lower than the previous seven-day period.';
        }

        if ($predictedProbability !== null) {
            $insights[] = "Station-wide predicted fire-risk probability for the selected forecast window is {$predictedProbability}%.";
        }

        $highestRiskBarangay = collect($barangayPredictions)
            ->sortByDesc(fn ($item) => (float) ($item['probability_percent'] ?? 0))
            ->first();
        if (! empty($highestRiskBarangay['barangay_name'])) {
            $insights[] = "{$highestRiskBarangay['barangay_name']} shows the highest forecast probability in the current station coverage.";
        }

        if (! empty($predictedPeakHours)) {
            $insights[] = 'Peak incident probability is concentrated around '.implode(', ', array_slice($predictedPeakHours, 0, 3)).'.';
        }

        $patternContext = $this->buildHistoricalPatternContext(
            $teamIds,
            $stationId,
            $predictionContext,
            $modelPrediction,
            $selectedBarangayPrediction,
            $barangayPredictions,
            $selectedBarangayId,
            $historicalWindow
        );

        $predictedHourlySeries = array_fill(0, 24, null);
        if ($predictedProbability !== null && ! empty($predictedPeakHours)) {
            foreach ($predictedPeakHours as $label) {
                $hour = (int) explode(':', (string) $label)[0];
                if ($hour >= 0 && $hour <= 23) {
                    $predictedHourlySeries[$hour] = round($predictedProbability, 2);
                }
            }
        }

        $hourlyLabels = collect(range(0, 23))
            ->map(fn ($hour) => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00')
            ->all();

        return [
            'daily' => [
                'labels' => $dailyLabels,
                'previous_period' => $previousDailySeries,
                'current_period' => $currentDailySeries,
                'predicted_probability' => $predictedDailyProbabilitySeries,
                'predicted_probability_available' => $predictedProbability !== null,
                'missing' => [
                    'previous_period' => empty(array_filter($previousDailySeries)),
                    'current_period' => empty(array_filter($currentDailySeries)),
                    'predicted_probability' => $predictedProbability === null,
                ],
            ],
            'hourly' => [
                'labels' => $hourlyLabels,
                'historical_baseline' => $patternContext['time_of_day_pattern']['historical_baseline'] ?? array_fill(0, 24, 0),
                'current_period' => $patternContext['time_of_day_pattern']['current_actual'] ?? array_fill(0, 24, 0),
                'predicted_probability' => $predictedHourlySeries,
                'predicted_peak_hours' => $predictedPeakHours,
                'missing' => [
                    'historical_baseline' => empty(array_filter($patternContext['time_of_day_pattern']['historical_baseline'] ?? [])),
                    'current_period' => empty(array_filter($patternContext['time_of_day_pattern']['current_actual'] ?? [])),
                    'predicted_probability' => $predictedProbability === null || empty($predictedPeakHours),
                ],
            ],
            'insights' => $insights,
            'recent_trend' => [
                'label' => 'Recent Trend',
                'period_label' => $currentStart->format('M j').' - '.$currentEnd->format('M j, Y'),
            ],
            'historical_trend' => $patternContext['historical_trend'],
            'seasonal_pattern' => $patternContext['seasonal_pattern'],
            'monthly_pattern' => $patternContext['monthly_pattern'],
            'ml_trend_analysis' => $patternContext['ml_trend_analysis'],
            'day_of_week_pattern' => $patternContext['day_of_week_pattern'],
            'time_of_day_pattern' => $patternContext['time_of_day_pattern'],
            'weather_pattern' => $patternContext['weather_pattern'],
            'historical_vs_predicted' => $patternContext['historical_vs_predicted'],
            'prediction_factors' => $patternContext['prediction_factors'],
        ];
    }

    private function buildHistoricalPatternContext(
        array $teamIds,
        int $stationId,
        array $predictionContext,
        ?array $modelPrediction,
        ?array $selectedBarangayPrediction,
        array $barangayPredictions,
        ?int $selectedBarangayId = null,
        $historicalWindow = '3'
    ): array {
        $targetMonthStart = $this->resolvePredictionTargetMonthStart($predictionContext);
        $comparisonContext = $this->buildCalendarAlignedComparisonContext(
            $teamIds,
            $stationId,
            $targetMonthStart,
            $selectedBarangayId,
            $historicalWindow
        );

        $predictedIncidentCount = null;
        $predictedProbability = null;
        if (is_array($selectedBarangayPrediction)) {
            $predictedIncidentCount = $selectedBarangayPrediction['predicted_fire_incidents']
                ?? $selectedBarangayPrediction['predicted_incident_count']
                ?? null;
            $predictedProbability = $selectedBarangayPrediction['probability_percent'] ?? null;
        }

        if ($predictedIncidentCount === null && is_array($modelPrediction)) {
            $predictedIncidentCount = data_get($modelPrediction, 'output.predicted_incident_count');
        }

        if ($predictedProbability === null && is_array($modelPrediction)) {
            $predictedProbability = data_get($modelPrediction, 'output.probability_percent');
        }
        $forecastLabel = $comparisonContext['current_label'];

        $highestRiskBarangay = collect($barangayPredictions)
            ->sortByDesc(fn ($item) => (float) ($item['probability_percent'] ?? 0))
            ->first();

        $predictionFactorDrivers = collect(
            $selectedBarangayPrediction['drivers']
                ?? data_get($modelPrediction, 'output.drivers')
                ?? ($highestRiskBarangay['drivers'] ?? [])
        )
            ->map(function ($driver) {
                if (is_string($driver)) {
                    return $driver;
                }

                return $driver['summary'] ?? null;
            })
            ->filter()
            ->values()
            ->take(6)
            ->all();

        $predictionFactorWatchItems = collect(
            $selectedBarangayPrediction['watch_items']
                ?? data_get($modelPrediction, 'output.watch_items')
                ?? ($highestRiskBarangay['watch_items'] ?? [])
        )
            ->filter()
            ->values()
            ->take(6)
            ->all();

        return [
            'historical_trend' => $comparisonContext['historical_trend'],
            'seasonal_pattern' => $comparisonContext['seasonal_pattern'],
            'monthly_pattern' => $comparisonContext['monthly_pattern'],
            'ml_trend_analysis' => $this->buildMlTrendAnalysisSeries($stationId, $selectedBarangayId),
            'day_of_week_pattern' => $comparisonContext['day_of_week_pattern'],
            'time_of_day_pattern' => $comparisonContext['time_of_day_pattern'],
            'weather_pattern' => $comparisonContext['weather_pattern'],
            'historical_window_meta' => $comparisonContext['historical_window_meta'],
            'historical_vs_predicted' => [
                'labels' => $comparisonContext['historical_vs_forecast_labels'],
                'historical_values' => $comparisonContext['historical_vs_forecast_historical'],
                'historical_baseline' => $comparisonContext['historical_vs_forecast_baseline'],
                'current_actual' => $comparisonContext['historical_vs_forecast_current'],
                'forecast_incidents' => array_replace(
                    $comparisonContext['historical_vs_forecast_predicted'],
                    [count($comparisonContext['historical_vs_forecast_predicted']) - 1 => $predictedIncidentCount !== null ? (int) round((float) $predictedIncidentCount) : null]
                ),
                'forecast_probability' => array_replace(
                    $comparisonContext['historical_vs_forecast_probability'],
                    [count($comparisonContext['historical_vs_forecast_probability']) - 1 => $predictedProbability !== null ? round((float) $predictedProbability, 1) : null]
                ),
                'forecast_label' => $forecastLabel,
                'missing' => [
                    'actual_incidents' => empty(array_filter($comparisonContext['historical_vs_forecast_historical'], fn ($value) => $value !== null)),
                    'historical_baseline' => empty(array_filter($comparisonContext['historical_vs_forecast_baseline'], fn ($value) => $value !== null)),
                    'current_actual' => empty(array_filter($comparisonContext['historical_vs_forecast_current'], fn ($value) => $value !== null)),
                    'forecast_incidents' => $predictedIncidentCount === null,
                    'forecast_probability' => $predictedProbability === null,
                ],
            ],
            'prediction_factors' => [
                'drivers' => $predictionFactorDrivers,
                'watch_items' => $predictionFactorWatchItems,
                'target_label' => $forecastLabel,
                'selected_barangay' => $selectedBarangayPrediction['barangay_name'] ?? ($highestRiskBarangay['barangay_name'] ?? null),
                'confidence_band' => data_get($modelPrediction, 'output.confidence_band'),
                'missing' => [
                    'drivers' => empty($predictionFactorDrivers),
                    'watch_items' => empty($predictionFactorWatchItems),
                ],
            ],
        ];
    }

    private function buildCalendarAlignedComparisonContext(
        array $teamIds,
        int $stationId,
        Carbon $targetMonthStart,
        ?int $selectedBarangayId = null,
        $historicalWindow = '3'
    ): array {
        $now = Carbon::now();
        $isCurrentMonthTarget = $targetMonthStart->isSameMonth($now) && $targetMonthStart->year === $now->year;
        $currentPeriodEnd = $isCurrentMonthTarget
            ? $now->copy()->endOfDay()
            : $targetMonthStart->copy()->endOfMonth()->endOfDay();
        $periodDayLimit = $isCurrentMonthTarget
            ? min($now->day, $targetMonthStart->daysInMonth)
            : $targetMonthStart->daysInMonth;

        $historicalYears = $this->resolveHistoricalComparisonYears($stationId, $targetMonthStart, $selectedBarangayId, $historicalWindow);
        $currentRows = $this->fetchCurrentActualIncidentRows($teamIds, $targetMonthStart, $currentPeriodEnd, $selectedBarangayId);
        $historicalPeriods = collect($historicalYears)->map(function (int $year) use ($stationId, $targetMonthStart, $periodDayLimit, $selectedBarangayId) {
            $periodStart = Carbon::create($year, $targetMonthStart->month, 1)->startOfDay();
            $periodEnd = Carbon::create($year, $targetMonthStart->month, min($periodDayLimit, Carbon::create($year, $targetMonthStart->month, 1)->daysInMonth))
                ->endOfDay();

            return [
                'year' => $year,
                'start' => $periodStart,
                'end' => $periodEnd,
                'rows' => $this->fetchHistoricalIncidentRows($stationId, $periodStart, $periodEnd, $selectedBarangayId),
            ];
        })->values();

        $dailyLabels = collect(range(1, $periodDayLimit))->map(fn ($day) => $targetMonthStart->copy()->day($day)->format('M j'))->all();
        $currentDailySeries = [];
        foreach (range(1, $periodDayLimit) as $day) {
            $currentDailySeries[] = $this->sumIncidentRowsForDay($currentRows, $targetMonthStart->copy()->day($day));
        }

        $historicalDailySeries = $historicalPeriods->map(function (array $period) use ($targetMonthStart, $periodDayLimit) {
            $values = [];
            foreach (range(1, $periodDayLimit) as $day) {
                $values[] = $this->sumIncidentRowsForMonthDay($period['rows'], $targetMonthStart->month, $day);
            }

            return [
                'year' => $period['year'],
                'label' => (string) $period['year'],
                'values' => $values,
                'total' => array_sum($values),
            ];
        })->values();

        $historicalAverageDaily = [];
        for ($index = 0; $index < count($dailyLabels); $index++) {
            $values = $historicalDailySeries->pluck("values.$index")->filter(fn ($value) => $value !== null)->all();
            $historicalAverageDaily[] = count($values) ? round(array_sum($values) / count($values), 2) : null;
        }

        $historicalTrend = [
            'labels' => $dailyLabels,
            'current_actual' => $currentDailySeries,
            'historical_average' => $historicalAverageDaily,
            'historical_years' => $historicalDailySeries->all(),
            'current_label' => $isCurrentMonthTarget
                ? $targetMonthStart->format('M').' 1-'.$periodDayLimit.', '.$targetMonthStart->year
                : $targetMonthStart->format('F Y'),
            'missing' => [
                'current_actual' => empty(array_filter($currentDailySeries)),
                'historical_average' => empty(array_filter($historicalAverageDaily, fn ($value) => $value !== null)),
            ],
        ];

        $previousYearAveragesByMonth = $this->buildHistoricalMonthlyAveragesByMonth($stationId, $targetMonthStart->year, $selectedBarangayId);
        $currentYearMonthlyActual = $this->buildCurrentYearMonthlyActuals($teamIds, $stationId, $targetMonthStart->year, $selectedBarangayId);
        $monthlyPattern = [
            'labels' => collect(range(1, 12))->map(fn ($month) => Carbon::createFromDate(2000, $month, 1)->format('M'))->all(),
            'current_actual' => $currentYearMonthlyActual,
            'historical_average' => $previousYearAveragesByMonth,
            'current_month_history' => $historicalPeriods
                ->map(fn (array $period) => (int) collect($period['rows'])->sum(fn ($row) => (int) ($row->incident_count ?? 1)))
                ->values()
                ->all(),
            'comparison_years' => $historicalYears,
            'missing' => [
                'current_actual' => empty(array_filter($currentYearMonthlyActual, fn ($value) => $value !== null)),
                'historical_average' => empty(array_filter($previousYearAveragesByMonth, fn ($value) => $value !== null)),
            ],
        ];

        $historicalSeasonAverages = $this->buildHistoricalSeasonAverages($stationId, $targetMonthStart->year, $selectedBarangayId);
        $currentSeasonActual = $this->buildCurrentSeasonActuals($teamIds, $stationId, $targetMonthStart->year, $selectedBarangayId);
        $seasonalPattern = [
            'labels' => ['Dry season', 'Wet season'],
            'current_actual' => $currentSeasonActual,
            'historical_average' => $historicalSeasonAverages,
            'current_season' => $this->deriveSeasonFromMonth($targetMonthStart->month),
            'missing' => [
                'current_actual' => empty(array_filter($currentSeasonActual, fn ($value) => $value !== null)),
                'historical_average' => empty(array_filter($historicalSeasonAverages, fn ($value) => $value !== null)),
            ],
        ];

        $historicalWeekdaySeries = $historicalPeriods->map(function (array $period) {
            return $this->buildWeekdayDistribution($period['rows'], true);
        })->values();
        $currentWeekday = $this->buildWeekdayDistribution($currentRows, false);
        $historicalWeekdayAverage = [];
        for ($i = 0; $i < 7; $i++) {
            $values = $historicalWeekdaySeries->pluck($i)->filter(fn ($value) => $value !== null)->all();
            $historicalWeekdayAverage[] = count($values) ? round(array_sum($values) / count($values), 2) : null;
        }

        $dayOfWeekPattern = [
            'labels' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            'current_actual' => $currentWeekday,
            'historical_average' => $historicalWeekdayAverage,
            'missing' => [
                'current_actual' => empty(array_filter($currentWeekday)),
                'historical_average' => empty(array_filter($historicalWeekdayAverage, fn ($value) => $value !== null)),
            ],
        ];

        $historicalHourlySeries = $historicalPeriods->map(function (array $period) {
            return $this->buildHourlyDistribution($period['rows'], true);
        })->values();
        $currentHourly = $this->buildHourlyDistribution($currentRows, false);
        $historicalHourlyAverage = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $values = $historicalHourlySeries->pluck($hour)->filter(fn ($value) => $value !== null)->all();
            $historicalHourlyAverage[] = count($values) ? round(array_sum($values) / count($values), 2) : null;
        }

        $timeOfDayPattern = [
            'historical_baseline' => $historicalHourlyAverage,
            'current_actual' => $currentHourly,
            'missing' => [
                'historical_baseline' => empty(array_filter($historicalHourlyAverage, fn ($value) => $value !== null)),
                'current_actual' => empty(array_filter($currentHourly)),
            ],
        ];

        $weatherComparisonRows = $historicalPeriods->map(function (array $period) {
            return [
                'label' => (string) $period['year'],
                'incident_count' => (int) collect($period['rows'])->sum(fn ($row) => (int) ($row->incident_count ?? 1)),
                'temperature_2m' => $this->averageWeatherField($period['rows'], 'temperature_2m', 'temperature'),
                'relative_humidity_2m' => $this->averageWeatherField($period['rows'], 'relative_humidity_2m', 'humidity'),
                'wind_speed_10m' => $this->averageWeatherField($period['rows'], 'wind_speed_10m', 'wind_speed'),
                'wind_gusts_10m' => $this->averageWeatherField($period['rows'], 'wind_gusts_10m', 'wind_gusts'),
                'precipitation' => $this->averageWeatherField($period['rows'], 'precipitation'),
                'rain' => $this->averageWeatherField($period['rows'], 'rain'),
            ];
        })->values();
        $weatherComparisonRows->push([
            'label' => $isCurrentMonthTarget ? $targetMonthStart->year.' current actual' : $targetMonthStart->year.' selected month',
            'incident_count' => (int) collect($currentRows)->sum(fn ($row) => (int) ($row->incident_count ?? 1)),
            'temperature_2m' => $this->averageWeatherField($currentRows, 'temperature_2m', 'temperature'),
            'relative_humidity_2m' => $this->averageWeatherField($currentRows, 'relative_humidity_2m', 'humidity'),
            'wind_speed_10m' => $this->averageWeatherField($currentRows, 'wind_speed_10m', 'wind_speed'),
            'wind_gusts_10m' => $this->averageWeatherField($currentRows, 'wind_gusts_10m', 'wind_gusts'),
            'precipitation' => $this->averageWeatherField($currentRows, 'precipitation'),
            'rain' => $this->averageWeatherField($currentRows, 'rain'),
        ]);

        $weatherPattern = [
            'labels' => $weatherComparisonRows->pluck('label')->all(),
            'actual_incidents' => $weatherComparisonRows->pluck('incident_count')->map(fn ($value) => $value !== null ? (int) $value : null)->all(),
            'temperature_2m' => $weatherComparisonRows->pluck('temperature_2m')->map(fn ($value) => $value !== null ? round((float) $value, 1) : null)->all(),
            'relative_humidity_2m' => $weatherComparisonRows->pluck('relative_humidity_2m')->map(fn ($value) => $value !== null ? round((float) $value, 1) : null)->all(),
            'wind_speed_10m' => $weatherComparisonRows->pluck('wind_speed_10m')->map(fn ($value) => $value !== null ? round((float) $value, 1) : null)->all(),
            'wind_gusts_10m' => $weatherComparisonRows->pluck('wind_gusts_10m')->map(fn ($value) => $value !== null ? round((float) $value, 1) : null)->all(),
            'precipitation' => $weatherComparisonRows->pluck('precipitation')->map(fn ($value) => $value !== null ? round((float) $value, 1) : null)->all(),
            'rain' => $weatherComparisonRows->pluck('rain')->map(fn ($value) => $value !== null ? round((float) $value, 1) : null)->all(),
            'missing' => [
                'actual_incidents' => empty(array_filter($weatherComparisonRows->pluck('incident_count')->all(), fn ($value) => $value !== null)),
                'temperature_2m' => empty(array_filter($weatherComparisonRows->pluck('temperature_2m')->all(), fn ($value) => $value !== null)),
                'relative_humidity_2m' => empty(array_filter($weatherComparisonRows->pluck('relative_humidity_2m')->all(), fn ($value) => $value !== null)),
                'wind_speed_10m' => empty(array_filter($weatherComparisonRows->pluck('wind_speed_10m')->all(), fn ($value) => $value !== null)),
                'wind_gusts_10m' => empty(array_filter($weatherComparisonRows->pluck('wind_gusts_10m')->all(), fn ($value) => $value !== null)),
                'precipitation' => empty(array_filter($weatherComparisonRows->pluck('precipitation')->all(), fn ($value) => $value !== null)),
                'rain' => empty(array_filter($weatherComparisonRows->pluck('rain')->all(), fn ($value) => $value !== null)),
            ],
        ];

        $historicalTotals = $historicalPeriods->map(fn (array $period) => (int) collect($period['rows'])->sum(fn ($row) => (int) ($row->incident_count ?? 1)))->values()->all();
        $historicalBaselineTotal = count($historicalTotals) ? round(array_sum($historicalTotals) / count($historicalTotals), 2) : null;
        $currentActualTotal = (int) collect($currentRows)->sum(fn ($row) => (int) ($row->incident_count ?? 1));
        $historicalVsForecastLabels = array_map(fn ($year) => (string) $year, $historicalYears);
        $historicalVsForecastLabels[] = '3-Year Baseline';
        $historicalVsForecastLabels[] = 'Current Actual';
        $historicalVsForecastLabels[] = 'Forecast';

        $historicalSeries = array_merge($historicalTotals, [null, null, null]);
        $baselineSeries = array_merge(array_fill(0, count($historicalYears), null), [$historicalBaselineTotal, null, null]);
        $currentSeries = array_merge(array_fill(0, count($historicalYears) + 1, null), [$currentActualTotal, null]);
        $predictedSeries = array_merge(array_fill(0, count($historicalYears) + 2, null), [null]);

        return [
            'historical_window_meta' => [
                'value' => (string) $historicalWindow,
                'label' => $this->formatHistoricalWindowLabel($historicalWindow, count($historicalYears)),
                'year_count' => count($historicalYears),
                'years' => $historicalYears,
            ],
            'current_label' => $historicalTrend['current_label'],
            'historical_trend' => $historicalTrend,
            'monthly_pattern' => $monthlyPattern,
            'seasonal_pattern' => $seasonalPattern,
            'day_of_week_pattern' => $dayOfWeekPattern,
            'time_of_day_pattern' => $timeOfDayPattern,
            'weather_pattern' => $weatherPattern,
            'historical_vs_forecast_labels' => $historicalVsForecastLabels,
            'historical_vs_forecast_historical' => $historicalSeries,
            'historical_vs_forecast_baseline' => $baselineSeries,
            'historical_vs_forecast_current' => $currentSeries,
            'historical_vs_forecast_predicted' => $predictedSeries,
            'historical_vs_forecast_probability' => array_fill(0, count($historicalVsForecastLabels), null),
        ];
    }

    public function previewHistoricalWeatherArchive(Request $request)
    {
        $validated = $request->validate([
            'station_id' => ['required', 'integer'],
            'barangay_id' => ['required', 'integer', 'min:1'],
            'historical_window' => ['nullable'],
        ]);

        $stationOfficer = StationOfficer::where('user_id', Auth::id())->first();
        if (! $stationOfficer || (int) $stationOfficer->station_id !== (int) $validated['station_id']) {
            abort(403, 'You are not authorized to preview archive weather for this station.');
        }

        $stationId = (int) $validated['station_id'];
        $barangayId = (int) $validated['barangay_id'];
        $historicalWindow = $validated['historical_window'] ?? '3';

        $barangayScope = $this->resolveStationBarangays($stationId)
            ->first(fn ($row) => (int) ($row->barangay_id ?? 0) === $barangayId);

        if (! $barangayScope) {
            return response()->json([
                'success' => false,
                'message' => 'The selected barangay is outside the current station scope.',
            ], 422);
        }

        $coordinateRow = $this->resolveBarangayArchiveLocation($barangayId);

        if (
            ! $coordinateRow
            || $coordinateRow->lat === null
            || $coordinateRow->lng === null
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No barangay marker coordinates were found for the selected barangay.',
            ], 422);
        }

        $archivePeriods = $this->resolveHistoricalArchivePeriods($stationId, $barangayId, $historicalWindow);
        if (empty($archivePeriods)) {
            return response()->json([
                'success' => true,
                'preview_only' => true,
                'message' => 'No historical fire-incident years were found for the selected barangay.',
                'barangay' => [
                    'id' => $barangayId,
                    'name' => $coordinateRow->barangay_name,
                    'city_name' => $coordinateRow->city_name,
                ],
                'barangay_hall' => [
                    'barangay_hall_id' => (int) $coordinateRow->barangay_hall_id,
                    'marker_id' => (int) $coordinateRow->marker_id,
                ],
                'coordinates' => [
                    'latitude' => (float) $coordinateRow->lat,
                    'longitude' => (float) $coordinateRow->lng,
                ],
                'years' => [],
                'archive_periods' => [],
                'rows' => [],
            ]);
        }

        try {
            Log::info('Historical weather preview requested', [
                'station_id' => $stationId,
                'barangay_id' => $barangayId,
                'historical_window' => $historicalWindow,
                'archive_periods' => $archivePeriods,
            ]);

            $previewPayload = $this->buildHistoricalWeatherPreviewPayload(
                $stationId,
                $barangayId,
                (float) $coordinateRow->lat,
                (float) $coordinateRow->lng,
                $archivePeriods
            );

            return response()->json([
                'success' => true,
                'preview_only' => true,
                'message' => 'Historical weather preview generated successfully.',
                'barangay' => [
                    'id' => $barangayId,
                    'name' => $coordinateRow->barangay_name,
                    'city_name' => $coordinateRow->city_name,
                ],
                'barangay_hall' => [
                    'barangay_hall_id' => (int) $coordinateRow->barangay_hall_id,
                    'marker_id' => (int) $coordinateRow->marker_id,
                ],
                'coordinates' => [
                    'latitude' => (float) $coordinateRow->lat,
                    'longitude' => (float) $coordinateRow->lng,
                ],
                'historical_window' => [
                    'value' => (string) $historicalWindow,
                    'label' => $this->formatHistoricalWindowLabel($historicalWindow, count($archivePeriods)),
                ],
                'years' => collect($archivePeriods)->pluck('year')->values()->all(),
                'archive_periods' => $archivePeriods,
                'requested_variables' => [
                    'temperature_2m',
                    'relative_humidity_2m',
                    'precipitation',
                    'rain',
                    'weather_code',
                    'vapour_pressure_deficit',
                    'wind_gusts_10m',
                    'wind_speed_10m',
                ],
                'request_template' => 'https://archive-api.open-meteo.com/v1/archive',
                'archive_requests' => collect($archivePeriods)->map(fn ($period) => [
                    'year' => (int) ($period['year'] ?? 0),
                    'start_date' => $period['start_date'] ?? null,
                    'end_date' => $period['end_date'] ?? null,
                    'url' => $this->buildOpenMeteoArchiveUrl(
                        (float) $coordinateRow->lat,
                        (float) $coordinateRow->lng,
                        (string) ($period['start_date'] ?? ''),
                        (string) ($period['end_date'] ?? '')
                    ),
                ])->values()->all(),
                'rows' => $previewPayload['rows'],
                'summary' => $previewPayload['summary'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Historical weather preview failed', [
                'station_id' => $stationId,
                'barangay_id' => $barangayId,
                'historical_window' => $historicalWindow,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Historical weather preview failed.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function saveHistoricalWeatherArchive(Request $request)
    {
        $validated = $request->validate([
            'station_id' => ['required', 'integer'],
            'barangay_id' => ['required', 'integer', 'min:1'],
            'historical_window' => ['nullable'],
        ]);

        $stationOfficer = StationOfficer::where('user_id', Auth::id())->first();
        if (! $stationOfficer || (int) $stationOfficer->station_id !== (int) $validated['station_id']) {
            abort(403, 'You are not authorized to save archive weather for this station.');
        }

        $stationId = (int) $validated['station_id'];
        $barangayId = (int) $validated['barangay_id'];
        $historicalWindow = $validated['historical_window'] ?? '3';

        $coordinateRow = $this->resolveBarangayArchiveLocation($barangayId);

        if (
            ! $coordinateRow
            || $coordinateRow->lat === null
            || $coordinateRow->lng === null
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No barangay marker coordinates were found for the selected barangay.',
            ], 422);
        }

        $archivePeriods = $this->resolveHistoricalArchivePeriods($stationId, $barangayId, $historicalWindow);
        if (empty($archivePeriods)) {
            return response()->json([
                'success' => false,
                'message' => 'No historical fire-incident years were found for the selected barangay.',
            ], 422);
        }

        try {
            $previewPayload = $this->buildHistoricalWeatherPreviewPayload(
                $stationId,
                $barangayId,
                (float) $coordinateRow->lat,
                (float) $coordinateRow->lng,
                $archivePeriods
            );

            $saveSummary = DB::transaction(function () use ($barangayId, $previewPayload) {
                $updatedRows = 0;
                $createdPlaceholders = 0;
                $availableColumns = $this->getHistoricalFireIncidentWritableColumns();

                foreach ($previewPayload['rows'] as $row) {
                    $monthStart = Carbon::create((int) $row['year'], (int) $row['month'], 1)->startOfMonth();
                    $monthEnd = $monthStart->copy()->endOfMonth();

                    $payload = $this->filterHistoricalFireIncidentPayload([
                        'temperature' => $row['temperature_2m'],
                        'temperature_2m' => $row['temperature_2m'],
                        'humidity' => $row['relative_humidity_2m'],
                        'relative_humidity_2m' => $row['relative_humidity_2m'],
                        'precipitation' => $row['precipitation'],
                        'rain' => $row['rain'],
                        'weather_code' => $row['weather_code'],
                        'vapour_pressure_deficit' => $row['vapour_pressure_deficit'],
                        'wind_speed' => $row['wind_speed_10m'],
                        'wind_speed_10m' => $row['wind_speed_10m'],
                        'wind_gusts' => $row['wind_gusts_10m'],
                        'wind_gusts_10m' => $row['wind_gusts_10m'],
                        'weather_condition' => $row['weather_code'] !== null ? 'weather_code:'.$row['weather_code'] : null,
                        'updated_at' => now(),
                    ], $availableColumns);

                    $monthUpdateCount = DB::table('historical_fire_incidents')
                        ->where('barangay_id', $barangayId)
                        ->whereBetween('incident_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                        ->update($payload);

                    if ($monthUpdateCount > 0) {
                        $updatedRows += $monthUpdateCount;

                        continue;
                    }

                    DB::table('historical_fire_incidents')->updateOrInsert(
                        [
                            'barangay_id' => $barangayId,
                            'source_reference' => sprintf('open-meteo-archive:%04d-%02d', (int) $row['year'], (int) $row['month']),
                        ],
                        $this->filterHistoricalFireIncidentPayload([
                            'incident_id' => null,
                            'incident_date' => $monthStart->toDateString(),
                            'incident_time' => null,
                            'source_name' => 'Open-Meteo Archive',
                            'source_type' => 'other',
                            'fire_classification' => 'undetermined',
                            'fire_cause' => null,
                            'incident_location' => 'Monthly weather placeholder',
                            'incident_count' => 0,
                            'injured_count' => 0,
                            'death_count' => 0,
                            'estimated_damage' => null,
                            'temperature' => $row['temperature_2m'],
                            'temperature_2m' => $row['temperature_2m'],
                            'humidity' => $row['relative_humidity_2m'],
                            'relative_humidity_2m' => $row['relative_humidity_2m'],
                            'precipitation' => $row['precipitation'],
                            'rain' => $row['rain'],
                            'wind_speed' => $row['wind_speed_10m'],
                            'wind_speed_10m' => $row['wind_speed_10m'],
                            'wind_gusts' => $row['wind_gusts_10m'],
                            'wind_gusts_10m' => $row['wind_gusts_10m'],
                            'wind_direction' => null,
                            'wind_direction_10m' => null,
                            'weather_code' => $row['weather_code'],
                            'vapour_pressure_deficit' => $row['vapour_pressure_deficit'],
                            'weather_condition' => $row['weather_code'] !== null ? 'weather_code:'.$row['weather_code'] : null,
                            'is_verified' => false,
                            'notes' => 'Synthetic monthly weather baseline row created from Open-Meteo archive for months without incident rows.',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ], $availableColumns)
                    );

                    $createdPlaceholders++;
                }

                return [
                    'updated_rows' => $updatedRows,
                    'created_placeholders' => $createdPlaceholders,
                    'processed_months' => count($previewPayload['rows']),
                ];
            });

            Log::info('Historical weather save completed', [
                'station_id' => $stationId,
                'barangay_id' => $barangayId,
                'summary' => $saveSummary,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Historical archive weather saved to historical fire incidents.',
                'barangay' => [
                    'id' => $barangayId,
                    'name' => $coordinateRow->barangay_name,
                    'city_name' => $coordinateRow->city_name,
                ],
                'years' => collect($archivePeriods)->pluck('year')->values()->all(),
                'summary' => array_merge($previewPayload['summary'], $saveSummary),
            ]);
        } catch (\Throwable $e) {
            Log::error('Historical weather save failed', [
                'station_id' => $stationId,
                'barangay_id' => $barangayId,
                'historical_window' => $historicalWindow,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Historical weather save failed.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    private function resolveHistoricalComparisonYears(int $stationId, Carbon $targetMonthStart, ?int $selectedBarangayId = null, $historicalWindow = '3'): array
    {
        $years = collect();

        $historicalQuery = DB::table('historical_fire_incidents')
            ->whereMonth('incident_date', $targetMonthStart->month)
            ->whereYear('incident_date', '<', $targetMonthStart->year);

        if ($selectedBarangayId) {
            $historicalQuery->where('barangay_id', $selectedBarangayId);
        } else {
            $barangayIds = $this->resolveStationBarangays($stationId)
                ->pluck('barangay_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if (! empty($barangayIds)) {
                $historicalQuery->whereIn('barangay_id', $barangayIds);
            }
        }

        $years = $years->merge(
            $historicalQuery
                ->selectRaw('DISTINCT EXTRACT(YEAR FROM incident_date) as year_value')
                ->orderByDesc('year_value')
                ->pluck('year_value')
                ->map(fn ($value) => (int) $value)
        );

        $resolvedYears = $years
            ->filter(fn ($year) => $year > 0)
            ->unique()
            ->sortDesc()
            ->values();

        $limit = $this->normalizeHistoricalWindowLimit($historicalWindow);
        if ($limit !== null) {
            $resolvedYears = $resolvedYears->take($limit);
        }

        return $resolvedYears->all();
    }

    private function resolveHistoricalArchivePeriods(int $stationId, int $barangayId, $historicalWindow = '3'): array
    {
        $barangayIds = $this->resolveStationBarangays($stationId)
            ->pluck('barangay_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (! in_array($barangayId, $barangayIds, true)) {
            return [];
        }

        $years = DB::table('historical_fire_incidents')
            ->where('barangay_id', $barangayId)
            ->selectRaw('CAST(EXTRACT(YEAR FROM incident_date) AS INTEGER) as year_value')
            ->distinct()
            ->orderByDesc('year_value')
            ->pluck('year_value')
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($year) => $year > 0)
            ->unique()
            ->sortDesc()
            ->values();

        $limit = $this->normalizeHistoricalWindowLimit($historicalWindow);
        if ($limit !== null) {
            $years = $years->take($limit);
        }

        $today = Carbon::today();

        return $years->map(function (int $year) use ($today) {
            $startDate = Carbon::create($year, 1, 1)->toDateString();
            $endDate = Carbon::create($year, 12, 31)->toDateString();

            if ($year >= (int) $today->year) {
                $endDate = $today->toDateString();
            }

            return [
                'year' => $year,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        })->values()->all();
    }

    private function buildHistoricalWeatherPreviewPayload(int $stationId, int $barangayId, float $latitude, float $longitude, array $archivePeriods): array
    {
        $rows = [];
        $totalIncidents = 0;
        $monthsWithWeather = 0;

        foreach ($archivePeriods as $period) {
            $year = (int) ($period['year'] ?? 0);
            $startDate = (string) ($period['start_date'] ?? sprintf('%d-01-01', $year));
            $endDate = (string) ($period['end_date'] ?? sprintf('%d-12-31', $year));

            $monthlyIncidentCounts = DB::table('historical_fire_incidents')
                ->where('barangay_id', $barangayId)
                ->whereYear('incident_date', $year)
                ->selectRaw('CAST(EXTRACT(MONTH FROM incident_date) AS INTEGER) as month_value, SUM(COALESCE(incident_count, 1)) as total_incidents, COUNT(*) as matched_row_count')
                ->groupByRaw('CAST(EXTRACT(MONTH FROM incident_date) AS INTEGER)')
                ->get()
                ->mapWithKeys(fn ($row) => [
                    (int) $row->month_value => [
                        'incident_count' => (int) ($row->total_incidents ?? 0),
                        'matched_row_count' => (int) ($row->matched_row_count ?? 0),
                    ],
                ]);

            $archivePayload = $this->fetchOpenMeteoArchiveRange($latitude, $longitude, $startDate, $endDate, $year);
            $monthlyWeather = $this->summarizeOpenMeteoArchiveByMonth($archivePayload);

            for ($month = 1; $month <= 12; $month++) {
                $dbMonthRow = $monthlyIncidentCounts->get($month, [
                    'incident_count' => 0,
                    'matched_row_count' => 0,
                ]);
                $incidentCount = (int) ($dbMonthRow['incident_count'] ?? 0);
                $weatherRow = $monthlyWeather[$month] ?? [];

                $row = [
                    'year' => (int) $year,
                    'month' => $month,
                    'month_label' => Carbon::create((int) $year, $month, 1)->format('M Y'),
                    'incident_count' => $incidentCount,
                    'historical_row_count' => (int) ($dbMonthRow['matched_row_count'] ?? 0),
                    'temperature_2m' => $weatherRow['temperature_2m'] ?? null,
                    'relative_humidity_2m' => $weatherRow['relative_humidity_2m'] ?? null,
                    'precipitation' => $weatherRow['precipitation'] ?? null,
                    'rain' => $weatherRow['rain'] ?? null,
                    'weather_code' => $weatherRow['weather_code'] ?? null,
                    'vapour_pressure_deficit' => $weatherRow['vapour_pressure_deficit'] ?? null,
                    'wind_gusts_10m' => $weatherRow['wind_gusts_10m'] ?? null,
                    'wind_speed_10m' => $weatherRow['wind_speed_10m'] ?? null,
                ];

                if (collect($weatherRow)->filter(fn ($value) => $value !== null)->isNotEmpty()) {
                    $monthsWithWeather++;
                }

                $totalIncidents += $incidentCount;
                $rows[] = $row;
            }
        }

        return [
            'rows' => $rows,
            'summary' => [
                'row_count' => count($rows),
                'year_count' => count($archivePeriods),
                'total_incidents' => $totalIncidents,
                'months_with_weather' => $monthsWithWeather,
                'station_id' => $stationId,
                'barangay_id' => $barangayId,
            ],
        ];
    }

    private function resolveBarangayArchiveLocation(int $barangayId): ?object
    {
        return DB::table('barangay_halls')
            ->join('markers', 'barangay_halls.marker_id', '=', 'markers.marker_id')
            ->join('barangays', 'barangay_halls.barangay_id', '=', 'barangays.barangay_id')
            ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
            ->where('barangay_halls.barangay_id', $barangayId)
            ->whereNotNull('barangay_halls.marker_id')
            ->select(
                'barangay_halls.barangay_hall_id',
                'barangay_halls.marker_id',
                'barangays.barangay_id',
                'barangays.barangay_name',
                'cities.city_name',
                'markers.lat',
                'markers.lng'
            )
            ->first();
    }

    private function fetchOpenMeteoArchiveRange(float $latitude, float $longitude, string $startDate, string $endDate, int $year): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        if ($start->greaterThan($end)) {
            throw new \RuntimeException(sprintf(
                'Open-Meteo archive request for %d is invalid because the requested year is in the future.',
                $year
            ));
        }

        $response = Http::connectTimeout(10)
            ->timeout(60)
            ->acceptJson()
            ->get('https://archive-api.open-meteo.com/v1/archive', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'hourly' => 'temperature_2m,relative_humidity_2m,precipitation,rain,weather_code,vapour_pressure_deficit,wind_gusts_10m,wind_speed_10m',
                'timezone' => 'auto',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(sprintf(
                'Open-Meteo archive request failed for %d with HTTP %d.',
                $year,
                $response->status()
            ));
        }

        return $response->json() ?: [];
    }

    private function buildOpenMeteoArchiveUrl(float $latitude, float $longitude, string $startDate, string $endDate): string
    {
        return 'https://archive-api.open-meteo.com/v1/archive?'.http_build_query([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'hourly' => 'temperature_2m,relative_humidity_2m,precipitation,rain,weather_code,vapour_pressure_deficit,wind_gusts_10m,wind_speed_10m',
            'timezone' => 'auto',
        ]);
    }

    private function summarizeOpenMeteoArchiveByMonth(array $archivePayload): array
    {
        $hourly = $archivePayload['hourly'] ?? [];
        $times = $hourly['time'] ?? [];

        $months = [];
        foreach ($times as $index => $timestamp) {
            try {
                $month = Carbon::parse($timestamp)->month;
            } catch (\Throwable $e) {
                continue;
            }

            if (! isset($months[$month])) {
                $months[$month] = [
                    'temperature_2m' => [],
                    'relative_humidity_2m' => [],
                    'precipitation' => [],
                    'rain' => [],
                    'weather_code' => [],
                    'vapour_pressure_deficit' => [],
                    'wind_gusts_10m' => [],
                    'wind_speed_10m' => [],
                ];
            }

            foreach (array_keys($months[$month]) as $field) {
                $value = $hourly[$field][$index] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }

                $months[$month][$field][] = $value;
            }
        }

        $summary = [];
        foreach ($months as $month => $values) {
            $summary[$month] = [
                'temperature_2m' => $this->averageNonNullValues($values['temperature_2m']),
                'relative_humidity_2m' => $this->averageNonNullValues($values['relative_humidity_2m']),
                'precipitation' => $this->sumNonNullValues($values['precipitation']),
                'rain' => $this->sumNonNullValues($values['rain']),
                'weather_code' => $this->modeValue($values['weather_code']),
                'vapour_pressure_deficit' => $this->averageNonNullValues($values['vapour_pressure_deficit']),
                'wind_gusts_10m' => $this->averageNonNullValues($values['wind_gusts_10m']),
                'wind_speed_10m' => $this->averageNonNullValues($values['wind_speed_10m']),
            ];
        }

        return $summary;
    }

    private function sumNonNullValues(array $values): ?float
    {
        $numeric = collect($values)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (float) $value)
            ->values();

        if ($numeric->isEmpty()) {
            return null;
        }

        return round((float) $numeric->sum(), 2);
    }

    private function modeValue(array $values): ?int
    {
        $counts = collect($values)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->countBy()
            ->sortDesc();

        if ($counts->isEmpty()) {
            return null;
        }

        return (int) $counts->keys()->first();
    }

    private function getHistoricalFireIncidentWritableColumns(): array
    {
        static $columns = null;

        if ($columns !== null) {
            return $columns;
        }

        $columns = Schema::hasTable('historical_fire_incidents')
            ? Schema::getColumnListing('historical_fire_incidents')
            : [];

        return $columns;
    }

    private function filterHistoricalFireIncidentPayload(array $payload, array $availableColumns): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => in_array($column, $availableColumns, true))
            ->all();
    }

    private function fetchCurrentActualIncidentRows(array $teamIds, Carbon $start, Carbon $end, ?int $selectedBarangayId = null)
    {
        if (empty($teamIds)) {
            return collect();
        }

        $query = Incident::query()
            ->whereIn('team_id', $teamIds)
            ->whereBetween('created_at', [$start, $end])
            ->whereRaw('LOWER(status) IN (?, ?)', ['closed', 'completed'])
            ->select(['incident_id', 'barangay_id', 'created_at', 'occurred_at']);

        if ($selectedBarangayId) {
            $query->where('barangay_id', $selectedBarangayId);
        }

        return $query->get()->map(function ($incident) {
            $context = $incident->context()->first([
                'temperature',
                'temperature_2m',
                'humidity',
                'relative_humidity_2m',
                'precipitation',
                'rain',
                'wind_speed',
                'wind_speed_10m',
                'wind_gusts',
                'wind_gusts_10m',
                'wind_direction',
                'wind_direction_10m',
                'weather_code',
                'weather_condition',
            ]);

            return (object) [
                'incident_date' => optional($incident->occurred_at ?: $incident->created_at)?->toDateString(),
                'incident_time' => optional($incident->occurred_at ?: $incident->created_at)?->format('H:i:s'),
                'incident_count' => 1,
                'temperature' => $context?->temperature,
                'temperature_2m' => $context?->temperature_2m,
                'humidity' => $context?->humidity,
                'relative_humidity_2m' => $context?->relative_humidity_2m,
                'precipitation' => $context?->precipitation,
                'rain' => $context?->rain,
                'wind_speed' => $context?->wind_speed,
                'wind_speed_10m' => $context?->wind_speed_10m,
                'wind_gusts' => $context?->wind_gusts,
                'wind_gusts_10m' => $context?->wind_gusts_10m,
                'wind_direction' => $context?->wind_direction,
                'wind_direction_10m' => $context?->wind_direction_10m,
                'weather_code' => $context?->weather_code,
                'weather_condition' => $context?->weather_condition,
            ];
        });
    }

    private function fetchHistoricalIncidentRows(int $stationId, Carbon $start, Carbon $end, ?int $selectedBarangayId = null)
    {
        $query = DB::table('historical_fire_incidents')
            ->whereBetween('incident_date', [$start->toDateString(), $end->toDateString()]);

        if ($selectedBarangayId) {
            $query->where('barangay_id', $selectedBarangayId);
        } else {
            $barangayIds = $this->resolveStationBarangays($stationId)
                ->pluck('barangay_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if (! empty($barangayIds)) {
                $query->whereIn('barangay_id', $barangayIds);
            }
        }

        return $query->get([
            'incident_date',
            'incident_time',
            'incident_count',
            'temperature',
            'humidity',
            'wind_speed',
            'wind_direction',
            'weather_condition',
        ]);
    }

    private function resolveWeatherFieldValue(object $row, string $preferredField, ?string $fallbackField = null): mixed
    {
        $preferred = $row->{$preferredField} ?? null;
        if ($preferred !== null && $preferred !== '') {
            return $preferred;
        }

        if ($fallbackField) {
            $fallback = $row->{$fallbackField} ?? null;
            if ($fallback !== null && $fallback !== '') {
                return $fallback;
            }
        }

        return null;
    }

    private function averageWeatherField($rows, string $preferredField, ?string $fallbackField = null): ?float
    {
        return $this->averageNonNullValues(
            collect($rows)
                ->map(fn ($row) => $this->resolveWeatherFieldValue($row, $preferredField, $fallbackField))
                ->all()
        );
    }

    private function sumIncidentRowsForDay($rows, Carbon $day): int
    {
        return (int) collect($rows)->sum(function ($row) use ($day) {
            try {
                return Carbon::parse($row->incident_date)->isSameDay($day) ? (int) ($row->incident_count ?? 1) : 0;
            } catch (\Throwable $e) {
                return 0;
            }
        });
    }

    private function sumIncidentRowsForMonthDay($rows, int $month, int $day): int
    {
        return (int) collect($rows)->sum(function ($row) use ($month, $day) {
            try {
                $date = Carbon::parse($row->incident_date);

                return $date->month === $month && $date->day === $day ? (int) ($row->incident_count ?? 1) : 0;
            } catch (\Throwable $e) {
                return 0;
            }
        });
    }

    private function buildHistoricalMonthlyAveragesByMonth(int $stationId, int $targetYear, ?int $selectedBarangayId = null): array
    {
        $comparisonYears = collect(range(1, 3))
            ->map(fn ($offset) => $targetYear - $offset)
            ->filter(fn ($year) => $year > 0)
            ->values();

        $monthlyBuckets = array_fill(1, 12, []);

        $rows = DB::table('historical_fire_incidents')
            ->when($selectedBarangayId, function ($query) use ($selectedBarangayId) {
                $query->where('barangay_id', $selectedBarangayId);
            }, function ($query) use ($stationId) {
                $barangayIds = $this->resolveStationBarangays($stationId)
                    ->pluck('barangay_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                if (empty($barangayIds)) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereIn('barangay_id', $barangayIds);
            })
            ->whereIn(DB::raw('EXTRACT(YEAR FROM incident_date)'), $comparisonYears->all())
            ->get(['incident_date', 'incident_count']);

        $grouped = $this->buildMonthlySeriesFromIncidentRows($rows);
        foreach ($grouped as $row) {
            $year = $row['period_start']->year;
            $monthNumber = (int) $row['month_number'];
            if (in_array($year, $comparisonYears->all(), true)) {
                $monthlyBuckets[$monthNumber][] = (int) $row['incident_count'];
            }
        }

        return collect(range(1, 12))
            ->map(function ($monthNumber) use ($monthlyBuckets) {
                $values = $monthlyBuckets[$monthNumber];

                return count($values) ? round(array_sum($values) / count($values), 2) : null;
            })
            ->all();
    }

    private function buildCurrentYearMonthlyActuals(array $teamIds, int $stationId, int $targetYear, ?int $selectedBarangayId = null): array
    {
        if ((int) Carbon::now()->year !== $targetYear) {
            return array_fill(0, 12, null);
        }

        if (empty($teamIds)) {
            return array_fill(0, 12, null);
        }

        $query = Incident::query()
            ->whereIn('team_id', $teamIds)
            ->whereYear('created_at', $targetYear);

        if ($selectedBarangayId) {
            $query->where('barangay_id', $selectedBarangayId);
        }

        $rows = $query->get(['created_at']);
        $monthlyCounts = array_fill(1, 12, 0);
        foreach ($rows as $row) {
            try {
                $month = Carbon::parse($row->created_at)->month;
                $monthlyCounts[$month]++;
            } catch (\Throwable $e) {
            }
        }

        return array_values($monthlyCounts);
    }

    private function buildHistoricalSeasonAverages(int $stationId, int $targetYear, ?int $selectedBarangayId = null): array
    {
        $comparisonYears = collect(range(1, 3))
            ->map(fn ($offset) => $targetYear - $offset)
            ->filter(fn ($year) => $year > 0)
            ->values();

        $seasonBuckets = ['dry' => [], 'wet' => []];

        $rows = DB::table('historical_fire_incidents')
            ->when($selectedBarangayId, function ($query) use ($selectedBarangayId) {
                $query->where('barangay_id', $selectedBarangayId);
            }, function ($query) use ($stationId) {
                $barangayIds = $this->resolveStationBarangays($stationId)
                    ->pluck('barangay_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                if (empty($barangayIds)) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereIn('barangay_id', $barangayIds);
            })
            ->whereIn(DB::raw('EXTRACT(YEAR FROM incident_date)'), $comparisonYears->all())
            ->get(['incident_date', 'incident_count']);

        foreach ($rows as $row) {
            try {
                $date = Carbon::parse($row->incident_date);
                $seasonBuckets[$this->deriveSeasonFromMonth($date->month)][] = (int) ($row->incident_count ?? 1);
            } catch (\Throwable $e) {
            }
        }

        return [
            count($seasonBuckets['dry']) ? round(array_sum($seasonBuckets['dry']) / count($seasonBuckets['dry']), 2) : null,
            count($seasonBuckets['wet']) ? round(array_sum($seasonBuckets['wet']) / count($seasonBuckets['wet']), 2) : null,
        ];
    }

    private function buildCurrentSeasonActuals(array $teamIds, int $stationId, int $targetYear, ?int $selectedBarangayId = null): array
    {
        if ((int) Carbon::now()->year !== $targetYear || empty($teamIds)) {
            return [null, null];
        }

        $query = Incident::query()
            ->whereIn('team_id', $teamIds)
            ->whereYear('created_at', $targetYear);

        if ($selectedBarangayId) {
            $query->where('barangay_id', $selectedBarangayId);
        }

        $rows = $query->get(['created_at']);
        $counts = ['dry' => 0, 'wet' => 0];
        foreach ($rows as $row) {
            try {
                $counts[$this->deriveSeasonFromMonth(Carbon::parse($row->created_at)->month)]++;
            } catch (\Throwable $e) {
            }
        }

        return [$counts['dry'], $counts['wet']];
    }

    private function buildWeekdayDistribution($rows, bool $useIncidentDateField = true): array
    {
        $counts = array_fill(0, 7, 0);
        foreach ($rows as $row) {
            try {
                $date = $useIncidentDateField
                    ? Carbon::parse($row->incident_date)
                    : Carbon::parse($row->incident_date ?? $row->created_at);
                $counts[$date->dayOfWeek] += (int) ($row->incident_count ?? 1);
            } catch (\Throwable $e) {
            }
        }

        return array_values($counts);
    }

    private function buildHourlyDistribution($rows, bool $useIncidentTime = true): array
    {
        $counts = array_fill(0, 24, 0);
        foreach ($rows as $row) {
            try {
                if ($useIncidentTime && ! empty($row->incident_time)) {
                    $hour = (int) explode(':', (string) $row->incident_time)[0];
                } else {
                    $source = $row->incident_date ?? $row->created_at ?? null;
                    $hour = $source ? (int) Carbon::parse($source)->format('H') : null;
                }

                if ($hour !== null && $hour >= 0 && $hour <= 23) {
                    $counts[$hour] += (int) ($row->incident_count ?? 1);
                }
            } catch (\Throwable $e) {
            }
        }

        return array_values($counts);
    }

    private function attachBarangayHistoricalBaselines(int $stationId, array $barangayPredictions, array $predictionContext): array
    {
        $targetMonthStart = $this->resolvePredictionTargetMonthStart($predictionContext);
        $comparisonYears = $this->resolveHistoricalComparisonYears($stationId, $targetMonthStart);

        if (empty($comparisonYears)) {
            return $barangayPredictions;
        }

        return collect($barangayPredictions)->map(function (array $prediction) use ($targetMonthStart, $comparisonYears) {
            $barangayId = (int) ($prediction['barangay_id'] ?? 0);
            if (! $barangayId) {
                return $prediction;
            }

            $historicalRows = DB::table('historical_fire_incidents')
                ->where('barangay_id', $barangayId)
                ->whereMonth('incident_date', $targetMonthStart->month)
                ->whereIn(DB::raw('EXTRACT(YEAR FROM incident_date)'), $comparisonYears)
                ->get(['incident_date', 'incident_count']);

            $yearlyTotals = [];
            foreach ($comparisonYears as $year) {
                $yearlyTotals[$year] = (int) $historicalRows->filter(function ($row) use ($year) {
                    try {
                        return Carbon::parse($row->incident_date)->year === $year;
                    } catch (\Throwable $e) {
                        return false;
                    }
                })->sum(fn ($row) => (int) ($row->incident_count ?? 1));
            }

            $baseline = count($yearlyTotals) ? round(array_sum($yearlyTotals) / count($yearlyTotals), 2) : null;
            $prediction['historical_baseline_incidents'] = $baseline;
            $prediction['historical_comparison_years'] = array_values($comparisonYears);
            $prediction['historical_yearly_totals'] = $yearlyTotals;

            return $prediction;
        })->all();
    }

    private function buildMlTrendAnalysisSeries(int $stationId, ?int $selectedBarangayId = null): array
    {
        $recentAnalyses = Analysis::query()
            ->where('station_id', $stationId)
            ->latest('created_at')
            ->limit(12)
            ->get();

        $savedPoints = $recentAnalyses
            ->sortBy('created_at')
            ->values()
            ->map(function (Analysis $analysis) use ($selectedBarangayId) {
                $point = $this->buildScopedMlTrendPoint($analysis, $selectedBarangayId);
                if (! $point) {
                    return null;
                }
                $point['date'] = optional($analysis->created_at)?->toDateString();

                return $point;
            })
            ->filter()
            ->keyBy('date');

        $officerIds = StationOfficer::query()->where('station_id', $stationId)->pluck('station_officer_id')->all();
        $teamIds = Team::query()->where(function ($query) use ($officerIds) {
            $query->whereIn('commander_driver', $officerIds)->orWhereIn('pumper', $officerIds)->orWhereIn('team_leader', $officerIds)
                ->orWhereIn('firefighter_officer_1', $officerIds)->orWhereIn('firefighter_officer_2', $officerIds)
                ->orWhereIn('firefighter_officer_3', $officerIds)->orWhereIn('rescue_radio', $officerIds);
        })->pluck('team_id')->all();
        $actuals = Incident::query()->whereRaw('LOWER(status) IN (?, ?)', ['closed', 'completed'])
            ->where(function ($query) use ($stationId, $teamIds) {
                $query->where('station_id', $stationId)->orWhereIn('team_id', $teamIds);
            })
            ->when($selectedBarangayId, fn ($query) => $query->where('barangay_id', $selectedBarangayId))
            ->get(['created_at', 'occurred_at']);
        $latestSavedPoint = $savedPoints->sortBy('date')->last();

        $monthStart = Carbon::today()->startOfMonth();
        $daysFromMonthStart = $monthStart->diffInDays(Carbon::today());
        $series = collect(range(0, $daysFromMonthStart))->map(function (int $dayOffset) use ($actuals, $savedPoints, $latestSavedPoint, $monthStart, $daysFromMonthStart) {
            $date = $monthStart->copy()->addDays($dayOffset);
            $saved = $savedPoints->get($date->toDateString());
            $displayPoint = $saved ?: ($dayOffset === $daysFromMonthStart ? $latestSavedPoint : null);

            return [
                'label' => $date->format('M d'),
                'incident_count' => $actuals->filter(fn ($incident) => optional($incident->occurred_at ?: $incident->created_at)?->isSameDay($date))->count(),
                'predicted_fire_incidents' => $displayPoint['predicted_fire_incidents'] ?? null,
                'confidence' => $displayPoint['confidence'] ?? null,
                'risk_level' => $displayPoint['risk_level'] ?? 'No saved forecast',
            ];
        })->values()->all();

        return [
            'series' => $series,
            'month_over_month' => $this->buildMlMonthOverMonthComparison(
                $recentAnalyses,
                $actuals,
                $selectedBarangayId
            ),
            'latest_risk' => ! empty($series) ? ($series[count($series) - 1]['risk_level'] ?? 'No data') : 'No data',
            'latest_confidence' => ! empty($series) ? (int) ($series[count($series) - 1]['confidence'] ?? 0) : 0,
            'run_count' => $savedPoints->count(),
            'missing' => [
                'series' => empty($series),
            ],
        ];
    }

    private function buildMlMonthOverMonthComparison($analyses, $actuals, ?int $selectedBarangayId = null): array
    {
        $currentMonth = Carbon::today()->startOfMonth();
        $lastMonth = $currentMonth->copy()->subMonth();

        $forecastByMonth = collect($analyses)
            ->sortBy('created_at')
            ->map(function (Analysis $analysis) use ($selectedBarangayId) {
                $analysisData = is_array($analysis->analysis_data) ? $analysis->analysis_data : [];
                $rawData = $analysisData['raw_data'] ?? [];
                $targetLabel = (string) data_get($rawData, 'prediction_context.target_month_label', '');

                try {
                    $targetMonth = $targetLabel !== ''
                        ? Carbon::createFromFormat('F Y', $targetLabel)->startOfMonth()
                        : null;
                } catch (\Throwable $e) {
                    $targetMonth = null;
                }

                $point = $this->buildScopedMlTrendPoint($analysis, $selectedBarangayId);

                return $targetMonth && $point
                    ? ['month' => $targetMonth->format('Y-m'), 'point' => $point]
                    : null;
            })
            ->filter()
            ->keyBy('month');

        $series = collect([$lastMonth, $currentMonth])
            ->map(function (Carbon $month) use ($actuals, $forecastByMonth) {
                $forecast = data_get($forecastByMonth->get($month->format('Y-m')), 'point', []);

                return [
                    'label' => $month->format('F Y'),
                    'actual_incidents' => $actuals
                        ->filter(fn ($incident) => optional($incident->occurred_at ?: $incident->created_at)?->isSameMonth($month))
                        ->count(),
                    'predicted_fire_incidents' => array_key_exists('predicted_fire_incidents', $forecast)
                        ? $forecast['predicted_fire_incidents']
                        : null,
                    'confidence' => array_key_exists('confidence', $forecast) ? $forecast['confidence'] : null,
                    'risk_level' => $forecast['risk_level'] ?? 'No saved forecast',
                ];
            })
            ->values()
            ->all();

        return [
            'series' => $series,
            'missing' => [
                'forecast' => collect($series)->every(fn ($item) => $item['predicted_fire_incidents'] === null),
                'actual_incidents' => collect($series)->sum('actual_incidents') === 0,
            ],
        ];
    }

    private function buildScopedMlTrendPoint(Analysis $analysis, ?int $selectedBarangayId = null): ?array
    {
        $analysisData = is_array($analysis->analysis_data) ? $analysis->analysis_data : [];
        $rawData = $analysisData['raw_data'] ?? [];
        $predictionContext = is_array($rawData['prediction_context'] ?? null) ? $rawData['prediction_context'] : [];
        $scopedPrediction = $this->resolveScopedPredictionFromAnalysis($analysis, $selectedBarangayId);

        if ($selectedBarangayId && $scopedPrediction === null) {
            return null;
        }

        $confidence = is_numeric($analysis->confidence_data)
            ? (int) $analysis->confidence_data
            : (is_numeric($analysis->confidence_score ?? null) ? (int) $analysis->confidence_score : 0);

        $predictedIncidentCount = $scopedPrediction['predicted_incident_count']
            ?? $scopedPrediction['predicted_fire_incidents']
            ?? data_get($analysis->predictions, 'predicted_incident_count')
            ?? data_get($analysis->predictions, 'predicted_fire_incidents')
            ?? data_get($rawData, 'model_prediction.output.predicted_incident_count')
            ?? data_get($rawData, 'model_prediction.output.predicted_fire_incidents')
            ?? null;

        $riskLevel = $scopedPrediction['risk_level']
            ?? $analysis->risk_level
            ?? 'Unknown';

        $label = (string) (
            $predictionContext['target_month_label']
            ?? $predictionContext['target_period_label']
            ?? optional($analysis->created_at)?->format('M d')
            ?? 'Saved run'
        );

        return [
            'label' => $label,
            'incident_count' => $this->resolveScopedActualIncidentCountForAnalysis($analysis, $selectedBarangayId, $scopedPrediction),
            'predicted_fire_incidents' => $predictedIncidentCount !== null ? (int) round((float) $predictedIncidentCount) : 0,
            'confidence' => $confidence,
            'risk_level' => (string) $riskLevel,
        ];
    }

    private function resolveScopedPredictionFromAnalysis(Analysis $analysis, ?int $selectedBarangayId = null): ?array
    {
        $analysisData = is_array($analysis->analysis_data) ? $analysis->analysis_data : [];
        $rawData = $analysisData['raw_data'] ?? [];

        if (! $selectedBarangayId) {
            return is_array($rawData['selected_barangay_prediction'] ?? null)
                ? $rawData['selected_barangay_prediction']
                : null;
        }

        $selectedPrediction = $rawData['selected_barangay_prediction'] ?? null;
        if (is_array($selectedPrediction) && (int) ($selectedPrediction['barangay_id'] ?? 0) === (int) $selectedBarangayId) {
            return $selectedPrediction;
        }

        $barangayPrediction = collect($rawData['barangay_predictions'] ?? [])
            ->first(fn ($item) => (int) ($item['barangay_id'] ?? 0) === (int) $selectedBarangayId);

        return is_array($barangayPrediction) ? $barangayPrediction : null;
    }

    private function resolveScopedActualIncidentCountForAnalysis(
        Analysis $analysis,
        ?int $selectedBarangayId = null,
        ?array $scopedPrediction = null
    ): ?int {
        $analysisData = is_array($analysis->analysis_data) ? $analysis->analysis_data : [];
        $rawData = $analysisData['raw_data'] ?? [];
        $predictionContext = is_array($rawData['prediction_context'] ?? null) ? $rawData['prediction_context'] : [];
        $targetMonthLabel = (string) ($predictionContext['target_month_label'] ?? '');
        if ($targetMonthLabel === '') {
            return null;
        }

        try {
            $targetMonth = Carbon::createFromFormat('F Y', $targetMonthLabel)->startOfMonth();
        } catch (\Throwable $e) {
            return null;
        }

        if ($targetMonth->isFuture()) {
            return null;
        }

        $stationId = (int) ($analysis->station_id ?? 0);
        $teamIds = $stationId > 0
            ? Team::query()->where('station_id', $stationId)->pluck('team_id')->filter()->map(fn ($id) => (int) $id)->all()
            : [];

        $actualQuery = Incident::query()
            ->whereBetween('created_at', [$targetMonth->copy()->startOfMonth(), $targetMonth->copy()->endOfMonth()])
            ->whereRaw('LOWER(status) IN (?, ?)', ['closed', 'completed']);

        if (Schema::hasColumn('incidents', 'incident_type_id')) {
            $actualQuery->where('incident_type_id', 1);
        }

        if ($stationId > 0 && Schema::hasColumn('incidents', 'station_id')) {
            $actualQuery->where(function ($query) use ($stationId, $teamIds) {
                $query->where('station_id', $stationId);
                if (! empty($teamIds)) {
                    $query->orWhereIn('team_id', $teamIds);
                }
            });
        } elseif (! empty($teamIds)) {
            $actualQuery->whereIn('team_id', $teamIds);
        } else {
            return $selectedBarangayId ? null : (isset($analysis->incident_count) ? (int) $analysis->incident_count : null);
        }

        if ($selectedBarangayId) {
            if (! Schema::hasColumn('incidents', 'barangay_id')) {
                return null;
            }
            $actualQuery->where('barangay_id', $selectedBarangayId);
        }

        return (int) $actualQuery->count();
    }

    private function buildOperationalPredictionEvaluationSeries(int $stationId, ?int $selectedBarangayId = null): ?array
    {
        $rows = Analysis::query()
            ->where('station_id', $stationId)
            ->latest('created_at')
            ->limit(18)
            ->get()
            ->map(function (Analysis $analysis) use ($selectedBarangayId) {
                $analysisData = is_array($analysis->analysis_data) ? $analysis->analysis_data : [];
                $rawData = $analysisData['raw_data'] ?? [];
                $predictionContext = is_array($rawData['prediction_context'] ?? null) ? $rawData['prediction_context'] : [];
                $scopedPrediction = $this->resolveScopedPredictionFromAnalysis($analysis, $selectedBarangayId);

                if ($selectedBarangayId && $scopedPrediction === null) {
                    return null;
                }

                $predicted = $scopedPrediction['predicted_incident_count']
                    ?? $scopedPrediction['predicted_fire_incidents']
                    ?? data_get($analysis->predictions, 'predicted_incident_count')
                    ?? data_get($analysis->predictions, 'predicted_fire_incidents')
                    ?? data_get($rawData, 'model_prediction.output.predicted_incident_count')
                    ?? data_get($rawData, 'model_prediction.output.predicted_fire_incidents')
                    ?? null;

                $actual = $this->resolveScopedActualIncidentCountForAnalysis($analysis, $selectedBarangayId, $scopedPrediction);
                if ($predicted === null || $actual === null) {
                    return null;
                }

                $label = (string) (
                    $predictionContext['target_month_label']
                    ?? $predictionContext['target_period_label']
                    ?? optional($analysis->created_at)?->format('M d, Y')
                    ?? 'Saved run'
                );

                return [
                    'label' => $label,
                    'actual' => (float) $actual,
                    'predicted' => (float) $predicted,
                    'sort_key' => optional($analysis->created_at)?->timestamp ?? 0,
                ];
            })
            ->filter()
            ->sortBy('sort_key')
            ->values()
            ->take(12)
            ->all();

        if (count($rows) < 2) {
            return null;
        }

        $labels = array_map(fn ($row) => (string) $row['label'], $rows);
        $actual = array_map(fn ($row) => round((float) $row['actual'], 2), $rows);
        $predicted = array_map(fn ($row) => round((float) $row['predicted'], 2), $rows);
        $residuals = array_map(
            fn ($row) => round((float) $row['predicted'] - (float) $row['actual'], 2),
            $rows
        );

        return [
            'actual_vs_predicted' => [
                'target' => 'saved_forecast_runs',
                'target_column' => 'incident_count',
                'metric_label' => 'Incident count',
                'labels' => $labels,
                'actual' => $actual,
                'predicted' => $predicted,
                'x_axis_label' => 'Saved forecast period',
                'source' => 'saved_forecast_runs',
            ],
            'residual_pattern' => [
                'target' => 'saved_forecast_runs',
                'target_column' => 'incident_count',
                'metric_label' => 'Prediction error (predicted - actual incidents)',
                'labels' => $labels,
                'residuals' => $residuals,
                'reference_line' => 0,
                'x_axis_label' => 'Saved forecast period',
                'source' => 'saved_forecast_runs',
            ],
        ];
    }

    private function fetchHistoricalMonthlySeries(int $stationId, ?int $selectedBarangayId = null)
    {
        return $this->buildMonthlySeriesFromIncidentRows(
            DB::table('historical_fire_incidents')
                ->when($selectedBarangayId, function ($query) use ($selectedBarangayId) {
                    $query->where('barangay_id', $selectedBarangayId);
                }, function ($query) use ($stationId) {
                    $barangayIds = $this->resolveStationBarangays($stationId)
                        ->pluck('barangay_id')
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->all();

                    if (empty($barangayIds)) {
                        $query->whereRaw('1 = 0');

                        return;
                    }

                    $query->whereIn('barangay_id', $barangayIds);
                })
                ->whereDate('incident_date', '>=', Carbon::now()->subMonths(36)->startOfMonth())
                ->orderBy('incident_date')
                ->get(['incident_date', 'incident_count'])
        );
    }

    private function fetchHistoricalWeatherSeries(int $stationId, ?int $selectedBarangayId = null)
    {
        $query = DB::table('historical_fire_incidents')
            ->whereDate('incident_date', '>=', Carbon::now()->subMonths(24)->startOfMonth())
            ->orderBy('incident_date');

        if ($selectedBarangayId) {
            $query->where('barangay_id', $selectedBarangayId);
        } else {
            $barangayIds = $this->resolveStationBarangays($stationId)
                ->pluck('barangay_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if (empty($barangayIds)) {
                return collect();
            }

            $query->whereIn('barangay_id', $barangayIds);
        }

        $rows = $query->get(['incident_date', 'incident_count', 'temperature', 'humidity', 'wind_speed']);

        return $rows
            ->groupBy(function ($row) {
                try {
                    return Carbon::parse($row->incident_date)->format('Y-m');
                } catch (\Throwable $e) {
                    return 'unknown';
                }
            })
            ->reject(fn ($rows, $key) => $key === 'unknown')
            ->map(function ($periodRows, $periodKey) {
                [$year, $month] = explode('-', $periodKey);
                $periodStart = Carbon::createFromDate((int) $year, (int) $month, 1)->startOfMonth();

                return [
                    'period_start' => $periodStart,
                    'label' => $periodStart->format('M Y'),
                    'incident_count' => (int) $periodRows->sum(fn ($row) => (int) ($row->incident_count ?? 1)),
                    'temperature' => $this->averageNonNullValues($periodRows->pluck('temperature')->all()),
                    'humidity' => $this->averageNonNullValues($periodRows->pluck('humidity')->all()),
                    'wind_speed' => $this->averageNonNullValues($periodRows->pluck('wind_speed')->all()),
                ];
            })
            ->sortBy(fn (array $row) => $row['period_start']->timestamp)
            ->values();
    }

    private function buildMonthlySeriesFromIncidentRows($rows)
    {
        return collect($rows)
            ->groupBy(function ($row) {
                try {
                    return Carbon::parse($row->incident_date)->format('Y-m');
                } catch (\Throwable $e) {
                    return 'unknown';
                }
            })
            ->reject(fn ($rows, $key) => $key === 'unknown')
            ->map(function ($periodRows, $periodKey) {
                [$year, $month] = explode('-', $periodKey);
                $periodStart = Carbon::createFromDate((int) $year, (int) $month, 1)->startOfMonth();
                $monthNumber = (int) $month;

                return [
                    'period_start' => $periodStart,
                    'label' => $periodStart->format('M Y'),
                    'month_number' => $monthNumber,
                    'incident_count' => (int) $periodRows->sum(fn ($row) => (int) ($row->incident_count ?? 1)),
                    'season' => $this->deriveSeasonFromMonth($monthNumber),
                    'temperature' => null,
                    'humidity' => null,
                    'wind_speed' => null,
                ];
            })
            ->sortBy(fn (array $row) => $row['period_start']->timestamp)
            ->values();
    }

    private function resolvePredictionTargetMonthStart(array $predictionContext): Carbon
    {
        $label = $predictionContext['target_month_label'] ?? null;
        if (is_string($label) && trim($label) !== '') {
            try {
                return Carbon::createFromFormat('F Y', $label)->startOfMonth();
            } catch (\Throwable $e) {
            }
        }

        return Carbon::now()->startOfMonth();
    }

    private function monthNameToNumber(?string $monthName): ?int
    {
        if (! $monthName) {
            return null;
        }

        $index = [
            'january' => 1,
            'february' => 2,
            'march' => 3,
            'april' => 4,
            'may' => 5,
            'june' => 6,
            'july' => 7,
            'august' => 8,
            'september' => 9,
            'october' => 10,
            'november' => 11,
            'december' => 12,
        ];

        return $index[Str::lower(trim($monthName))] ?? null;
    }

    private function deriveSeasonFromMonth(int $monthNumber): string
    {
        return in_array($monthNumber, [11, 12, 1, 2, 3, 4], true) ? 'dry' : 'wet';
    }

    private function averageNonNullValues(array $values): ?float
    {
        $numbers = collect($values)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (float) $value)
            ->values();

        if ($numbers->isEmpty()) {
            return null;
        }

        return round($numbers->avg(), 2);
    }

    private function normalizeHistoricalWindowLimit($historicalWindow): ?int
    {
        $value = Str::lower(trim((string) $historicalWindow));

        if ($value === 'all' || $value === 'all_available') {
            return null;
        }

        if (is_numeric($value)) {
            $limit = (int) $value;

            return $limit > 0 ? $limit : 3;
        }

        return 3;
    }

    private function formatHistoricalWindowLabel($historicalWindow, int $yearCount): string
    {
        $value = Str::lower(trim((string) $historicalWindow));

        if ($value === 'all' || $value === 'all_available') {
            return $yearCount > 0
                ? "All available history ({$yearCount} year".($yearCount === 1 ? '' : 's').')'
                : 'All available history';
        }

        $limit = $this->normalizeHistoricalWindowLimit($historicalWindow) ?? $yearCount;

        return "{$limit}-year window";
    }

    private function countFireIncidents($incidents): int
    {
        if (! Schema::hasColumn('incidents', 'incident_type_id')) {
            return $incidents->count();
        }

        return $incidents->where('incident_type_id', 1)->count();
    }

    private function fetchIncidentsForPredictionWindow(int $stationId, array $teamIds, Carbon $start, Carbon $end)
    {
        if ($stationId <= 0 && empty($teamIds)) {
            return collect();
        }

        $incidentDateExpression = Schema::hasColumn('incidents', 'occurred_at')
            ? 'COALESCE(occurred_at, created_at)'
            : 'created_at';

        return Incident::query()
            ->where(function ($query) use ($stationId, $teamIds) {
                if ($stationId > 0 && Schema::hasColumn('incidents', 'station_id')) {
                    $query->where('station_id', $stationId);
                }
                if (! empty($teamIds)) {
                    $method = $stationId > 0 && Schema::hasColumn('incidents', 'station_id') ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('team_id', $teamIds);
                }
            })
            ->whereRaw("{$incidentDateExpression} BETWEEN ? AND ?", [$start, $end])
            ->whereRaw('LOWER(status) IN (?, ?)', ['closed', 'completed'])
            ->get();
    }
    // public function exploreRecommendation(Request $request)
    // {
    //     try {
    //         $recommendationTitle = $request->input('recommendation_title');
    //         $recommendationDescription = $request->input('recommendation_description');
    //         $priority = $request->input('priority', 'Medium');

    //         // Log that we're starting
    //         Log::info('=== EXPLORE RECOMMENDATION START ===');
    //         Log::info('Title: ' . $recommendationTitle);

    //         // Build prompt for AI
    //         $prompt = "You are a fire safety implementation expert. Provide a detailed implementation plan for this recommendation.\n\n";
    //         $prompt .= "RECOMMENDATION: {$recommendationTitle}\n";
    //         $prompt .= "DESCRIPTION: {$recommendationDescription}\n";
    //         $prompt .= "PRIORITY: {$priority}\n\n";
    //         $prompt .= "Provide EXACTLY this format:\n\n";
    //         $prompt .= "STEPS:\n";
    //         $prompt .= "1. [First actionable step]\n";
    //         $prompt .= "2. [Second actionable step]\n";
    //         $prompt .= "3. [Third actionable step]\n";
    //         $prompt .= "4. [Fourth actionable step]\n";
    //         $prompt .= "5. [Fifth actionable step]\n\n";
    //         $prompt .= "RESOURCES NEEDED:\n";
    //         $prompt .= "- [Resource 1]\n";
    //         $prompt .= "- [Resource 2]\n";
    //         $prompt .= "- [Resource 3]\n\n";
    //         $prompt .= "TIMELINE: [Estimated timeline]";

    //         // Log that we're about to call AI
    //         Log::info('Calling AI API with prompt length: ' . strlen($prompt));

    //         // Call AI API
    //         $response = Http::withHeaders([
    //             'Authorization' => 'Bearer ' . $this->apiKey,
    //             'Content-Type' => 'application/json',
    //         ])->timeout(60)->post('https://openrouter.ai/api/v1/chat/completions', [
    //             'model' => 'openai/gpt-3.5-turbo',
    //             'messages' => [
    //                 ['role' => 'system', 'content' => 'You are a fire safety implementation expert. Provide detailed, actionable implementation plans.'],
    //                 ['role' => 'user', 'content' => $prompt]
    //             ],
    //             'temperature' => 0.5,
    //             'max_tokens' => 800
    //         ]);

    //         // Log the response status
    //         Log::info('AI API Response Status: ' . $response->status());

    //         if ($response->successful()) {
    //             $json = $response->json();
    //             $content = $json['choices'][0]['message']['content'] ?? '';

    //             // Log that we got AI response
    //             Log::info('AI Response received, length: ' . strlen($content));
    //             Log::info('AI Response preview: ' . substr($content, 0, 200));

    //             $parsedData = $this->parseExploreResponse($content);

    //             // Log if we're using AI or fallback
    //             Log::info('Returning AI-generated response');

    //             return response()->json([
    //                 'success' => true,
    //                 'data' => $parsedData,
    //                 'source' => 'ai_api'  // Add this to indicate source
    //             ]);
    //         } else {
    //             Log::error('AI API returned error: ' . $response->status());
    //             throw new \Exception('AI API call failed: ' . $response->status());
    //         }

    //     } catch (\Exception $e) {
    //         Log::error('AI API failed, using fallback: ' . $e->getMessage());

    //         // ONLY HERE does it use fallback (controller-generated)
    //         return response()->json([
    //             'success' => true,
    //             'data' => $this->getFallbackExploreData(
    //                 $request->input('recommendation_title', 'this recommendation'),
    //                 $request->input('priority', 'Medium')
    //             ),
    //             'source' => 'fallback',  // Indicates fallback was used
    //             'note' => 'AI service temporarily unavailable, using standard recommendations'
    //         ]);
    //     }
    // }
    /**
     * Parse the AI response into structured data
     */
    private function parseExploreResponse($content, ?array $contextualFallback = null)
    {
        $steps = [];
        $resources = [];
        $timeline = 'Implementation timeline varies based on resources and complexity';
        $how = null;
        $who = null;
        $when = null;

        $lines = explode("\n", $content);
        $currentSection = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('/^how\s*:/i', $line)) {
                $currentSection = 'how';
                $value = trim((string) preg_replace('/^how\s*:/i', '', $line));
                if ($value !== '') {
                    $how = $value;
                }

                continue;
            } elseif (preg_match('/^who\s*:/i', $line)) {
                $currentSection = 'who';
                $value = trim((string) preg_replace('/^who\s*:/i', '', $line));
                if ($value !== '') {
                    $who = $value;
                }

                continue;
            } elseif (preg_match('/^when\s*:/i', $line)) {
                $currentSection = 'when';
                $value = trim((string) preg_replace('/^when\s*:/i', '', $line));
                if ($value !== '') {
                    $when = $value;
                }

                continue;
            }

            // Detect sections
            if (preg_match('/steps?/i', $line) && strpos($line, '1.') !== false) {
                $currentSection = 'steps';
            } elseif (preg_match('/resources?/i', $line) && (strpos($line, '•') !== false || strpos($line, '-') !== false)) {
                $currentSection = 'resources';
            } elseif (preg_match('/timeline/i', $line)) {
                $currentSection = 'timeline';

                continue;
            }

            if ($currentSection === 'how' && $how === null && $line !== '') {
                $how = $line;

                continue;
            } elseif ($currentSection === 'who' && $who === null && $line !== '') {
                $who = $line;

                continue;
            } elseif ($currentSection === 'when' && $when === null && $line !== '') {
                $when = $line;

                continue;
            }

            // Extract numbered steps
            if (preg_match('/^\d+\.\s*(.+)/', $line, $matches)) {
                $steps[] = $matches[1];
            }
            // Extract bullet points for resources
            elseif (preg_match('/^[•\-*]\s*(.+)/', $line, $matches)) {
                if ($currentSection === 'resources' || strpos($line, '•') !== false) {
                    $resources[] = $matches[1];
                } elseif (empty($steps)) {
                    $steps[] = $matches[1];
                }
            }
            // Extract timeline
            elseif ($currentSection === 'timeline' && ! empty($line) && strlen($line) > 10 && strpos($line, '•') === false && strpos($line, '1.') === false) {
                $timeline = $line;
            }
        }

        // Ensure we have at least some steps
        if (empty($steps)) {
            $steps = $contextualFallback['steps'] ?? [
                'Conduct a thorough assessment of current practices',
                'Develop a detailed action plan with specific timelines and responsibilities',
                'Allocate necessary resources and personnel for implementation',
                'Train staff on new procedures',
                'Implement the recommendation in phases',
                'Monitor progress and adjust strategies as needed',
                'Review and document outcomes for future reference',
            ];
        }

        if (empty($resources)) {
            $resources = $contextualFallback['resources_needed'] ?? [
                'Dedicated personnel for implementation',
                'Training materials and sessions',
                'Monitoring and evaluation tools',
                'Budget allocation for necessary equipment',
            ];
        }

        if (
            $timeline === 'Implementation timeline varies based on resources and complexity'
            && ! empty($contextualFallback['estimated_timeline'])
        ) {
            $timeline = $contextualFallback['estimated_timeline'];
        }

        if ($how === null && ! empty($contextualFallback['how'])) {
            $how = $contextualFallback['how'];
        }

        if ($who === null && ! empty($contextualFallback['who'])) {
            $who = $contextualFallback['who'];
        }

        if ($when === null) {
            $when = $contextualFallback['when'] ?? $timeline;
        }

        return [
            'steps' => array_slice($steps, 0, 7),
            'resources_needed' => array_slice($resources, 0, 5),
            'estimated_timeline' => $timeline,
            'how' => $how,
            'who' => $who,
            'when' => $when,
        ];
    }

    /**
     * Get fallback data when AI is unavailable
     */
    private function getFallbackExploreData($recommendationTitle, $priority, ?string $recommendationDescription = null, array $contextPayload = [])
    {
        $recommendationTitle = $this->normalizeRecommendationTitle($recommendationTitle);
        $priority = $priority ?: 'Medium';
        $priorityLevel = strtolower((string) $priority);
        $urgencyText = $priorityLevel === 'critical' ? 'Immediate' : ($priorityLevel === 'high' ? 'Urgent' : 'Standard');
        $profile = $this->buildRecommendationActionProfile($recommendationTitle, $recommendationDescription, $contextPayload);

        return [
            'steps' => $profile['steps'],
            'resources_needed' => $profile['resources_needed'],
            'estimated_timeline' => $profile['estimated_timeline'] ?? (
                $priorityLevel === 'critical'
                    ? '1-2 weeks for initial implementation, with daily monitoring'
                    : ($priorityLevel === 'high'
                        ? '2-4 weeks for full implementation, with weekly reviews'
                        : '1-3 months for complete implementation, with monthly progress reviews')
            ),
            'how' => $profile['how'] ?? ("Use a field-based implementation workflow focused on {$recommendationTitle}, then validate the correction on site before closing the action."),
            'who' => $profile['who'] ?? 'Assigned station lead, field responders, and barangay coordination personnel',
            'when' => $profile['when'] ?? ($priorityLevel === 'critical'
                ? 'Start within 24 hours and monitor daily until stabilized'
                : ($priorityLevel === 'high'
                    ? 'Start this week and complete follow-up within 2-4 weeks'
                    : 'Schedule within the current operational cycle and review weekly')),
            'explanation' => $profile['explanation'] ?? null,
            'context_summary' => $profile['context_summary'] ?? null,
        ];
    }

    private function buildRecommendationActionProfile(string $recommendationTitle, ?string $recommendationDescription = null, array $contextPayload = []): array
    {
        $combined = trim($recommendationTitle.'. '.($recommendationDescription ?? ''));
        $normalized = Str::lower($combined);
        $priorityLevel = Str::lower((string) ($contextPayload['recommendation']['priority'] ?? 'medium'));
        $urgencyText = $priorityLevel === 'critical' ? 'Immediate' : ($priorityLevel === 'high' ? 'Urgent' : 'Standard');
        $barangay = $this->extractContextBarangayName($contextPayload, $combined);
        $place = $this->extractContextPlaceLabel($contextPayload, $combined, $barangay);
        $analysisSummary = $contextPayload['prediction_summary'] ?? null;
        $locationPhrase = $place ?: ($barangay ?: 'the priority area');
        $resources = [
            'Assigned inspection or response lead',
            'Photo and field validation checklist',
            'Monitoring log for follow-up actions',
        ];
        $timeline = '2-4 weeks with weekly field validation and command review';
        $explanation = "Fallback implementation plan localized for {$locationPhrase}.";

        if (Str::contains($normalized, ['wiring', 'electrical', 'octopus', 'exposed wire', 'overloaded outlet', 'improper splicing'])) {
            return [
                'steps' => [
                    "Verify the exact electrical hazard reported in {$locationPhrase}, including unsafe taps, exposed conductors, or overloaded outlets mentioned in the recommendation.",
                    "Inspect nearby homes or structures around {$locationPhrase} for similar wiring patterns and document which connections need immediate correction.",
                    'Coordinate with the barangay and property occupants to remove temporary illegal connections, replace damaged extension lines, and isolate hazardous circuits.',
                    "Issue a correction notice for all unsafe electrical setups found in {$locationPhrase} and set a short compliance deadline for rewiring or repair.",
                    "Revisit {$locationPhrase} after corrective work and confirm that hazardous electrical conditions were removed before closing the action.",
                ],
                'resources_needed' => array_merge($resources, [
                    'Licensed electrician or electrical safety partner',
                    'Insulated inspection tools and PPE',
                ]),
                'estimated_timeline' => $priorityLevel === 'critical'
                    ? '24-72 hours for high-risk electrical correction, then 1 week validation'
                    : '1-2 weeks for inspection, correction, and validation',
                'explanation' => $explanation,
                'context_summary' => $analysisSummary,
            ];
        }

        if (Str::contains($normalized, ['lpg', 'gas', 'leaking gas', 'cooking', 'stove', 'regulator', 'hose'])) {
            return [
                'steps' => [
                    "Inspect the reported LPG or cooking setup in {$locationPhrase} and verify hose condition, regulator fit, tank placement, and ventilation around the cooking area.",
                    'Remove or isolate any leaking, expired, or improperly positioned LPG tanks found during the site visit.',
                    "Require occupants or operators in {$locationPhrase} to correct unsafe cooking layouts, especially tanks near heat sources or enclosed storage.",
                    "Conduct a short barangay safety briefing for households or vendors around {$locationPhrase} on safe LPG handling and unattended cooking prevention.",
                    'Reinspect the site and confirm that the gas-related fire hazard has been corrected before marking the action complete.',
                ],
                'resources_needed' => array_merge($resources, [
                    'LPG leak detection or soap-test materials',
                    'Replacement regulators or hoses coordination list',
                ]),
                'estimated_timeline' => '3-10 days for correction and reinspection',
                'explanation' => $explanation,
                'context_summary' => $analysisSummary,
            ];
        }

        if (Str::contains($normalized, ['hydrant', 'water access', 'response capability'])) {
            return [
                'steps' => [
                    "Confirm the exact hydrant or water-access issue affecting {$locationPhrase}, including blockage, inoperability, missing fittings, or access obstruction.",
                    "Map the nearest alternative water source and measure how the current issue can delay suppression response in {$locationPhrase}.",
                    'Coordinate with the barangay and utility or maintenance personnel to clear obstructions and restore usable hydrant access immediately.',
                    'Test hydrant operability after corrective work and document pressure, accessibility, and hose connection readiness.',
                    "Update responders assigned to {$locationPhrase} with the corrected hydrant status and any temporary alternate water source plan.",
                ],
                'resources_needed' => array_merge($resources, [
                    'Hydrant testing tools or maintenance crew',
                    'Water source access map for the affected area',
                ]),
                'estimated_timeline' => '1-2 weeks for restoration and operability confirmation',
                'explanation' => $explanation,
                'context_summary' => $analysisSummary,
            ];
        }

        if (Str::contains($normalized, ['route', 'response time', 'access road', 'narrow road', 'response protocol'])) {
            return [
                'steps' => [
                    "Review the actual responder approach path toward {$locationPhrase} and identify choke points, narrow access, turning limits, or delays affecting dispatch.",
                    'Run a route validation with station personnel using the current road condition and compare it with the forecasted response concern in the recommendation.',
                    'Coordinate with barangay officials to clear obstructions, regulate roadside parking, or establish a temporary emergency access lane where needed.',
                    "Update dispatch notes, route instructions, and station response guidance for incidents occurring around {$locationPhrase}.",
                    'Conduct a follow-up travel-time check to confirm whether the route adjustment reduced expected delay.',
                ],
                'resources_needed' => array_merge($resources, [
                    'Updated route map and dispatch notes',
                    'Coordination with barangay traffic or road clearing personnel',
                ]),
                'estimated_timeline' => '1-3 weeks for route validation, clearing, and retesting',
                'explanation' => $explanation,
                'context_summary' => $analysisSummary,
            ];
        }

        if (Str::contains($normalized, ['awareness', 'prevention briefing', 'campaign'])) {
            return [
                'steps' => [
                    "Use the current hotspot forecast and field findings to define the exact households, puroks, or sitios in {$locationPhrase} that should receive the first prevention briefing.",
                    "Prepare a short awareness module focused on the documented fire drivers in {$locationPhrase}, not just general fire safety reminders.",
                    'Coordinate with barangay officials and local volunteers to schedule a targeted house-to-house or clustered briefing in the priority area.',
                    'Record attendance, recurring resident concerns, and any additional hazards discovered during the awareness activity for follow-up validation.',
                    "After the campaign, compare the documented hazards and resident feedback in {$locationPhrase} to decide whether inspection or mitigation actions should be escalated.",
                ],
                'resources_needed' => array_merge($resources, [
                    'Printed awareness materials tailored to the reported hazard drivers',
                    'Barangay coordination list for household outreach',
                ]),
                'estimated_timeline' => '1-2 weeks for preparation, rollout, and post-activity validation',
                'explanation' => $explanation,
                'context_summary' => $analysisSummary,
            ];
        }

        if (Str::contains($normalized, ['housing', 'structural', 'occupancy', 'combustible', 'informal settlement', 'waste'])) {
            return [
                'steps' => [
                    "Inspect {$locationPhrase} and document the exact structural or exposure condition driving the recommendation, including spacing, combustibility, occupancy pressure, or waste buildup.",
                    "Mark the most exposed homes or structures in {$locationPhrase} and identify which ones require immediate risk reduction or barangay coordination.",
                    'Coordinate corrective action such as clearing combustible buildup, improving exit access, or addressing unsafe clustering patterns in the affected area.',
                    "Issue a follow-up inspection note for the households or structures with the highest spread potential in {$locationPhrase}.",
                    'Reassess the area after corrective action and update the hazard record with before-and-after findings for command review.',
                ],
                'resources_needed' => array_merge($resources, [
                    'Area sketch or household clustering map',
                    'Barangay enforcement or clean-up coordination support',
                ]),
                'estimated_timeline' => '2-4 weeks for inspection, correction, and reassessment',
                'explanation' => $explanation,
                'context_summary' => $analysisSummary,
            ];
        }

        return [
            'steps' => [
                "Validate the specific condition described in '{$recommendationTitle}' within {$locationPhrase} and confirm whether the finding is still active.",
                "Break down the recommendation into a field action list covering inspection, correction, coordination, and final validation for {$locationPhrase}.",
                'Assign responsible responders or partner units who will handle the corrective action and document progress with dated updates.',
                "Implement the highest-impact control first in {$locationPhrase}, then continue with secondary corrective actions based on the confirmed field findings.",
                "Close the action only after the original risk or hazard driver in {$locationPhrase} has been rechecked and documented as reduced or resolved.",
            ],
            'resources_needed' => array_merge($resources, [
                'Coordination lead for barangay follow-up',
            ]),
            'estimated_timeline' => $urgencyText === 'Immediate'
                ? '3-7 days with rapid validation and follow-up'
                : '2-4 weeks depending on field corrections required',
            'explanation' => $explanation,
            'context_summary' => $analysisSummary,
        ];
    }

    private function extractContextBarangayName(array $contextPayload, string $combinedText): ?string
    {
        if (! empty($contextPayload['recommendation']['barangay_name'])) {
            return $contextPayload['recommendation']['barangay_name'];
        }

        if (! empty($contextPayload['action_plan_checklist']['barangay_name'])) {
            return $contextPayload['action_plan_checklist']['barangay_name'];
        }

        if (preg_match('/barangay\s+([A-Za-z0-9\s-]+)/i', $combinedText, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function extractContextPlaceLabel(array $contextPayload, string $combinedText, ?string $barangay): ?string
    {
        if (preg_match('/\b(?:in|at)\s+([^.,]+(?:,\s*[^.,]+)?)/i', $combinedText, $matches)) {
            return trim($matches[1]);
        }

        if (! empty($contextPayload['recommendation']['street_or_sitio'])) {
            return $barangay
                ? trim($contextPayload['recommendation']['street_or_sitio'].', '.$barangay)
                : trim($contextPayload['recommendation']['street_or_sitio']);
        }

        return $barangay;
    }
}
