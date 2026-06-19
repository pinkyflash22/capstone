<?php

namespace App\Http\Controllers\Chief;

use App\Http\Controllers\Controller;
use App\Models\AiActionPlanChecklist;
use App\Models\AiActionPlanChecklistItem;
use App\Models\AiRecommendationSuggestion;
use App\Models\Analysis;
use App\Models\StationOfficer;
use App\Models\Team;
use App\Models\Incident;
use App\Models\User;
use App\Services\ForecastModelService;
use App\Support\BarangayNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Carbon\Carbon;

class AnalyticController extends Controller
{
  
    private $apiKey;
    private ForecastModelService $forecastModelService;
    
    public function __construct(ForecastModelService $forecastModelService)
    {
        $this->forecastModelService = $forecastModelService;
        // Load API key from .env file
        $this->apiKey = env('OPENROUTER_API_KEY');
        
        if (!$this->apiKey) {
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

        $query = AiActionPlanChecklist::query()
            ->with(['analysis', 'barangay', 'assignee', 'confirmer', 'suggestions', 'items.reviewer'])
            ->when($stationId, fn ($builder) => $builder->where('station_id', $stationId));

        if ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->integer('barangay_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority_level', $request->input('priority'));
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
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
            ->get();

        $barangays = AiActionPlanChecklist::query()
            ->with('barangay')
            ->when($stationId, fn ($builder) => $builder->where('station_id', $stationId))
            ->whereNotNull('barangay_id')
            ->get()
            ->pluck('barangay')
            ->filter()
            ->unique('barangay_id')
            ->sortBy('barangay_name')
            ->values();

        $summary = [
            'total' => $checklists->count(),
            'open' => $checklists->whereIn('status', ['pending', 'in_progress'])->count(),
            'submitted' => $checklists->where('status', 'submitted')->count(),
            'confirmed' => $checklists->where('status', 'confirmed')->count(),
            'rejected' => $checklists->where('status', 'rejected')->count(),
        ];

        return view('chief.course-checklists', [
            'checklists' => $checklists,
            'barangays' => $barangays,
            'summary' => $summary,
            'filters' => [
                'barangay_id' => $request->input('barangay_id'),
                'status' => $request->input('status'),
                'priority' => $request->input('priority'),
                'q' => $request->input('q'),
            ],
        ]);
    }
    
    /**
     * Get stations/locations for the analytics dropdown
     */
    public function getStationsForAnalytics()
    {
        try {
            Log::info('Fetching stations for analytics');
            
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'data' => [['id' => 'all', 'name' => 'All Locations']],
                    'message' => 'User not authenticated'
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

                    $locations->push((object)[
                        'id' => (string)$station->station_id,
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

                    $locations->push((object)[
                        'id' => (string)$station->station_id,
                        'name' => $station->station_name,
                        'barangays' => $barangays,
                    ]);
                }
            }
            
            if ($locations->isEmpty()) {
                $locations->push((object)[
                    'id' => 'all',
                    'name' => 'All Locations'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'data' => $locations
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching stations: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'data' => [['id' => 'all', 'name' => 'All Locations']],
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * AI-powered predictive analysis based on REAL database data
     */
    public function runAIPredictiveAnalysis(Request $request)
    {
        try {
            @set_time_limit(180);
            @ini_set('max_execution_time', '180');

            Log::info('AI Analysis request received', $request->all());
            
            $locationId = $request->input('location_id', 'all');
            $selectedBarangayId = $request->filled('barangay_id') ? (int) $request->input('barangay_id') : null;
            $days = (int)$request->input('days', 30);
            $predictionScope = $request->input('prediction_scope', 'current_month');
            
            // Fetch REAL data from database
            $analysisData = $this->fetchRealTimeData($locationId, $days, $predictionScope, $selectedBarangayId);
            
            $predictionAiResponse = null;
            $recommendationAiResponse = null;
            try {
                $predictionAiResponse = $this->callAIApi(
                    $this->buildAIPredictionPrompt($analysisData),
                    'You are a fire risk forecasting analyst. Predict likely monthly fire activity, possible fire count, probable peak hours, and operational outlook using the provided station data.'
                );
                $recommendationAiResponse = $this->callAIApi(
                    $this->buildAIRecommendationPrompt($analysisData),
                    'You are a fire station operations strategist. Produce concrete, station-ready recommendations based on the provided incident, staffing, equipment, and weather context.'
                );
                Log::info('AI prediction response received', ['response' => substr($predictionAiResponse, 0, 300)]);
                Log::info('AI recommendation response received', ['response' => substr($recommendationAiResponse, 0, 300)]);
            } catch (\Exception $aiError) {
                Log::warning('AI API failed: ' . $aiError->getMessage());
            }
            
            // If AI succeeded, parse it; otherwise use data-driven analysis
            if ($predictionAiResponse && $recommendationAiResponse) {
                $structuredAnalysis = $this->structureAIAnalysisSeparated($predictionAiResponse, $recommendationAiResponse, $analysisData);
                $structuredAnalysis['ai_source'] = true;
            } else {
                $structuredAnalysis = $this->generateDataDrivenAnalysis($analysisData);
                $structuredAnalysis['note'] = 'Analysis based on data patterns (AI service temporarily unavailable)';
                $structuredAnalysis['ai_source'] = false;
            }
            
            // Store analysis in database (optional, catch errors)
            $analysisId = null;
            try {
                $analysisId = $this->storeAnalysis($locationId, $days, $structuredAnalysis, $analysisData);
                if ($analysisId) {
                    $structuredAnalysis['analysis_id'] = $analysisId;
                    $structuredAnalysis['action_plan_checklists'] = $this->generateAndStoreActionPlanChecklists(
                        $analysisId,
                        $structuredAnalysis,
                        $analysisData
                    );
                    $structuredAnalysis['recommendations'] = $this->attachChecklistIdsToRecommendations(
                        $structuredAnalysis['recommendations'] ?? [],
                        $structuredAnalysis['action_plan_checklists'] ?? []
                    );
                }
            } catch (\Exception $storeError) {
                Log::warning('Failed to store analysis: ' . $storeError->getMessage());
                // Don't fail the response if storage fails
            }
            
            return response()->json([
                'success' => true,
                'data' => $structuredAnalysis,
                'location_name' => $analysisData['location_name']
            ]);
            
        } catch (\Exception $e) {
            Log::error('AI Analysis error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Analysis failed: ' . $e->getMessage()
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
private function fetchRealTimeData($locationId, $days, $predictionScope = 'current_month', ?int $selectedBarangayId = null)
{
    $startDate = Carbon::now()->subDays($days);
    
    // Get user's station
    $user = Auth::user();
    
    if (!$user) {
        throw new \Exception('User not authenticated');
    }
    
    $stationOfficer = StationOfficer::where('user_id', $user->user_id)->first();
    
    if (!$stationOfficer) {
        throw new \Exception('No station assigned to this user');
    }
    
    $stationId = $stationOfficer->station_id;
    
    // Get station name
    $station = DB::table('stations')->where('station_id', $stationId)->first();
    $locationName = $station ? $station->station_name : 'My Station';
    $weatherContext = $this->fetchMonthlyWeatherContext($station, $days, $locationName);
    
    // Get all personnel in this station
    $allPersons = StationOfficer::where('station_id', $stationId)->get();
    $userIds = $allPersons->pluck('user_id')->toArray();
    
    // Get team IDs for this station using the same station -> officers -> team leader flow used elsewhere.
    $teamIds = !empty($userIds)
        ? Team::whereIn('team_leader', $userIds)->pluck('team_id')->toArray()
        : [];
    
    Log::info('Team IDs found: ' . json_encode($teamIds));
    
    // Get incidents only for this station's teams.
    $incidents = collect();
    
    if (!empty($teamIds)) {
        try {
            $incidents = Incident::whereIn('team_id', $teamIds)
                ->where('created_at', '>=', $startDate)
                ->get();
        } catch (\Exception $e) {
            Log::warning('Error fetching station-scoped incidents: ' . $e->getMessage());
            $incidents = collect();
        }
    }
    
    Log::info('Found ' . $incidents->count() . ' incidents for station ' . $stationId);
    
    // Calculate statistics
    $totalIncidents = $incidents->count();
    
    // Incidents by type
    $incidentsByType = [];
    $fireIncidents = 0;
    
    foreach ($incidents as $incident) {
        $type = $incident->incident_type_id ?? '0';
        $incidentsByType[$type] = ($incidentsByType[$type] ?? 0) + 1;
        // if (strtolower($type) === 'fire' || strpos(strtolower($type), 'fire') !== false) {
        if ($type == '1'){

            $fireIncidents++;
        }
    }
    
    // Hourly distribution
    $hourlyData = array_fill(0, 24, 0);
    $dailyData = array_fill(0, 7, 0);
    
    foreach ($incidents as $incident) {
        $incidentDate = $incident->created_at ?? null;
        if ($incidentDate) {
            try {
                $date = Carbon::parse($incidentDate);
                $hour = (int)$date->format('H');
                $dayOfWeek = $date->dayOfWeek;
                $hourlyData[$hour]++;
                $dailyData[$dayOfWeek]++;
            } catch (\Exception $e) {}
        }
    }
    
    // Find peak hour
    $peakHour = array_search(max($hourlyData), $hourlyData);
    if ($peakHour === false || max($hourlyData) === 0) {
        $peakHour = null;
    }
    
    // Calculate average response time
    $avgResponseTime = null;
    $completedIncidents = $incidents->filter(function($inc) {
        return isset($inc->dateEnded) && isset($inc->alarmdatetime);
    });
    
    if ($completedIncidents->count() > 0) {
        $totalMinutes = 0;
        foreach ($completedIncidents as $inc) {
            try {
                $alarm = Carbon::parse($inc->alarmdatetime);
                $ended = Carbon::parse($inc->dateEnded);
                $totalMinutes += $alarm->diffInMinutes($ended);
            } catch (\Exception $e) {}
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
        Log::warning('Error fetching station equipment stats: ' . $e->getMessage());
        $totalEquipment = 0;
        $availableEquipment = 0;
    }
    
    $predictionContext = $this->buildPredictionContext($teamIds, $predictionScope);
    $barangayPredictions = $this->buildBarangayPredictionData($stationId, $predictionContext, $weatherContext);
    $modelPrediction = $this->buildModelPredictionData($stationId, $predictionContext, $weatherContext, $barangayPredictions);
    $selectedBarangayPrediction = null;

    if ($selectedBarangayId) {
        $selectedBarangayPrediction = collect($barangayPredictions)
            ->first(fn ($item) => (int) ($item['barangay_id'] ?? 0) === $selectedBarangayId);

        if ($selectedBarangayPrediction) {
            $barangayPredictions = [$selectedBarangayPrediction];
            $locationName = ($station ? $station->station_name : 'My Station') . ' · ' . ($selectedBarangayPrediction['barangay_name'] ?? 'Selected barangay');
        }
    }

    $mitigationContext = $this->buildMitigationContext($stationId);

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
        'prediction_context' => $predictionContext,
        'model_prediction' => $modelPrediction,
        'barangay_predictions' => $barangayPredictions,
        'mitigation_context' => $mitigationContext,
    ];
}

    private function buildMitigationContext(int $stationId): array
    {
        $priorityWeights = [
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'critical' => 4,
        ];

        $checklists = AiActionPlanChecklist::query()
            ->where('station_id', $stationId)
            ->get(['priority_level', 'status']);

        $totalWeight = 0;
        $confirmedWeight = 0;
        $pendingWeight = 0;

        foreach ($checklists as $checklist) {
            $weight = $priorityWeights[strtolower($checklist->priority_level ?? 'medium')] ?? 2;
            $totalWeight += $weight;

            if (strtolower($checklist->status ?? '') === 'confirmed') {
                $confirmedWeight += $weight;
            } elseif (in_array(strtolower($checklist->status ?? ''), ['pending', 'in_progress', 'submitted', 'rejected'], true)) {
                $pendingWeight += $weight;
            }
        }

        $completionRatio = $totalWeight > 0 ? round($confirmedWeight / $totalWeight, 4) : 0.0;
        $riskReductionPercent = $totalWeight > 0 ? round(min(0.25, $completionRatio * 0.25) * 100, 2) : 0.0;

        return [
            'total_checklists' => $checklists->count(),
            'confirmed_weight' => $confirmedWeight,
            'pending_weight' => $pendingWeight,
            'completion_ratio' => $completionRatio,
            'risk_reduction_percent' => $riskReductionPercent,
            'summary' => $totalWeight > 0
                ? "{$checklists->count()} mitigation checklist(s) tracked, with " . round($completionRatio * 100, 1) . "% weighted completion."
                : 'No confirmed mitigation checklist actions yet.',
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
            $predictedDamage = round((float) collect($barangayPredictions)->sum(function ($item) {
                return (float) ($item['predicted_estimated_damage'] ?? 0);
            }), 2);

            $maxProbability = (float) ($probabilities->max() ?? 0);
            $averageProbability = (float) ($probabilities->avg() ?? 0);
            $stationProbability = round(($maxProbability * 0.55) + ($averageProbability * 0.45), 2);

            $riskLevel = 'low';
            if ($stationProbability >= 65 || $predictedIncidentCount >= 6 || $predictedDamage >= 500000) {
                $riskLevel = 'high';
            } elseif ($stationProbability >= 35 || $predictedIncidentCount >= 3 || $predictedDamage >= 150000) {
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
                'predicted_incident_count' => $predictedIncidentCount,
                'predicted_estimated_damage' => $predictedDamage,
                'drivers' => array_map(fn ($summary) => ['summary' => $summary], $driverSummaries),
                'watch_items' => $watchItems,
            ];

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
            Log::warning('Unable to build model prediction data: ' . $e->getMessage());
            return null;
        }
    }

    private function buildBarangayPredictionData(int $stationId, array $predictionContext, array $weatherContext = []): array
    {
        try {
            $this->forecastModelService->ensureReady();

            $targetMonth = !empty($predictionContext['target_month_label'])
                ? Carbon::createFromFormat('F Y', $predictionContext['target_month_label'])->startOfMonth()
                : Carbon::now()->startOfMonth();

            $stationHistoricalRow = DB::table('historical_data')
                ->where('station_id', $stationId)
                ->where('month', $targetMonth->format('F'))
                ->where('year', $targetMonth->year)
                ->first();

            if (!$stationHistoricalRow) {
                $stationHistoricalRow = DB::table('historical_data')
                    ->where('station_id', $stationId)
                    ->orderByDesc('year')
                    ->orderByRaw("
                        CASE month
                            WHEN 'January' THEN 1
                            WHEN 'February' THEN 2
                            WHEN 'March' THEN 3
                            WHEN 'April' THEN 4
                            WHEN 'May' THEN 5
                            WHEN 'June' THEN 6
                            WHEN 'July' THEN 7
                            WHEN 'August' THEN 8
                            WHEN 'September' THEN 9
                            WHEN 'October' THEN 10
                            WHEN 'November' THEN 11
                            WHEN 'December' THEN 12
                            ELSE 13
                        END DESC
                    ")
                    ->first();
            }

            if (!$stationHistoricalRow) {
                return [];
            }

            $barangays = $this->resolveStationBarangays($stationId);
            if ($barangays->isEmpty()) {
                return [];
            }

            $forecasts = [];

            foreach ($barangays as $barangay) {
                $payload = $this->buildBarangayPredictionPayload(
                    $stationId,
                    $barangay,
                    $stationHistoricalRow,
                    $targetMonth,
                    $weatherContext
                );

                if (!$payload) {
                    continue;
                }

                $process = Process::path(base_path())
                    ->input(json_encode($payload))
                    ->run(['python', 'capstone-ai/predict_event.py']);

                if ($process->failed()) {
                    Log::warning('Python barangay prediction process failed', [
                        'station_id' => $stationId,
                        'barangay_id' => $barangay->barangay_id ?? null,
                        'error' => $process->errorOutput(),
                        'output' => $process->output(),
                    ]);
                    continue;
                }

                $decoded = json_decode($process->output(), true);
                if (!is_array($decoded)) {
                    Log::warning('Python barangay prediction output was not valid JSON', [
                        'station_id' => $stationId,
                        'barangay_id' => $barangay->barangay_id ?? null,
                        'output' => $process->output(),
                    ]);
                    continue;
                }

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
            Log::warning('Unable to build barangay prediction data: ' . $e->getMessage());
            return [];
        }
    }

    private function resolveStationBarangays(int $stationId)
    {
        $barangays = collect();

        $stationAddressBarangays = DB::table('stations')
            ->leftJoin('full_addresses', 'stations.address_id', '=', 'full_addresses.address_id')
            ->leftJoin('barangays', 'full_addresses.barangay_id', '=', 'barangays.barangay_id')
            ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
            ->where('stations.station_id', $stationId)
            ->select('barangays.barangay_id', 'barangays.barangay_name', 'cities.city_name', 'cities.city_id')
            ->get()
            ->filter(fn ($row) => !empty($row->barangay_id));

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

    private function buildBarangayPredictionPayload(int $stationId, object $barangay, object $stationHistoricalRow, Carbon $targetMonth, array $weatherContext = []): ?array
    {
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

        $nearWater = DB::table('risk_areas')
            ->join('barangay_halls', 'risk_areas.barangay_hall_id', '=', 'barangay_halls.barangay_hall_id')
            ->where('risk_areas.station_id', $stationId)
            ->where('barangay_halls.barangay_id', $barangay->barangay_id)
            ->where(function ($query) {
                $query->where('risk_areas.near_water_source', 1)
                    ->orWhere('risk_areas.near_hydrant', 1);
            })
            ->exists()
            ? 1
            : (int) ($stationHistoricalRow->near_water ?? 0);

        return [
            'station_id' => $stationId,
            'barangay_id' => (int) $barangay->barangay_id,
            'barangay_name' => $barangay->barangay_name,
            'month' => $targetMonth->format('F'),
            'year' => (int) $targetMonth->year,
            'incident_count' => $incidentCount,
            'risk_level' => $historicalRiskLevel,
            'average_temperature' => (float) $averageTemperature,
            'average_humidity' => $averageHumidity !== null ? (float) $averageHumidity : null,
            'average_wind_speed' => $averageWindSpeed !== null ? (float) $averageWindSpeed : null,
            'weather_condition' => $weatherCondition,
            'season' => $stationHistoricalRow->season,
            'near_water' => $nearWater,
            'density' => $density,
            'materials' => $materials,
            'peak_hours' => $peakHours,
            'accesibility' => $accessibility,
            'type_incident' => $typeIncident,
            'occupancy' => $occupancy,
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
                } catch (\Exception $e) {}
            }
        }
        
        if ($recentIncidents > $incidentRate * 7 * 1.5) {
            $score += 20;
        }
        
        $score = min(100, round($score));
        
        $level = 'Low';
        if ($score >= 70) $level = 'Critical';
        elseif ($score >= 50) $level = 'High';
        elseif ($score >= 30) $level = 'Medium';
        
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
            $factors[] = "Incident patterns within normal operational parameters";
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
        
        if (!empty($data['incidents_by_type'])) {
            $prompt .= "\nBY TYPE:\n";
            foreach ($data['incidents_by_type'] as $type => $count) {
                $prompt .= "- {$type}: {$count}\n";
            }
        }
        
        $prompt .= "\nPATTERNS:\n";
        $prompt .= "- Peak Hour: " . ($data['peak_hour'] ? $data['peak_hour'] . ':00' : 'No clear pattern') . "\n";
        $prompt .= "- Response Time: " . ($data['avg_response_time'] ? $data['avg_response_time'] . ' minutes' : 'N/A') . "\n";
        $prompt .= "- Risk Score: {$data['risk_score']}/100 ({$data['risk_level']})\n";
        $prompt .= "- Total Personnel: {$data['total_personnel']}\n";
        $prompt .= "- Available Personnel: {$data['available_personnel']}\n";
        $prompt .= "- Total Equipment: {$data['total_equipment']}\n";
        $prompt .= "- Available Equipment: {$data['available_equipment']}\n\n";

        if (!empty($data['prediction_context'])) {
            $predictionContext = $data['prediction_context'];
            $prompt .= "MONTHLY PREDICTION TARGET:\n";
            $prompt .= "- Scope: " . ($predictionContext['scope_label'] ?? $data['prediction_scope']) . "\n";
            $prompt .= "- Target Month: " . ($predictionContext['target_month_label'] ?? 'Current Month') . "\n";
            $prompt .= "- Calendar Window: " . ($predictionContext['target_period_label'] ?? 'N/A') . "\n";
            $prompt .= "- Current Month Incidents Counted: " . ($predictionContext['current_month_total_incidents'] ?? 0) . "\n";
            $prompt .= "- Current Month Fire Incidents: " . ($predictionContext['current_month_fire_incidents'] ?? 0) . "\n";
            $prompt .= "- Previous Full Month Incidents: " . ($predictionContext['previous_month_total_incidents'] ?? 0) . "\n";
            $prompt .= "- Previous Full Month Fire Incidents: " . ($predictionContext['previous_month_fire_incidents'] ?? 0) . "\n";
            $prompt .= "- Observed Peak Hours This Month: " . implode(', ', $predictionContext['likely_peak_hours'] ?? ['None']) . "\n";
            $prompt .= "- Current Month Hourly Incident Distribution: " . json_encode($predictionContext['current_month_hourly_data'] ?? []) . "\n\n";
        }

        if (!empty($data['weather_context'])) {
            $weather = $data['weather_context'];
            $prompt .= "WEATHER CONTEXT FOR THIS MONTH:\n";
            $prompt .= "- Query Location: " . ($weather['query_location'] ?? $data['location_name']) . "\n";
            $prompt .= "- Coverage Window: " . ($weather['coverage'] ?? 'Current month') . "\n";
            $prompt .= "- Average Temperature: " . (($weather['avg_temp_c'] ?? null) !== null ? $weather['avg_temp_c'] . "°C" : 'N/A') . "\n";
            $prompt .= "- Average Humidity: " . (($weather['avg_humidity'] ?? null) !== null ? $weather['avg_humidity'] . "%" : 'N/A') . "\n";
            $prompt .= "- Average Wind: " . (($weather['avg_wind_kph'] ?? null) !== null ? $weather['avg_wind_kph'] . " kph" : 'N/A') . "\n";
            $prompt .= "- Rainy Days in Sample: " . ($weather['rainy_days'] ?? 0) . "\n";
            $prompt .= "- Hot Days in Sample: " . ($weather['hot_days'] ?? 0) . "\n";
            $prompt .= "- Dominant Condition: " . ($weather['dominant_condition'] ?? 'N/A') . "\n";
            $prompt .= "- Fire Weather Risk: " . ($weather['weather_risk_label'] ?? 'N/A') . "\n";
            $prompt .= "- Weather Summary: " . ($weather['summary'] ?? 'N/A') . "\n\n";
        }

        if (!empty($data['mitigation_context'])) {
            $mitigation = $data['mitigation_context'];
            $prompt .= "MITIGATION STATUS:\n";
            $prompt .= "- " . ($mitigation['summary'] ?? 'No mitigation summary available') . "\n";
            $prompt .= "- Estimated risk reduction from confirmed actions: " . ($mitigation['risk_reduction_percent'] ?? 0) . "%\n\n";
        }

        if (!empty($data['selected_barangay_prediction'])) {
            $prompt .= "SELECTED BARANGAY FOCUS:\n";
            $prompt .= "- Barangay: " . ($data['selected_barangay_prediction']['barangay_name'] ?? 'Selected barangay') . "\n";
            $prompt .= "- Barangay risk probability: " . ($data['selected_barangay_prediction']['probability_percent'] ?? 0) . "%\n";
            $prompt .= "- Barangay risk level: " . strtoupper($data['selected_barangay_prediction']['risk_level'] ?? 'unknown') . "\n\n";
        }

        if (!empty($data['barangay_predictions'])) {
            $prompt .= "BARANGAY-LEVEL HOTSPOT FORECASTS:\n";
            foreach (array_slice($data['barangay_predictions'], 0, 5) as $barangayForecast) {
                $prompt .= "- " . ($barangayForecast['barangay_name'] ?? 'Unknown barangay')
                    . ': ' . strtoupper($barangayForecast['risk_level'] ?? 'unknown')
                    . ' risk at ' . ($barangayForecast['probability_percent'] ?? 0) . "% probability";

                if (!empty($barangayForecast['drivers'][0]['summary'])) {
                    $prompt .= " | Driver: " . $barangayForecast['drivers'][0]['summary'];
                }

                $prompt .= "\n";
            }
            $prompt .= "\n";
        }

        if (!empty($data['model_prediction']['output'])) {
            $modelPrediction = $data['model_prediction']['output'];
            $prompt .= "ML MODEL SIGNALS:\n";
            $prompt .= "- Model Target: " . ($modelPrediction['target'] ?? 'N/A') . "\n";
            $prompt .= "- Probability: " . (($modelPrediction['probability_percent'] ?? null) !== null ? $modelPrediction['probability_percent'] . "%" : 'N/A') . "\n";
            $prompt .= "- Risk Level: " . strtoupper($modelPrediction['risk_level'] ?? 'unknown') . "\n";
            $prompt .= "- Confidence Band: " . strtoupper($modelPrediction['confidence_band'] ?? 'unknown') . "\n";
            $prompt .= "- Predicted Incident Count: " . ($modelPrediction['predicted_incident_count'] ?? 'N/A') . "\n";
            $prompt .= "- Predicted Damage Exposure: " . (isset($modelPrediction['predicted_estimated_damage']) ? 'PHP ' . number_format((float) $modelPrediction['predicted_estimated_damage'], 2) : 'N/A') . "\n";
            $prompt .= "- Watch Items: " . implode(', ', $modelPrediction['watch_items'] ?? ['None']) . "\n";
            foreach (array_slice($modelPrediction['drivers'] ?? [], 0, 3) as $driver) {
                if (!empty($driver['summary'])) {
                    $prompt .= "- Driver: {$driver['summary']}\n";
                }
            }
            $prompt .= "\nUse these model signals as grounded evidence. Do not ignore them when forming the prediction.\n\n";
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
        $prompt .= "PEAK HOUR: " . ($data['peak_hour'] ? $data['peak_hour'] . ':00' : 'No clear pattern') . "\n";
        $prompt .= "AVG RESPONSE TIME: " . ($data['avg_response_time'] ? $data['avg_response_time'] . ' minutes' : 'N/A') . "\n";
        $prompt .= "AVAILABLE PERSONNEL: {$data['available_personnel']} / {$data['total_personnel']}\n";
        $prompt .= "AVAILABLE EQUIPMENT: {$data['available_equipment']} / {$data['total_equipment']}\n";

        if (!empty($data['prediction_context'])) {
            $prompt .= "MONTHLY CONTEXT:\n";
            $prompt .= "- Current month fire incidents: " . ($data['prediction_context']['current_month_fire_incidents'] ?? 0) . "\n";
            $prompt .= "- Likely peak hours: " . implode(', ', $data['prediction_context']['likely_peak_hours'] ?? ['None']) . "\n";
            $prompt .= "- Target month: " . ($data['prediction_context']['target_month_label'] ?? 'Current Month') . "\n";
        }

        if (!empty($data['weather_context'])) {
            $prompt .= "WEATHER SUMMARY: " . ($data['weather_context']['summary'] ?? 'N/A') . "\n";
        }

        if (!empty($data['mitigation_context'])) {
            $prompt .= "MITIGATION SUMMARY: " . ($data['mitigation_context']['summary'] ?? 'N/A') . "\n";
            $prompt .= "MITIGATION RISK REDUCTION: " . ($data['mitigation_context']['risk_reduction_percent'] ?? 0) . "%\n";
        }

        if (!empty($data['selected_barangay_prediction'])) {
            $selectedBarangay = $data['selected_barangay_prediction'];
            $prompt .= "SELECTED BARANGAY PRIORITY:\n";
            $prompt .= "- Barangay: " . ($selectedBarangay['barangay_name'] ?? 'Selected barangay') . "\n";
            $prompt .= "- Probability: " . ($selectedBarangay['probability_percent'] ?? 0) . "%\n";
            $prompt .= "- Risk level: " . strtoupper($selectedBarangay['risk_level'] ?? 'unknown') . "\n";
            if (!empty($selectedBarangay['drivers'][0]['summary'])) {
                $prompt .= "- Leading driver: " . $selectedBarangay['drivers'][0]['summary'] . "\n";
            }
        }

        if (!empty($data['barangay_predictions'])) {
            $prompt .= "TOP BARANGAY HOTSPOTS:\n";
            foreach (array_slice($data['barangay_predictions'], 0, 5) as $barangayForecast) {
                $prompt .= "- " . ($barangayForecast['barangay_name'] ?? 'Unknown barangay')
                    . " | probability: " . ($barangayForecast['probability_percent'] ?? 0) . "%"
                    . " | risk: " . strtoupper($barangayForecast['risk_level'] ?? 'unknown') . "\n";
            }
        }

        if (!empty($data['model_prediction']['output'])) {
            $modelPrediction = $data['model_prediction']['output'];
            $prompt .= "ML MODEL RISK BRIEF:\n";
            $prompt .= "- Risk level: " . strtoupper($modelPrediction['risk_level'] ?? 'unknown') . "\n";
            $prompt .= "- Probability: " . (($modelPrediction['probability_percent'] ?? null) !== null ? $modelPrediction['probability_percent'] . "%" : 'N/A') . "\n";
            $prompt .= "- Confidence: " . strtoupper($modelPrediction['confidence_band'] ?? 'unknown') . "\n";
            $prompt .= "- Predicted incidents: " . ($modelPrediction['predicted_incident_count'] ?? 'N/A') . "\n";
            $prompt .= "- Predicted damage: " . (isset($modelPrediction['predicted_estimated_damage']) ? 'PHP ' . number_format((float) $modelPrediction['predicted_estimated_damage'], 2) : 'N/A') . "\n";
            $prompt .= "- Watch items: " . implode(', ', $modelPrediction['watch_items'] ?? ['None']) . "\n";
            foreach (array_slice($modelPrediction['drivers'] ?? [], 0, 3) as $driver) {
                if (!empty($driver['summary'])) {
                    $prompt .= "- Evidence: {$driver['summary']}\n";
                }
            }
        }

        $prompt .= "\nProvide station recommendations only. Do not repeat the monthly prediction.\n";
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
        $prompt .= "CONFIDENCE SCORE: " . ($analysis['confidence_score'] ?? 'N/A') . "\n";

        if (!empty($analysis['predictions']['summary'])) {
            $prompt .= "PREDICTION SUMMARY: {$analysis['predictions']['summary']}\n";
        }

        if (!empty($data['model_prediction']['output'])) {
            $modelPrediction = $data['model_prediction']['output'];
            $prompt .= "MODEL PROBABILITY: " . ($modelPrediction['probability_percent'] ?? 'N/A') . "%\n";
            $prompt .= "MODEL RISK LEVEL: " . strtoupper($modelPrediction['risk_level'] ?? 'unknown') . "\n";
            $prompt .= "MODEL PREDICTED INCIDENT COUNT: " . ($modelPrediction['predicted_incident_count'] ?? 'N/A') . "\n";
            $prompt .= "MODEL PREDICTED DAMAGE: " . (isset($modelPrediction['predicted_estimated_damage']) ? 'PHP ' . number_format((float) $modelPrediction['predicted_estimated_damage'], 2) : 'N/A') . "\n";
            foreach (array_slice($modelPrediction['drivers'] ?? [], 0, 3) as $driver) {
                if (!empty($driver['summary'])) {
                    $prompt .= "MODEL DRIVER: {$driver['summary']}\n";
                }
            }
        }

        $prompt .= "\nRECOMMENDATIONS TO CONVERT INTO A CHECKLIST:\n";
        foreach (array_slice($analysis['recommendations'] ?? [], 0, 10) as $index => $recommendation) {
            $prompt .= ($index + 1) . ". {$recommendation['title']} | {$recommendation['priority']}\n";
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
    if (!$this->apiKey) {
        Log::error('No API key available for AI call');
        throw new \Exception('No API key configured');
    }
    
    Log::info('Calling OpenRouter API with key: ' . substr($this->apiKey, 0, 15) . '...');
    
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->apiKey,
        'Content-Type' => 'application/json',
        'HTTP-Referer' => url('/'),
        'X-Title' => 'PyroSense Analytics'
    ])->connectTimeout(10)->timeout(35)->post('https://openrouter.ai/api/v1/chat/completions', [
        'model' => 'deepseek/deepseek-r1-distill-qwen-32b',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.3,
        'max_tokens' => 700
    ]);
    
    if (!$response->successful()) {
        $errorBody = $response->body();
        Log::error('OpenRouter API call failed: ' . $response->status() . ' - ' . $errorBody);
        throw new \Exception('API call failed: ' . $response->status());
    }
    
    $json = $response->json();
    $content = $json['choices'][0]['message']['content'] ?? '';
    
    if (empty($content)) {
        throw new \Exception('Empty response from AI API');
    }
    
    Log::info('AI API call successful, response length: ' . strlen($content));
    
    return $content;
}

    private function generateAndStoreActionPlanChecklists(int $analysisId, array $analysis, array $rawData): array
    {
        $recommendations = array_slice($analysis['recommendations'] ?? [], 0, 10);
        if (empty($recommendations)) {
            return [];
        }

        $plans = [];
        $aiGenerated = false;
        $shouldGenerateAiActionPlans = filter_var(
            env('AI_GENERATE_ACTION_PLAN_CHECKLISTS', false),
            FILTER_VALIDATE_BOOL
        );

        if ($shouldGenerateAiActionPlans) {
            try {
                $aiResponse = $this->callAIApi(
                    $this->buildAIActionPlanPrompt($analysis, $rawData),
                    'You are a fire safety compliance planner. Return JSON only, with concrete checklist items that BFP can review and confirm.'
                );
                $plans = $this->parseActionPlanChecklistResponse($aiResponse);
                $aiGenerated = !empty($plans);
            } catch (\Throwable $e) {
                Log::warning('Action-plan checklist AI generation failed: ' . $e->getMessage());
            }
        } else {
            Log::info('Skipping checklist AI generation during analytics request and using recommendation-backed checklist rows.');
        }

        $plans = $this->normalizeActionPlanCount($plans, $recommendations);

        AiActionPlanChecklist::query()->where('analysis_id', $analysisId)->delete();

        $savedPlans = [];
        foreach ($plans as $index => $plan) {
            $sourceRecommendation = $recommendations[$index] ?? $recommendations[0];

            $saved = AiActionPlanChecklist::create([
                'analysis_id' => $analysisId,
                'station_id' => $rawData['station_id'] ?? null,
                'barangay_id' => $rawData['barangay_id'] ?? null,
                'source_type' => 'action_plan',
                'source_title' => $plan['source_title'] ?? ($sourceRecommendation['title'] ?? 'AI Action Plan'),
                'source_description' => $sourceRecommendation['description'] ?? null,
                'plan_title' => $plan['plan_title'] ?? ($sourceRecommendation['title'] ?? 'AI Action Plan'),
                'plan_description' => $plan['plan_description'] ?? ($sourceRecommendation['description'] ?? null),
                'checklist_items' => array_values(array_filter($plan['checklist_items'] ?? [])),
                'success_indicator' => $plan['success_indicator'] ?? null,
                'priority_level' => strtolower($plan['priority_level'] ?? ($sourceRecommendation['priority'] ?? 'medium')),
                'status' => 'pending',
                'target_date' => isset($plan['target_days']) ? now()->addDays((int) $plan['target_days'])->toDateString() : null,
                'ai_payload' => $plan,
                'is_ai_generated' => $aiGenerated,
            ]);

            $this->syncChecklistItems($saved, array_values(array_filter($plan['checklist_items'] ?? [])));
            BarangayNotificationService::notifyActionPlanChecklistPublished($saved);

            $savedPlans[] = $saved->fresh();
        }

        return $savedPlans;
    }

    private function parseActionPlanChecklistResponse(string $content): array
    {
        $cleaned = trim($content);
        $cleaned = preg_replace('/^```json\s*/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/^```\s*/', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\s*```$/', '', $cleaned) ?? $cleaned;

        $decoded = json_decode($cleaned, true);
        if (!is_array($decoded)) {
            return [];
        }

        $plans = $decoded['plans'] ?? null;
        return is_array($plans) ? $plans : [];
    }

    private function buildFallbackActionPlanChecklists(array $recommendations): array
    {
        return array_map(function ($recommendation) {
            $priority = strtolower($recommendation['priority'] ?? 'medium');

            return [
                'source_title' => $recommendation['title'] ?? 'AI Recommendation',
                'plan_title' => $recommendation['title'] ?? 'AI Action Plan',
                'plan_description' => $recommendation['description'] ?? null,
                'priority_level' => $priority,
                'success_indicator' => 'BFP confirms the action was completed and documented.',
                'target_days' => $priority === 'critical' ? 7 : ($priority === 'high' ? 14 : 30),
                'checklist_items' => [
                    'Assign a responsible officer or team for this action.',
                    'Document the current risk condition and affected area.',
                    'Complete the recommended mitigation activity.',
                    'Record proof of compliance for BFP review.',
                ],
            ];
        }, $recommendations);
    }

    private function normalizeActionPlanCount(array $plans, array $recommendations): array
    {
        if (empty($plans)) {
            return $this->buildFallbackActionPlanChecklists($recommendations);
        }

        $normalizedPlans = [];
        foreach ($recommendations as $index => $recommendation) {
            $existingPlan = $plans[$index] ?? null;

            if (is_array($existingPlan) && !empty($existingPlan['plan_title'])) {
                $normalizedPlans[] = $existingPlan;
                continue;
            }

            $fallbackPlan = $this->buildFallbackActionPlanChecklists([$recommendation])[0];
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

        if (!empty($rows)) {
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
                    'priority' => $priority
                ];
            }
        }
        
        // Sort recommendations by priority
        usort($recommendations, function($a, $b) {
            $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
            $priorityA = strtolower($a['priority']);
            $priorityB = strtolower($b['priority']);
            return ($priorityOrder[$priorityA] ?? 3) - ($priorityOrder[$priorityB] ?? 3);
        });
        
        // If no recommendations parsed, generate data-driven ones
        if (empty($recommendations)) {
            Log::warning('No recommendations parsed from AI, using data-driven');
            $recommendations = $this->generateDataDrivenRecommendations($data);
            usort($recommendations, function($a, $b) {
                $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
                $priorityA = strtolower($a['priority']);
                $priorityB = strtolower($b['priority']);
                return ($priorityOrder[$priorityA] ?? 3) - ($priorityOrder[$priorityB] ?? 3);
            });
        }
        
        return [
            'risk_score' => $data['risk_score'],
            'risk_level' => $data['risk_level'],
            'confidence_score' => 85,
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
            'ai_explanation' => substr($aiContent, 0, 500)
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
        
        if ($data['risk_score'] >= 70) return 'High';
        if ($data['risk_score'] >= 50) return 'Medium';
        
        return 'Low';
    }
    
    /**
     * Generate data-driven recommendations
     */
    private function generateDataDrivenRecommendations($data)
    {
        $recommendations = [];
        
        if ($data['peak_hour']) {
            $recommendations[] = [
                'title' => 'Increase patrol during peak hours',
                'description' => "Deploy additional units around {$data['peak_hour']}:00 when incident frequency is highest",
                'priority' => 'Medium'
            ];
        }
        
        if ($data['fire_incidents'] > 5) {
            $recommendations[] = [
                'title' => 'Conduct fire safety inspections',
                'description' => "Schedule inspections following {$data['fire_incidents']} fire incidents",
                'priority' => 'High'
            ];
        }
        
        if ($data['avg_response_time'] && $data['avg_response_time'] > 15) {
            $recommendations[] = [
                'title' => 'Optimize response routes',
                'description' => "Review routes to reduce {$data['avg_response_time']}-minute response time",
                'priority' => 'High'
            ];
        }
        
        if ($data['available_personnel'] < $data['total_personnel'] * 0.6) {
            $recommendations[] = [
                'title' => 'Address personnel availability',
                'description' => "Only {$data['available_personnel']} of {$data['total_personnel']} personnel available",
                'priority' => 'Critical'
            ];
        }
        
        $recommendations[] = [
            'title' => 'Community fire safety awareness',
            'description' => 'Launch prevention campaign targeting common incident types',
            'priority' => 'Medium'
        ];
        
        $recommendations[] = [
            'title' => 'Review response protocols',
            'description' => 'Update SOPs based on recent incident patterns',
            'priority' => 'Low'
        ];
        
        return $recommendations;
    }
    
    /**
     * Generate fallback analysis
     */
    private function generateDataDrivenAnalysis($data)
    {
        $recommendations = $this->generateDataDrivenRecommendations($data);
        $predictions = $this->generateDataDrivenPrediction($data);
        
        $explanation = "Based on {$data['total_incidents']} incidents over {$data['days_analyzed']} days, " .
                      "risk level is {$data['risk_level']} with {$data['fire_incidents']} fire incidents. " .
                      "Peak time: " . ($data['peak_hour'] ? $data['peak_hour'] . ':00' : 'not determined');
        
        return [
            'risk_score' => $data['risk_score'],
            'risk_level' => $data['risk_level'],
            'confidence_score' => 80,
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
            'barangay_predictions' => $data['barangay_predictions'] ?? [],
            'prediction_scope' => $data['prediction_scope'] ?? 'current_month',
            'prediction_context' => $data['prediction_context'] ?? null,
            'predictions' => $predictions,
            'recommendations' => $recommendations,
            'ai_explanation' => $explanation,
            'ai_source' => false
        ];
    }

    private function structureAIAnalysisSeparated($predictionAiContent, $recommendationAiContent, $data)
    {
        $recommendations = $this->extractRecommendationsFromAi($recommendationAiContent, $data);
        $predictions = $this->extractPredictionData($predictionAiContent, $data);

        usort($recommendations, function($a, $b) {
            $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
            $priorityA = strtolower($a['priority']);
            $priorityB = strtolower($b['priority']);
            return ($priorityOrder[$priorityA] ?? 3) - ($priorityOrder[$priorityB] ?? 3);
        });

        if (empty($recommendations)) {
            $recommendations = $this->generateDataDrivenRecommendations($data);
            usort($recommendations, function($a, $b) {
                $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
                $priorityA = strtolower($a['priority']);
                $priorityB = strtolower($b['priority']);
                return ($priorityOrder[$priorityA] ?? 3) - ($priorityOrder[$priorityB] ?? 3);
            });
        }

        return [
            'risk_score' => $data['risk_score'],
            'risk_level' => $data['risk_level'],
            'confidence_score' => 85,
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
            'barangay_predictions' => $data['barangay_predictions'] ?? [],
            'prediction_scope' => $data['prediction_scope'] ?? 'current_month',
            'prediction_context' => $data['prediction_context'] ?? null,
            'predictions' => $predictions,
            'recommendations' => array_slice($recommendations, 0, 6),
            'ai_explanation' => substr($predictionAiContent, 0, 300) . "\n\n" . substr($recommendationAiContent, 0, 300)
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

            if ((preg_match('/^(\d+)[\.\)]\s+(.+)/', $line, $matches) || preg_match('/^[â€¢\-*]\s+(.+)/', $line, $matches)) && !$currentRecommendation) {
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
            return !empty($recommendation['title']) && !empty($recommendation['description']);
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
            
            if (!$stationId) {
                Log::warning('No station ID found in raw data, skipping storage');
                return null;
            }
            
            // Get incident count from raw data
            $incidentCount = $rawData['total_incidents'] ?? 0;
            
            // Prepare sector name
            $sectorName = $rawData['location_name'];
            
            // Prepare analysis_data JSON
            $analysisDataJson = json_encode([
                'analysis_summary' => $analysis,
                'raw_data' => [
                    'hourly_data' => $rawData['hourly_data'],
                    'daily_data' => $rawData['daily_data'],
                    'peak_hour' => $rawData['peak_hour'],
                    'avg_response_time' => $rawData['avg_response_time'],
                    'total_personnel' => $rawData['total_personnel'],
                    'available_personnel' => $rawData['available_personnel'],
                    'total_equipment' => $rawData['total_equipment'],
                    'available_equipment' => $rawData['available_equipment'],
                    'risk_factors' => $rawData['risk_factors'],
                    'weather_context' => $rawData['weather_context'] ?? null,
                    'mitigation_context' => $rawData['mitigation_context'] ?? null,
                    'model_prediction' => $rawData['model_prediction'] ?? null,
                    'barangay_predictions' => $rawData['barangay_predictions'] ?? [],
                    'selected_barangay_prediction' => $rawData['selected_barangay_prediction'] ?? null,
                    'prediction_context' => $rawData['prediction_context'] ?? null,
                    'prediction_scope' => $rawData['prediction_scope'] ?? 'current_month'
                ],
                'ai_analysis' => $analysis['ai_explanation'] ?? null,
                'generated_at' => now()->toISOString(),
                'ai_source' => $analysis['ai_source'] ?? false
            ]);
            
            // Prepare recommendations JSON
            $recommendationsJson = json_encode(array_map(function($rec) {
                return [
                    'title' => $rec['title'],
                    'description' => $rec['description'],
                    'priority' => $rec['priority']
                ];
            }, $analysis['recommendations'] ?? []));

            $predictionsJson = json_encode($analysis['predictions'] ?? null);
            
              $analysisId = DB::table('analysis')->insertGetId([
                  'sector' => $sectorName,
                  'period' => "Last {$days} days",
                  'risk_level' => $analysis['risk_level'],
                  'confidence_data' => isset($analysis['confidence_score']) ? (string) $analysis['confidence_score'] : null,
                  'analysis_data' => $analysisDataJson,
                  'recommendations' => $recommendationsJson,
                  'predictions' => $predictionsJson,
                  'incident_count' => $incidentCount,
                  'station_id' => $stationId,
                  'created_at' => now(),
                  'updated_at' => now()
              ]);
            
            Log::info('Analysis stored successfully', [
                'analysis_id' => $analysisId,
                'station_id' => $stationId,
                'incident_count' => $incidentCount
            ]);
            
            return $analysisId;
            
        } catch (\Exception $e) {
            Log::error('Failed to store analysis: ' . $e->getMessage());
            // Don't throw, just log
            return null;
        }
    }
    
    /**
     * Get historical analyses from database
     */
    public function getHistoricalAnalyses()
    {
        try {
            $user = Auth::user();
            $stationOfficer = StationOfficer::where('user_id', $user->user_id)->first();
            
            if (!$stationOfficer) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }
            
              $analyses = DB::table('analysis')
                  ->where('station_id', $stationOfficer->station_id)
                  ->orderBy('created_at', 'desc')
                  ->limit(10)
                  ->get([
                      'analysis_id',
                      'sector',
                      'period',
                      'risk_level',
                      'confidence_data',
                      'incident_count',
                      'station_id',
                      'created_at'
                  ]);

              $analyses->transform(function ($analysis) {
                  $analysis->confidence_score = is_numeric($analysis->confidence_data)
                      ? (int) $analysis->confidence_data
                      : $analysis->confidence_data;

                  return $analysis;
              });
            
            return response()->json([
                'success' => true,
                'data' => $analyses
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching historical analyses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => $e->getMessage()
            ]);
        }
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

            return response()->json([
                'success' => true,
                'data' => $query->get(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error fetching action plan checklists: ' . $e->getMessage());

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
            Log::error('Error fetching recommendation suggestions: ' . $e->getMessage());

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

            $checklist->update([
                'status' => $validated['status'],
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
                'confirmation_notes' => $validated['confirmation_notes'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $checklist->fresh(['analysis', 'barangay', 'assignee', 'confirmer', 'suggestions', 'items.reviewer']),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error confirming action plan checklist: ' . $e->getMessage());

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

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Checklist item not found.',
                ], 422);
            }

            $itemStatus = $validated['status'];
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

            return response()->json([
                'success' => true,
                'data' => $checklist->fresh(['analysis', 'barangay', 'assignee', 'confirmer', 'suggestions', 'items.reviewer']),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error updating checklist item review: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
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
            
            if (!$analysis) {
                return response()->json([
                    'success' => false,
                    'message' => 'Analysis not found'
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
                'data' => $analysis
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching analysis: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
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
    try {
        $recommendationTitle = $request->input('recommendation_title');
        $recommendationDescription = $request->input('recommendation_description');
        $priority = $request->input('priority', 'Medium');
        $analysisId = $request->input('analysis_id');
        $actionPlanChecklistId = $request->input('action_plan_checklist_id');
        
        Log::info('=== EXPLORE RECOMMENDATION START ===');
        Log::info('Title: ' . $recommendationTitle);
        
        if (!$this->apiKey) {
            Log::error('No API key available for explore');
            throw new \Exception('API key not configured');
        }
        
        $analysis = $analysisId ? Analysis::query()->find($analysisId) : null;
        $actionPlanChecklist = $actionPlanChecklistId
            ? AiActionPlanChecklist::query()->find($actionPlanChecklistId)
            : null;

        $contextPayload = $this->buildExploreRecommendationContext($analysis, $actionPlanChecklist, [
            'recommendation_title' => $recommendationTitle,
            'recommendation_description' => $recommendationDescription,
            'priority' => $priority,
        ]);

        $prompt = "You are a fire safety implementation expert. Provide a detailed implementation plan for this recommendation.\n\n";
        $prompt .= "RECOMMENDATION: {$recommendationTitle}\n";
        $prompt .= "DESCRIPTION: {$recommendationDescription}\n";
        $prompt .= "PRIORITY: {$priority}\n\n";
        $prompt .= "GROUNDING CONTEXT:\n";
        $prompt .= json_encode($contextPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
        $prompt .= "Provide EXACTLY this format:\n\n";
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
        $prompt .= "TIMELINE: [Estimated timeline]";
        
        Log::info('Calling AI API for explore, prompt length: ' . strlen($prompt));
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => url('/'),
            'X-Title' => 'PyroSense Analytics'
        ])->timeout(60)->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'deepseek/deepseek-r1-distill-qwen-32b',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a fire safety implementation expert. Provide detailed, actionable implementation plans.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.5,
            'max_tokens' => 1000
        ]);
        
        Log::info('AI API Response Status: ' . $response->status());
        
        if ($response->successful()) {
            $json = $response->json();
            $content = $json['choices'][0]['message']['content'] ?? '';
            Log::info('AI Response received, length: ' . strlen($content));
            
            $parsedData = $this->parseExploreResponse($content);
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
                'saved_suggestion_id' => $savedSuggestion?->ai_recommendation_suggestion_id,
            ]);
        } else {
            $errorBody = $response->body();
            Log::error('AI API returned error: ' . $response->status() . ' - ' . $errorBody);
            throw new \Exception('API call failed: ' . $response->status());
        }
        
    } catch (\Exception $e) {
        Log::error('AI API failed, using fallback: ' . $e->getMessage());

        $analysis = $analysisId ? Analysis::query()->find($analysisId) : null;
        $actionPlanChecklist = $actionPlanChecklistId
            ? AiActionPlanChecklist::query()->find($actionPlanChecklistId)
            : null;
        $contextPayload = $this->buildExploreRecommendationContext($analysis, $actionPlanChecklist, [
            'recommendation_title' => $request->input('recommendation_title', 'this recommendation'),
            'recommendation_description' => $request->input('recommendation_description'),
            'priority' => $request->input('priority', 'Medium'),
        ]);
        $fallbackData = $this->getFallbackExploreData(
            $request->input('recommendation_title', 'this recommendation'),
            $request->input('priority', 'Medium')
        );
        $savedSuggestion = $this->storeRecommendationSuggestion(
            $analysis,
            $actionPlanChecklist,
            $request->input('recommendation_title', 'this recommendation'),
            $request->input('recommendation_description'),
            $request->input('priority', 'Medium'),
            $contextPayload,
            $fallbackData,
            'fallback_response'
        );

        return response()->json([
            'success' => true,
            'data' => $fallbackData,
            'source' => 'fallback',
            'note' => 'AI service temporarily unavailable, using standard recommendations',
            'saved_suggestion_id' => $savedSuggestion?->ai_recommendation_suggestion_id,
        ]);
    }
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
        string $aiResponse
    ): ?AiRecommendationSuggestion {
        try {
            return AiRecommendationSuggestion::create([
                'analysis_id' => $analysis?->analysis_id,
                'ai_action_plan_checklist_id' => $actionPlanChecklist?->ai_action_plan_checklist_id,
                'station_id' => $analysis?->station_id,
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
                'is_ai_generated' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to store AI recommendation suggestion: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build a weather summary from the station's city or marker for the current month.
     */
    private function fetchMonthlyWeatherContext($station, $days, $fallbackLocationName)
    {
        $apiKey = env('WEATHER_API_KEY', '1432615e15a14140963113518261203');

        if (!$apiKey || !$station) {
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

            if (!$response->successful()) {
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
            $avgTemp = $dayCount > 0 ? round(collect($forecastDaysData)->avg(fn($day) => $day['day']['avgtemp_c'] ?? null), 1) : ($current['temp_c'] ?? null);
            $avgHumidity = $dayCount > 0 ? round(collect($forecastDaysData)->avg(fn($day) => $day['day']['avghumidity'] ?? null)) : ($current['humidity'] ?? null);
            $avgWind = $dayCount > 0 ? round(collect($forecastDaysData)->avg(fn($day) => $day['day']['maxwind_kph'] ?? null), 1) : ($current['wind_kph'] ?? null);
            $rainyDays = collect($forecastDaysData)->filter(fn($day) => ($day['day']['daily_chance_of_rain'] ?? 0) >= 50 || ($day['day']['totalprecip_mm'] ?? 0) > 0)->count();
            $hotDays = collect($forecastDaysData)->filter(fn($day) => ($day['day']['maxtemp_c'] ?? 0) >= 32)->count();
            $dominantCondition = collect($forecastDaysData)
                ->map(fn($day) => $day['day']['condition']['text'] ?? null)
                ->filter()
                ->countBy()
                ->sortDesc()
                ->keys()
                ->first() ?? ($current['condition']['text'] ?? 'Unknown');

            $weatherRiskScore = 0;
            if (($avgTemp ?? 0) >= 31) $weatherRiskScore += 2;
            if (($avgHumidity ?? 100) <= 55) $weatherRiskScore += 2;
            if (($avgWind ?? 0) >= 20) $weatherRiskScore += 1;
            if ($rainyDays === 0 && $dayCount > 0) $weatherRiskScore += 1;

            $weatherRiskLabel = 'Low';
            if ($weatherRiskScore >= 5) {
                $weatherRiskLabel = 'High';
            } elseif ($weatherRiskScore >= 3) {
                $weatherRiskLabel = 'Moderate';
            }

            return [
                'query_location' => $locationQuery,
                'coverage' => 'Current month context using available ' . max($dayCount, 1) . '-day weather sample',
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
            Log::warning('Failed to fetch weather context for analytics: ' . $e->getMessage());
            return null;
        }
    }

    private function resolveWeatherLocationQuery($station, $fallbackLocationName)
    {
        $cityName = null;
        if (!empty($station->address_id)) {
            $address = DB::table('full_addresses')
                ->leftJoin('barangays', 'full_addresses.barangay_id', '=', 'barangays.barangay_id')
                ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
                ->where('full_addresses.address_id', $station->address_id)
                ->select('cities.city_name')
                ->first();

            $cityName = $address->city_name ?? null;
        }

        if ($cityName) {
            return $cityName . ', Pangasinan, Philippines';
        }

        if (!empty($station->marker_id)) {
            $markerTitle = DB::table('markers')->where('marker_id', $station->marker_id)->value('title');
            if ($markerTitle) {
                return $markerTitle . ', Philippines';
            }
        }

        return $fallbackLocationName . ', Philippines';
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

        return ucfirst(implode(', ', $parts)) . ". Weather fire-risk outlook is {$weatherRiskLabel}.";
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

        if (!empty($drivers)) {
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

        if (!empty($weather['summary'])) {
            $drivers[] = $weather['summary'];
        }

        if (!empty($predictionContext['likely_peak_hours'])) {
            $drivers[] = 'observed monthly peak hours: ' . implode(', ', $predictionContext['likely_peak_hours']);
        }

        if (!empty($data['mitigation_context']['summary'])) {
            $drivers[] = $data['mitigation_context']['summary'];
        }

        $outlook = $data['risk_level'] ?? 'Medium';
        if (!empty($modelPrediction['risk_level'])) {
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
        if (empty($likelyPeakHours) && isset($data['peak_hour']) && $data['peak_hour'] !== null) {
            $likelyPeakHours = [str_pad((string) $data['peak_hour'], 2, '0', STR_PAD_LEFT) . ':00'];
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
                if (!empty($driver['summary'])) {
                    $drivers[] = $driver['summary'];
                }
            }
        }

        if (!empty($data['mitigation_context']['risk_reduction_percent'])) {
            $summary .= " Confirmed mitigation actions are currently reducing the projected risk context by about {$data['mitigation_context']['risk_reduction_percent']}%.";
        }

        if (!is_null($predictedDamage)) {
            $summary .= " Estimated possible damage exposure for the forecast window is around PHP " . number_format($predictedDamage, 2) . '.';
        }

        return [
            'summary' => $summary,
            'forecast_window' => $forecastWindow,
            'outlook' => $outlook,
            'predicted_fire_incidents' => $predictedFireCount,
            'predicted_damage' => $predictedDamage,
            'likely_peak_hours' => $likelyPeakHours,
            'drivers' => array_values(array_unique(array_filter($drivers))),
            'weather_context' => $weather ?: null,
            'model_prediction' => $modelPrediction,
            'mitigation_context' => $data['mitigation_context'] ?? null,
            'prediction_scope' => $data['prediction_scope'] ?? 'current_month',
            'prediction_context' => $predictionContext ?: null,
        ];
    }

    private function buildPredictionContext(array $teamIds, string $predictionScope): array
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

        $currentMonthIncidents = $this->fetchIncidentsForPredictionWindow($teamIds, $currentMonthStart, $now->copy()->endOfDay());
        $previousMonthIncidents = $this->fetchIncidentsForPredictionWindow($teamIds, $previousMonthStart, $previousMonthEnd);
        $currentMonthHourlyData = array_fill(0, 24, 0);

        foreach ($currentMonthIncidents as $incident) {
            try {
                $hour = (int) Carbon::parse($incident->created_at)->format('H');
                $currentMonthHourlyData[$hour]++;
            } catch (\Throwable $e) {
            }
        }

        arsort($currentMonthHourlyData);
        $topHours = array_keys(array_filter($currentMonthHourlyData));
        $likelyPeakHours = collect(array_slice($topHours, 0, 3))
            ->map(fn($hour) => str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00')
            ->values()
            ->all();

        return [
            'scope' => $predictionScope,
            'scope_label' => $predictionScope === 'next_month' ? 'Predict Next Month' : 'Predict Current Month',
            'target_month_label' => $targetMonthStart->format('F Y'),
            'target_period_label' => $targetMonthStart->format('M j') . ' - ' . $targetMonthEnd->format('M j, Y'),
            'current_month_total_incidents' => $currentMonthIncidents->count(),
            'current_month_fire_incidents' => $currentMonthIncidents->where('incident_type_id', 1)->count(),
            'previous_month_total_incidents' => $previousMonthIncidents->count(),
            'previous_month_fire_incidents' => $previousMonthIncidents->where('incident_type_id', 1)->count(),
            'current_month_hourly_data' => array_values($currentMonthHourlyData),
            'likely_peak_hours' => $likelyPeakHours,
        ];
    }

    private function fetchIncidentsForPredictionWindow(array $teamIds, Carbon $start, Carbon $end)
    {
        if (empty($teamIds)) {
            return collect();
        }

        return Incident::query()
            ->whereIn('team_id', $teamIds)
            ->whereBetween('created_at', [$start, $end])
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
private function parseExploreResponse($content)
{
    $steps = [];
    $resources = [];
    $timeline = "Implementation timeline varies based on resources and complexity";
    
    $lines = explode("\n", $content);
    $currentSection = null;
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Detect sections
        if (preg_match('/steps?/i', $line) && strpos($line, '1.') !== false) {
            $currentSection = 'steps';
        } elseif (preg_match('/resources?/i', $line) && (strpos($line, '•') !== false || strpos($line, '-') !== false)) {
            $currentSection = 'resources';
        } elseif (preg_match('/timeline/i', $line)) {
            $currentSection = 'timeline';
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
        elseif ($currentSection === 'timeline' && !empty($line) && strlen($line) > 10 && strpos($line, '•') === false && strpos($line, '1.') === false) {
            $timeline = $line;
        }
    }
    
    // Ensure we have at least some steps
    if (empty($steps)) {
        $steps = [
            "Conduct a thorough assessment of current practices",
            "Develop a detailed action plan with specific timelines and responsibilities",
            "Allocate necessary resources and personnel for implementation",
            "Train staff on new procedures",
            "Implement the recommendation in phases",
            "Monitor progress and adjust strategies as needed",
            "Review and document outcomes for future reference"
        ];
    }
    
    if (empty($resources)) {
        $resources = [
            "Dedicated personnel for implementation",
            "Training materials and sessions",
            "Monitoring and evaluation tools",
            "Budget allocation for necessary equipment"
        ];
    }
    
    return [
        'steps' => array_slice($steps, 0, 7),
        'resources_needed' => array_slice($resources, 0, 5),
        'estimated_timeline' => $timeline
    ];
}

/**
 * Get fallback data when AI is unavailable
 */
private function getFallbackExploreData($recommendationTitle, $priority)
{
    $priorityLevel = strtolower($priority);
    $urgencyText = $priorityLevel === 'critical' ? 'Immediate' : ($priorityLevel === 'high' ? 'Urgent' : 'Standard');
    
    return [
        'steps' => [
            "1. Assess current situation and identify specific gaps related to '{$recommendationTitle}'",
            "2. Develop a detailed {$urgencyText} action plan with clear milestones and responsible personnel",
            "3. Allocate necessary budget, equipment, and personnel resources",
            "4. Conduct training sessions for all relevant staff members",
            "5. Implement the recommendation starting with highest priority areas",
            "6. Monitor progress weekly and adjust strategies as needed",
            "7. Document results and best practices for future reference"
        ],
        'resources_needed' => [
            "Project lead and implementation team",
            "Training materials and workshops",
            "Monitoring and reporting tools",
            "Communication equipment if needed",
            "Budget for any new equipment or software"
        ],
        'estimated_timeline' => $priorityLevel === 'critical' 
            ? "1-2 weeks for initial implementation, with daily monitoring" 
            : ($priorityLevel === 'high' 
                ? "2-4 weeks for full implementation, with weekly reviews" 
                : "1-3 months for complete implementation, with monthly progress reviews")
    ];
}
}
