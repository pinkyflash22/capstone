<?php
// app/Http/Controllers/Firefighter/IncidentController.php

namespace App\Http\Controllers\Firefighter;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\IncidentReport;
use App\Models\RespondedTeam;
use App\Models\Personnel;
use App\Models\Marker;
use App\Models\Coordinate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class IncidentController extends Controller
{
    /**
     * Display incidents page for firefighter
     */
    public function index()
    {
        try {
            $user = Auth::user();
            
            // Try to find personnel record for this user
            $personnel = $this->findPersonnelRecord($user);
            
            // Get all incidents with their markers
            $allIncidents = Incident::with('marker')
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Get available incidents (reported status, not assigned)
            $availableIncidents = Incident::with('marker')
                ->where('status', 'reported')
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Initialize assigned and completed as empty collections
            $assignedIncidents = collect([]);
            $completedIncidents = collect([]);
            
            // If personnel found, get their specific incidents
            if ($personnel) {
                $incidentTable = Schema::hasTable('incidents') ? 'incidents' : 'incident';

                // Get incidents assigned to this firefighter
                $assignedIncidents = \App\Models\Scopes\ExcludeSpamIncidents::filter(DB::table($incidentTable), $incidentTable)
                    ->join('incident_report', $incidentTable . '.incident_id', '=', 'incident_report.incident_id')
                    ->join('responded_team', 'incident_report.id', '=', 'responded_team.incident_id')
                    ->where('responded_team.personnel_id', $personnel->id)
                    ->whereIn('responded_team.status', ['assigned', 'en_route', 'on_scene'])
                    ->select(
                        $incidentTable . '.*',
                        'incident_report.id as report_id',
                        'incident_report.severity',
                        'incident_report.victims',
                        'incident_report.dateEnded',
                        'responded_team.status as response_status',
                        'responded_team.role',
                        'responded_team.assigned_at'
                    )
                    ->orderBy('responded_team.assigned_at', 'desc')
                    ->get();

                // Get completed incidents for this firefighter
                $completedIncidents = \App\Models\Scopes\ExcludeSpamIncidents::filter(DB::table($incidentTable), $incidentTable)
                    ->join('incident_report', $incidentTable . '.incident_id', '=', 'incident_report.incident_id')
                    ->join('responded_team', 'incident_report.id', '=', 'responded_team.incident_id')
                    ->where('responded_team.personnel_id', $personnel->id)
                    ->whereIn('responded_team.status', ['completed', 'returned'])
                    ->select(
                        $incidentTable . '.*',
                        'incident_report.id as report_id',
                        'incident_report.severity',
                        'incident_report.victims',
                        'incident_report.dateEnded',
                        'responded_team.status as response_status',
                        'responded_team.completed_at'
                    )
                    ->orderBy('responded_team.completed_at', 'desc')
                    ->limit(10)
                    ->get();
            }
            
            // If no available incidents, use all incidents for demo purposes
            if ($availableIncidents->isEmpty() && $allIncidents->isNotEmpty()) {
                $availableIncidents = $allIncidents;
            }
            
            // Convert to collections with markers loaded
            $assignedIncidents = $this->loadMarkersForIncidents($assignedIncidents);
            $completedIncidents = $this->loadMarkersForIncidents($completedIncidents);
            
            return view('firefighters.incidents', compact(
                'personnel',
                'availableIncidents',
                'assignedIncidents',
                'completedIncidents'
            ));

        } catch (\Exception $e) {
            Log::error('IncidentController error: ' . $e->getMessage());
            
            // Return empty collections on error
            return view('firefighters.incidents', [
                'personnel' => null,
                'availableIncidents' => collect([]),
                'assignedIncidents' => collect([]),
                'completedIncidents' => collect([])
            ]);
        }
    }

    /**
     * Find personnel record for user
     */
    private function findPersonnelRecord($user)
    {
        // Method 1: Try direct account_id in users table
        if (isset($user->account_id) && !empty($user->account_id)) {
            $personnel = Personnel::where('account_id', $user->account_id)->first();
            if ($personnel) return $personnel;
        }
        
        // Method 2: Try through accounts table
        $personnel = Personnel::whereHas('account', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->first();
        if ($personnel) return $personnel;
        
        // Method 3: Try by email
        $personnel = Personnel::where('email', $user->email)->first();
        if ($personnel) return $personnel;
        
        // Method 4: Try by name
        $personnel = Personnel::where('first_name', $user->name)
            ->orWhere('last_name', $user->name)
            ->first();
        if ($personnel) return $personnel;
        
        // Method 5: Just get first personnel for demo
        return Personnel::first();
    }

    /**
     * Load markers for incidents from DB results
     */
    private function loadMarkersForIncidents($incidents)
    {
        $incidentsWithMarkers = collect([]);
        
        foreach ($incidents as $incident) {
            // Convert to object if it's an array
            $incidentObj = is_object($incident) ? $incident : (object) $incident;
            
            // Load marker
            $marker = Marker::where('marker_id', $incidentObj->marker_id ?? $incidentObj->id ?? null)->first();
            $incidentObj->marker = $marker;
            
            // Convert timestamps to Carbon instances
            if (isset($incidentObj->alarmdatetime) && is_string($incidentObj->alarmdatetime)) {
                $incidentObj->alarmdatetime = \Carbon\Carbon::parse($incidentObj->alarmdatetime);
            }
            if (isset($incidentObj->assigned_at) && is_string($incidentObj->assigned_at)) {
                $incidentObj->assigned_at = \Carbon\Carbon::parse($incidentObj->assigned_at);
            }
            if (isset($incidentObj->completed_at) && is_string($incidentObj->completed_at)) {
                $incidentObj->completed_at = \Carbon\Carbon::parse($incidentObj->completed_at);
            }
            if (isset($incidentObj->created_at) && is_string($incidentObj->created_at)) {
                $incidentObj->created_at = \Carbon\Carbon::parse($incidentObj->created_at);
            }
            
            $incidentsWithMarkers->push($incidentObj);
        }
        
        return $incidentsWithMarkers;
    }

    /**
     * Accept/respond to an incident
     */
    public function accept(Request $request, $incidentId)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $personnel = $this->findPersonnelRecord($user);
            
            if (!$personnel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Personnel record not found. Please contact administrator.'
                ], 404);
            }

            // Check if personnel is available
            if ($personnel->availability !== 'available') {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not available to respond to incidents'
                ], 400);
            }

            // Find the incident
            $incident = Incident::find($incidentId);
            if (!$incident) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incident not found'
                ], 404);
            }

            // Get or create incident report
            $incidentReport = IncidentReport::firstOrCreate(
                ['incident_id' => $incidentId],
                [
                    'victims' => 0,
                    'severity' => 'medium',
                    'dateEnded' => null
                ]
            );

            // Check if already assigned
            $existingAssignment = RespondedTeam::where('incident_id', $incidentReport->id)
                ->where('personnel_id', $personnel->id)
                ->first();

            if ($existingAssignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already assigned to this incident'
                ], 400);
            }

            // Assign to responded team
            $respondedTeam = RespondedTeam::create([
                'incident_id' => $incidentReport->id,
                'personnel_id' => $personnel->id,
                'role' => $request->input('role', $personnel->rank ?? 'firefighter'),
                'status' => 'assigned',
                'assigned_at' => now()
            ]);

            // Update incident status
            $incident->status = 'responding';
            $incident->save();

            // Update personnel availability
            $personnel->availability = 'responding';
            $personnel->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'You have successfully accepted the incident',
                'data' => [
                    'incident' => $incident,
                    'assignment' => $respondedTeam
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Accept incident error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept incident: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update response status
     */
    public function updateStatus(Request $request, $incidentId)
    {
        try {
            $user = Auth::user();
            $personnel = $this->findPersonnelRecord($user);
            
            if (!$personnel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Personnel record not found'
                ], 404);
            }
            
            $validated = $request->validate([
                'status' => 'required|in:en_route,on_scene,completed,returned'
            ]);

            $incidentReport = IncidentReport::where('incident_id', $incidentId)->first();
            
            if (!$incidentReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incident report not found'
                ], 404);
            }
            
            $assignment = RespondedTeam::where('incident_id', $incidentReport->id)
                ->where('personnel_id', $personnel->id)
                ->first();

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment not found'
                ], 404);
            }

            // Update status with appropriate timestamps
            $assignment->status = $validated['status'];
            
            if ($validated['status'] === 'on_scene' && !$assignment->arrived_at) {
                $assignment->arrived_at = now();
            } elseif (in_array($validated['status'], ['completed', 'returned'])) {
                $assignment->completed_at = now();
                
                // Check if all responders are done
                $pendingResponders = RespondedTeam::where('incident_id', $incidentReport->id)
                    ->whereNotIn('status', ['completed', 'returned'])
                    ->count();
                
                if ($pendingResponders === 0) {
                    $incident = Incident::find($incidentId);
                    if ($incident) {
                        $incident->status = 'responded';
                        $incident->save();
                    }
                }
                
                // Update personnel availability
                $personnel->availability = 'available';
                $personnel->save();
            }

            $assignment->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $assignment
            ]);

        } catch (\Exception $e) {
            Log::error('Update status error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get incident details
     */
    public function details($incidentId)
    {
        try {
            $incident = Incident::with('marker')->find($incidentId);
            
            if (!$incident) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incident not found'
                ], 404);
            }

            // Get responders
            $responders = DB::table('responded_team')
                ->join('incident_report', 'responded_team.incident_id', '=', 'incident_report.id')
                ->join('personnel', 'responded_team.personnel_id', '=', 'personnel.id')
                ->where('incident_report.incident_id', $incidentId)
                ->select(
                    'personnel.*',
                    'responded_team.status as response_status',
                    'responded_team.role',
                    'responded_team.assigned_at',
                    'responded_team.arrived_at',
                    'responded_team.completed_at'
                )
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'incident' => $incident,
                    'responders' => $responders
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Details error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get incident details'
            ], 500);
        }
    }
}
