<?php
// app/Http/Controllers/RespondedTeamController.php

namespace App\Http\Controllers;

use App\Models\RespondedTeam;
use App\Models\Personnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RespondedTeamController extends Controller
{
    /**
     * Store a newly created responded team member
     */
    public function store(Request $request)
    {
        try {
            // Log the incoming request for debugging
            Log::info('RespondedTeam store request:', $request->all());
            
            $validated = $request->validate([
                'incident_id' => 'required|exists:incident_report,id', // FIXED: changed from incident_id to id
                'personnel_id' => 'required|exists:personnels,id',
                'role' => 'nullable|string|in:team_leader,firefighter,driver,medic,commander',
                'status' => 'nullable|string|in:assigned,en_route,on_scene,completed,returned'
            ]);

            // Start transaction
            DB::beginTransaction();

            // Create responded team record
            $respondedTeam = RespondedTeam::create([
                'incident_id' => $validated['incident_id'],
                'personnel_id' => $validated['personnel_id'],
                'role' => $validated['role'] ?? 'firefighter',
                'status' => $validated['status'] ?? 'assigned',
                'assigned_at' => now()
            ]);

            // Update personnel availability to 'responding'
            Personnel::where('id', $validated['personnel_id'])
                ->update(['availability' => 'responding']);

            DB::commit();

            // Load relationships
            $respondedTeam->load(['personnel']);

            return response()->json([
                'success' => true,
                'message' => 'Personnel assigned successfully',
                'data' => $respondedTeam
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning personnel: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign personnel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all responded team members for an incident report
     */
    public function getByIncidentReport($incidentReportId)
    {
        try {
            $team = RespondedTeam::with(['personnel'])
                ->where('incident_id', $incidentReportId) // FIXED: changed from incident_report_id to incident_id
                ->get();

            return response()->json([
                'success' => true,
                'data' => $team
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching responded team: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch responded team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update team member status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:assigned,en_route,on_scene,completed,returned'
            ]);

            $teamMember = RespondedTeam::findOrFail($id);
            
            // Update timestamps based on status
            if ($validated['status'] === 'en_route' && !$teamMember->arrived_at) {
                // Already en_route, no timestamp change
            } elseif ($validated['status'] === 'on_scene' && !$teamMember->arrived_at) {
                $teamMember->arrived_at = now();
            } elseif ($validated['status'] === 'completed' && !$teamMember->completed_at) {
                $teamMember->completed_at = now();
                
                // Update personnel availability back to 'available'
                Personnel::where('id', $teamMember->personnel_id)
                    ->update(['availability' => 'available']);
            }

            $teamMember->status = $validated['status'];
            $teamMember->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $teamMember
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove team member from response
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $teamMember = RespondedTeam::findOrFail($id);
            
            // Update personnel availability back to 'available'
            Personnel::where('id', $teamMember->personnel_id)
                ->update(['availability' => 'available']);
            
            // Delete the record
            $teamMember->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Team member removed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error removing team member: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove team member',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}