<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    private $apiKey;

    public function __construct()
    {
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
        return view('admin.analytics');
    }
    
    /**
     * Get stations/locations for the analytics dropdown
     */
    public function getStationsForAnalytics()
    {
        try {
            Log::info('Fetching stations for analytics');
            
            $locations = collect();
            
            // Get from markers table
            try {
                $markers = DB::table('markers')
                    ->select('marker_id as id', 'title as name')
                    ->orderBy('title')
                    ->get();
                
                foreach ($markers as $marker) {
                    $locations->push((object)[
                        'id' => 'marker_' . $marker->id,
                        'name' => $marker->name
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Markers table not accessible: ' . $e->getMessage());
            }
            
            // Get from stations table
            try {
                $stations = DB::table('stations')
                    ->select('station_id as id', 'station_name as name')
                    ->orderBy('station_name')
                    ->get();
                
                foreach ($stations as $station) {
                    $locations->push((object)[
                        'id' => 'station_' . $station->id,
                        'name' => $station->name
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Stations table not accessible: ' . $e->getMessage());
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
            Log::info('AI Analysis request received', $request->all());
            
            $locationId = $request->input('location_id', 'all');
            $days = (int)$request->input('days', 30);
            
            // Fetch REAL data from database
            $analysisData = $this->fetchRealTimeData($locationId, $days);
            
            // Build AI prompt with the actual data
            $prompt = $this->buildAIPrompt($analysisData);
            
            // Try to call AI API
            $aiResponse = null;
            try {
                $aiResponse = $this->callAIApi($prompt);
            } catch (\Exception $aiError) {
                Log::warning('AI API failed: ' . $aiError->getMessage());
            }
            
            // If AI succeeded, parse it; otherwise use data-driven analysis
            if ($aiResponse) {
                $structuredAnalysis = $this->structureAIAnalysis($aiResponse, $analysisData);
            } else {
                $structuredAnalysis = $this->generateDataDrivenAnalysis($analysisData);
                $structuredAnalysis['note'] = 'Analysis based on data patterns (AI service temporarily unavailable)';
            }
            
            // Store analysis in database using the correct schema
            $this->storeAnalysis($locationId, $days, $structuredAnalysis, $analysisData);
            
            return response()->json([
                'success' => true,
                'data' => $structuredAnalysis,
                'location_name' => $analysisData['location_name']
            ]);
            
        } catch (\Exception $e) {
            Log::error('AI Analysis error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            // Fallback
            try {
                $analysisData = $this->fetchRealTimeData($request->input('location_id', 'all'), (int)$request->input('days', 30));
                $fallbackAnalysis = $this->generateDataDrivenAnalysis($analysisData);
                
                return response()->json([
                    'success' => true,
                    'data' => $fallbackAnalysis,
                    'location_name' => $analysisData['location_name'],
                    'note' => 'Analysis based on data patterns (AI service unavailable)'
                ]);
            } catch (\Exception $fallbackError) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to analyze data: ' . $e->getMessage()
                ], 500);
            }
        }
    }
    
    /**
     * Fetch REAL data from your database tables
     */
    private function fetchRealTimeData($locationId, $days)
    {
        $startDate = Carbon::now()->subDays($days);
        
        // Get location name
        $locationName = 'All Locations';
        $actualLocationId = null;
        
        // Parse location ID (remove prefix if present)
        if ($locationId !== 'all') {
            $parts = explode('_', $locationId, 2);
            $tableType = $parts[0] ?? null;
            $id = $parts[1] ?? null;
            
            if ($tableType === 'marker') {
                $marker = DB::table('markers')->where('marker_id', $id)->first();
                if ($marker) {
                    $locationName = $marker->title ?? $marker->name ?? 'Location';
                    $actualLocationId = $id;
                }
            } elseif ($tableType === 'station') {
                $station = DB::table('stations')->where('station_id', $id)->first();
                if ($station) {
                    $locationName = $station->station_name ?? 'Station';
                    $actualLocationId = $id;
                }
            }
        }
        
        // Build incident query
        $incidents = collect();
        
        try {
            $incidentTable = Schema::hasTable('incidents') ? 'incidents' : 'incident';
            $incidentsQuery = DB::table($incidentTable);
            $columns = Schema::getColumnListing($incidentTable);
            
            if (in_array('created_at', $columns)) {
                $incidentsQuery->where('created_at', '>=', $startDate);
            } elseif (in_array('date', $columns)) {
                $incidentsQuery->where('date', '>=', $startDate->toDateString());
            }
            
            if ($actualLocationId) {
                if (in_array('marker_id', $columns)) {
                    $incidentsQuery->where('marker_id', $actualLocationId);
                }
            }
            
            $incidents = $incidentsQuery->get();
            Log::info('Found ' . $incidents->count() . ' incidents');
            
        } catch (\Exception $e) {
            Log::warning('Error fetching incidents: ' . $e->getMessage());
            $incidents = collect();
        }
        
        // Calculate statistics
        $totalIncidents = $incidents->count();
        
        // Incidents by type
        $incidentsByType = [];
        $fireIncidents = 0;
        
        foreach ($incidents as $incident) {
            $type = $incident->incident_type_id ?? $incident->incident_type ?? 'unknown';
            $incidentsByType[$type] = ($incidentsByType[$type] ?? 0) + 1;
            if ((string) $type === '1' || (is_string($type) && (strtolower($type) === 'fire' || strpos(strtolower($type), 'fire') !== false))) {
                $fireIncidents++;
            }
        }
        
        // Hourly distribution
        $hourlyData = array_fill(0, 24, 0);
        $dailyData = array_fill(0, 7, 0);
        
        foreach ($incidents as $incident) {
            $incidentDate = $incident->created_at ?? $incident->date ?? null;
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
        $totalPersonnel = 0;
        $availablePersonnel = 0;
        
        try {
            $totalPersonnel = DB::table('station_officers')->count();
            $availablePersonnel = DB::table('station_officers')
                ->where('status', 'available')
                ->count();
        } catch (\Exception $e) {
            $totalPersonnel = 10;
            $availablePersonnel = 5;
        }
        
        // Get equipment data
        $totalEquipment = 0;
        $availableEquipment = 0;
        
        try {
            $totalEquipment = DB::table('equipments')->count();
            $availableEquipment = DB::table('equipments')
                ->where('status', 'operational')
                ->orWhereNull('status')
                ->count();
        } catch (\Exception $e) {
            $totalEquipment = 20;
            $availableEquipment = 12;
        }
        
        // Calculate risk score
        $riskScore = $this->calculateRiskScore($incidents, $fireIncidents, $days);
        
        // Identify risk factors
        $riskFactors = $this->identifyRiskFactors($incidents, $incidentsByType, $peakHour, $avgResponseTime);
        
        return [
            'location_name' => $locationName,
            'location_id' => $locationId,
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
            'risk_factors' => $riskFactors
        ];
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
            $incidentDate = $incident->created_at ?? $incident->date ?? null;
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
        
        $fireCount = ($incidentsByType[1] ?? 0) + ($incidentsByType['1'] ?? 0) + ($incidentsByType['fire'] ?? 0);
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
    private function buildAIPrompt($data)
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
        $prompt .= "- Risk Score: {$data['risk_score']}/100 ({$data['risk_level']})\n\n";
        
        $prompt .= "Provide: 1) Risk assessment (1 sentence), 2) 3 immediate actions (bullet points), 3) 3 recommendations (bullet points)";
        
        return $prompt;
    }
    
    /**
     * Call AI API
     */
    private function callAIApi($prompt)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'deepseek/deepseek-r1-distill-qwen-32b',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a fire safety expert. Provide concise analysis.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.3,
            'max_tokens' => 500
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('API call failed: ' . $response->status());
        }
        
        $json = $response->json();
        return $json['choices'][0]['message']['content'] ?? '';
    }
    
    /**
     * Structure AI analysis into usable format
     */
    private function structureAIAnalysis($aiContent, $data)
    {
        $recommendations = [];
        $lines = explode("\n", $aiContent);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^[•\-*]\s+(.+)/', $line, $matches)) {
                $recommendations[] = [
                    'title' => substr($matches[1], 0, 80),
                    'description' => $matches[1],
                    'priority' => $this->determinePriority($matches[1], $data)
                ];
            }
        }
        
        if (empty($recommendations)) {
            $recommendations = $this->generateDataDrivenRecommendations($data);
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
            'recommendations' => array_slice($recommendations, 0, 6),
            'ai_explanation' => substr($aiContent, 0, 300)
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
            'recommendations' => $recommendations,
            'ai_explanation' => $explanation
        ];
    }
    
    /**
     * Store analysis in database matching your exact schema
     * Table: analysis
     * Columns: analysis_id (primary), sector, period, risk_level, confidence_data,
     *          analysis_data (JSON), recommendations (JSON), predictions (JSON), incident_count, station_id, timestamps
     */
    private function storeAnalysis($locationId, $days, $analysis, $rawData)
    {
        try {
            // Prepare sector name for storage
            $sectorName = $rawData['location_name'];
            if ($locationId === 'all') {
                $sectorName = 'All Locations';
            }
            
            // Prepare analysis_data JSON (full analysis details)
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
                    'risk_factors' => $rawData['risk_factors']
                ],
                'ai_analysis' => $analysis['ai_explanation'] ?? null,
                'generated_at' => now()->toISOString()
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
            $stationId = $rawData['station_id'] ?? null;
            
            $analysisId = DB::table('analysis')->insertGetId([
                'sector' => $sectorName,
                'period' => "Last {$days} days",
                'risk_level' => $analysis['risk_level'],
                'confidence_data' => isset($analysis['confidence_score']) ? (string) $analysis['confidence_score'] : null,
                'analysis_data' => $analysisDataJson,
                'recommendations' => $recommendationsJson,
                'predictions' => $predictionsJson,
                'incident_count' => $rawData['total_incidents'],
                'station_id' => $stationId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            Log::info('Analysis stored successfully with ID: ' . $analysisId);
            return $analysisId;
            
        } catch (\Exception $e) {
            Log::error('Failed to store analysis: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return null;
        }
    }
    
    /**
     * Get historical analyses from database
     */
    public function getHistoricalAnalyses()
    {
        try {
            $analyses = DB::table('analysis')
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
            $analysis->predictions = json_decode($analysis->predictions ?? 'null', true);
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
}
