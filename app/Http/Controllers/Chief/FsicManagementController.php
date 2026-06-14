<?php

namespace App\Http\Controllers\Chief;

use App\Http\Controllers\Controller;
use App\Models\FsicRequest;
use App\Models\FsicInspection;
use App\Models\FsicApproval;
use App\Models\InspectionChecklist;
use App\Models\StationOfficer;
use App\Models\User;
use App\Models\Province;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Support\UserActivityLogger;

class FsicManagementController extends Controller
{
    protected function getAuthenticatedStationOfficer(): ?StationOfficer
    {
        return StationOfficer::where('user_id', Auth::user()->user_id)->first();
    }

    public function index()
    {
        $pendingRequests = FsicRequest::where('status', 'pending')
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();
        
        $forInspectionRequests = FsicRequest::where('status', 'for_inspection')
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();
        
        $inspectedRequests = FsicRequest::where('status', 'inspected')
            ->with(['user', 'inspection'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $approvedRequests = FsicRequest::where('status', 'approved')
            ->with(['user', 'approval.approver.user.role', 'approval.approver.station'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $rejectedRequests = FsicRequest::where('status', 'rejected')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $allRequests = FsicRequest::with('user')
            ->orderBy('created_at', 'desc')
            ->get();
        $provinces = Province::orderBy('province_name')->get();
        $users = User::where('role_id', 4)->orderBy('first_name')->get(); // Get all citizens
        $chiefStationOfficer = $this->getAuthenticatedStationOfficer();
        $availableInspectors = collect();

        if ($chiefStationOfficer?->station_id) {
            $availableInspectors = StationOfficer::with('user')
                ->where('station_id', $chiefStationOfficer->station_id)
                ->where('status', 'available')
                ->where('user_id', '!=', Auth::user()->user_id)
                ->orderBy('rank')
                ->orderBy('station_officer_id')
                ->get();
        }

        $pendingCount = $pendingRequests->count();
        $forInspectionCount = $forInspectionRequests->count();
        
        return view('chief.fsic', compact(
            'pendingRequests',
            'forInspectionRequests',
            'inspectedRequests',
            'approvedRequests',
            'rejectedRequests',
            'allRequests',
            'pendingCount',
            'forInspectionCount',
            'provinces',
            'users',
            'availableInspectors'
        ));
    }
    /**
 * Create a new applicant (user) on the fly
 */
public function createApplicant(Request $request)
{
    try {
        DB::beginTransaction();
        
        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'email' => 'required|email|unique:users,email',
        ]);
        
        // Generate account code (similar to RegisterController)
        $roleCode = 'CIT';
        $lastUser = User::where('account_code', 'LIKE', $roleCode . '-%')
            ->orderBy('user_id', 'desc')
            ->first();
        
        if ($lastUser) {
            $lastNumber = (int) substr($lastUser->account_code, strlen($roleCode) + 1);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        $accountCode = $roleCode . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        
        // Create user with role_id = 4 (Citizen)
        $user = User::create([
            'account_code' => $accountCode,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'contact_number' => $request->contact_number,
            'role_id' => 4, // Citizen role
            'password' => Hash::make('pyrosense123'),
            'address_id' => null,
            'phone_verified_at' => now(), // Auto-verify phone for admin-created users
            'is_verified' => true,
        ]);
        
        DB::commit();
        UserActivityLogger::log('created applicant', Auth::user()?->user_id, $request);
        
        return response()->json([
            'success' => true,
            'message' => 'Applicant created successfully',
            'user_id' => $user->user_id,
            'account_code' => $accountCode
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Create applicant error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to create applicant: ' . $e->getMessage()
        ], 500);
    }
}


/**
 * Store FSIC request (for station admin)
 */
public function store(Request $request)
{
    try {
        DB::beginTransaction();
        
        $fsicRequest = FsicRequest::create([
            'user_id' => $request->user_id,
            'request_code' => $request->request_code,
            'date_requested' => $request->date_requested,
            'business_name' => $request->business_name,
            'owner_name' => $request->owner_name,
            'business_type' => $request->business_type,
            'building_address' => $request->building_address,
            'barangay' => $request->barangay,
            'city' => $request->city,
            'province' => $request->province,
            'dti_sec_registration_no' => $request->dti_sec_registration_no,
            'mayors_permit_no' => $request->mayors_permit_no,
            'tin_number' => $request->tin_number,
            'status' => 'pending'
        ]);
        
        DB::commit();
        UserActivityLogger::log('created fsic request', Auth::user()?->user_id, $request);
        
        return response()->json([
            'success' => true,
            'message' => 'FSIC request submitted successfully!',
            'data' => $fsicRequest
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Store FSIC error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to submit request: ' . $e->getMessage()
        ], 500);
    }
}
    public function show($id)
    {
        try {
            $request = FsicRequest::with([
                'user',
                'inspection.checklist',
                'inspection.inspector.user',
                'approval.approver.user.role',
                'approval.approver.station'
            ])->findOrFail($id);
            $inspection = $request->inspection;
            $checklist = $inspection?->checklist;
            $checklistLabels = [
                'fire_protection' => 'Fire Protection',
                'alarm_systems' => 'Alarm Systems',
                'standard_material_used' => 'Standard Material Used',
                'electrical' => 'Electrical',
                'housekeeping' => 'Housekeeping',
                'hazards' => 'Hazards',
                'planning' => 'Planning',
            ];
            $failedChecklistItems = [];

            if ($checklist) {
                foreach ($checklistLabels as $column => $label) {
                    if ((int) ($checklist->{$column} ?? 0) !== 1) {
                        $failedChecklistItems[] = [
                            'key' => $column,
                            'label' => $label,
                        ];
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'id' => $request->id,
                'request_code' => $request->request_code,
                'date_requested' => $request->date_requested,
                'business_name' => $request->business_name,
                'owner_name' => $request->owner_name,
                'business_type' => $request->business_type,
                'building_address' => $request->building_address,
                'barangay' => $request->barangay,
                'city' => $request->city,
                'province' => $request->province,
                'dti_sec_registration_no' => $request->dti_sec_registration_no,
                'mayors_permit_no' => $request->mayors_permit_no,
                'tin_number' => $request->tin_number,
                'status' => $request->status,
                'status_badge' => $request->status_badge,
                'created_at' => $request->created_at,
                'inspection' => $request->inspection,
                'inspection_details' => $inspection ? [
                    'inspection_date' => $inspection->inspection_date,
                    'inspection_remarks' => $inspection->inspection_remarks,
                    'is_compliant' => (int) ($inspection->is_compliant ?? 0),
                    'inspector_name' => trim(($inspection->inspector?->user?->first_name ?? '') . ' ' . ($inspection->inspector?->user?->last_name ?? '')) ?: null,
                    'inspector_rank' => $inspection->inspector?->rank,
                    'checklist_remarks' => $checklist?->remarks,
                    'failed_checklist_items' => $failedChecklistItems,
                ] : null,
                'approval' => $request->approval,
                'approval_details' => $request->approval ? [
                    'approval_date' => $request->approval->approval_date,
                    'approval_remarks' => $request->approval->approval_remarks,
                    'fsic_number' => $request->approval->fsic_number,
                    'date_issued' => $request->approval->date_issued,
                    'valid_until' => $request->approval->valid_until,
                    'approver_name' => trim(($request->approval->approver?->user?->first_name ?? '') . ' ' . ($request->approval->approver?->user?->last_name ?? '')) ?: null,
                    'approver_rank' => $request->approval->approver?->rank,
                    'approver_role' => $request->approval->approver?->user?->role?->role,
                    'station_name' => $request->approval->approver?->station?->station_name,
                ] : null
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Request not found'], 404);
        }
    }
    
    public function review(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            $fsicRequest = FsicRequest::findOrFail($id);
            $chiefStationOfficer = $this->getAuthenticatedStationOfficer();
            
            $decision = $request->decision;
            $remarks = $request->remarks;
            
            if ($decision == 'for_inspection') {
                $validated = $request->validate([
                    'assigned_station_officer_id' => 'required|integer|exists:station_officers,station_officer_id',
                ]);

                $assignedOfficer = StationOfficer::findOrFail($validated['assigned_station_officer_id']);
                if (!$chiefStationOfficer || (int) $assignedOfficer->station_id !== (int) $chiefStationOfficer->station_id) {
                    throw new \Exception('Selected personnel is not part of your station.');
                }

                $fsicRequest->status = 'inspected';
                $message = 'Request assigned to inspection personnel';

                FsicInspection::updateOrCreate(
                    ['fsic_request_id' => $fsicRequest->id],
                    ['station_officer_id' => $assignedOfficer->station_officer_id]
                );
            } elseif ($decision == 'rejected') {
                $fsicRequest->status = 'rejected';
                $message = 'Request has been rejected';
            } else {
                throw new \Exception('Invalid decision');
            }
            
            $fsicRequest->save();
            
            DB::commit();
            UserActivityLogger::log('reviewed fsic', Auth::user()?->user_id, $request);
            
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process review: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function inspect(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            $fsicRequest = FsicRequest::findOrFail($id);
            
            // Get station officer ID from logged in user
            $stationOfficerId = $this->getAuthenticatedStationOfficer()?->station_officer_id;
            
            // Create or update inspection record for the assigned inspector.
            $inspection = FsicInspection::updateOrCreate([
                'fsic_request_id' => $id,
            ], [
                'station_officer_id' => $stationOfficerId,
                'inspection_date' => now(),
                'inspection_remarks' => $request->remarks,
                'is_compliant' => $request->is_compliant
            ]);
            
            // Create checklist
            $checklist = $request->checklist;
            InspectionChecklist::create([
                'fsic_inspection_id' => $inspection->id,
                'fire_protection' => $checklist['fire_protection'] ?? 0,
                'alarm_systems' => $checklist['alarm_systems'] ?? 0,
                'standard_material_used' => $checklist['standard_material_used'] ?? 0,
                'electrical' => $checklist['electrical'] ?? 0,
                'housekeeping' => $checklist['housekeeping'] ?? 0,
                'hazards' => $checklist['hazards'] ?? 0,
                'planning' => $checklist['planning'] ?? 0,
                'remarks' => $request->remarks
            ]);
            
            // Update FSIC request status
            $fsicRequest->status = 'inspected';
            $fsicRequest->save();
            
            DB::commit();
            UserActivityLogger::log('inspected fsic', Auth::user()?->user_id, $request);
            
            return response()->json([
                'success' => true,
                'message' => 'Inspection completed successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit inspection: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function approve(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            $fsicRequest = FsicRequest::findOrFail($id);
            $issueNow = $request->boolean('issue_now');
            $issuedAt = $issueNow ? now() : null;
            
            // Generate FSIC number
            $fsicNumber = $request->filled('fsic_number')
                ? $request->fsic_number
                : 'FSIC-' . date('Y') . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
            
            // Create approval record
            FsicApproval::updateOrCreate(
                ['fsic_request_id' => $id],
                [
                    'approved_by' => $this->getAuthenticatedStationOfficer()?->station_officer_id,
                    'approval_date' => now(),
                    'is_approved' => true,
                    'approval_remarks' => $request->remarks,
                    'fsic_number' => $fsicNumber,
                    'date_issued' => $issuedAt,
                    'valid_until' => $issuedAt ? $issuedAt->copy()->addYear() : null,
                ]
            );
            
            // Update FSIC request status
            $fsicRequest->status = 'approved';
            $fsicRequest->save();
            
            DB::commit();
            UserActivityLogger::log('approved fsic', Auth::user()?->user_id, $request);
            
            return response()->json([
                'success' => true,
                'message' => $issueNow
                    ? 'Request approved and certificate issued successfully.'
                    : 'Request approved successfully. Certificate is ready for citizen claim.',
                'fsic_number' => $fsicNumber
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve request: ' . $e->getMessage()
            ], 500);
        }
    }
}
