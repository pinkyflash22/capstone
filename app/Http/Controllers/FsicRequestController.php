<?php
// app/Http/Controllers/Citizen/FsicRequestController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FsicRequest;
use App\Models\FsicInspection;
use App\Models\FsicApproval;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Support\UserActivityLogger;

class FsicRequestController extends Controller
{
    /**
     * Display the FSIC request form data
     */
    public function index()
    {
        // Get user's FSIC requests
        $fsicRequests = FsicRequest::with('approval')
            ->where('user_id', Auth::user()->user_id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get provinces for dropdown
        $provinces = Province::orderBy('province_name')->get();
        
        return view('citizen.sections.fsic', compact('fsicRequests', 'provinces'));
    }

    /**
     * Get cities by province ID (AJAX)
     */
    public function getCities($provinceId)
    {
        $cities = City::where('province_id', $provinceId)
            ->orderBy('city_name')
            ->get(['city_id', 'city_name']);
        
        return response()->json($cities);
    }

    /**
     * Get barangays by city ID (AJAX)
     */
    public function getBarangays($cityId)
    {
        $barangays = Barangay::where('city_id', $cityId)
            ->orderBy('barangay_name')
            ->get(['barangay_id', 'barangay_name']);
        
        return response()->json($barangays);
    }

    /**
     * Store a new FSIC request
     */
    // public function store(Request $request)
    // {
    //     try {
    //         DB::beginTransaction();
            
    //         // Validate the request
    //         $validator = Validator::make($request->all(), [
    //             'establishment_type' => 'required|in:business,house',
    //             'business_name' => 'required|string|max:255',
    //             'owner_name' => 'required|string|max:255',
    //             'business_type' => 'nullable|string|max:100',
    //             'building_address' => 'required|string|max:500',
    //             'province_id' => 'required|exists:provinces,province_id',
    //             'city_id' => 'required|exists:cities,city_id',
    //             'barangay_id' => 'required|exists:barangays,barangay_id',
    //             'dti_sec_registration_no' => 'nullable|string|max:100',
    //             'mayors_permit_no' => 'nullable|string|max:100',
    //             'tin_number' => 'nullable|string|max:50',
    //             'request_code' => 'required|unique:fsic_requests,request_code',
    //             'date_requested' => 'required|date',
    //         ]);
            
    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Validation failed',
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }
            
    //         // Get province, city, barangay names
    //         $province = Province::find($request->province_id);
    //         $city = City::find($request->city_id);
    //         $barangay = Barangay::find($request->barangay_id);
            
    //         // Create FSIC request
    //         $fsicRequest = FsicRequest::create([
    //             'user_id' => Auth::user()->user_id,
    //             'request_code' => $request->request_code,
    //             'date_requested' => $request->date_requested,
    //             'establishment_type' => $request->establishment_type,
    //             'business_name' => $request->business_name,
    //             'owner_name' => $request->owner_name,
    //             'business_type' => $request->business_type,
    //             'building_address' => $request->building_address,
    //             'barangay' => $barangay->barangay_name,
    //             'city' => $city->city_name,
    //             'province' => $province->province_name,
    //             'dti_sec_registration_no' => $request->dti_sec_registration_no,
    //             'mayors_permit_no' => $request->mayors_permit_no,
    //             'tin_number' => $request->tin_number,
    //             'status' => 'pending'
    //         ]);
            
    //         DB::commit();
            
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'FSIC request submitted successfully!',
    //             'data' => $fsicRequest
    //         ], 201);
            
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         \Log::error('FSIC Request Error: ' . $e->getMessage());
            
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to submit request: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
// app/Http/Controllers/Citizen/FsicRequestController.php

public function store(Request $request)
{
    try {
        DB::beginTransaction();
        
        // Log incoming data for debugging
        \Log::info('FSIC Request Data:', $request->all());
        
        // Validate the request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
            'request_code' => 'required|unique:fsic_requests,request_code',
            'date_requested' => 'required|date',
            'business_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'building_address' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'dti_sec_registration_no' => 'nullable|string|max:255',
            'mayors_permit_no' => 'nullable|string|max:255',
            'tin_number' => 'nullable|string|max:255',
            'status' => 'required|in:pending,for_inspection,inspected,approved,rejected'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Create FSIC request
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
            'status' => $request->status
        ]);
        
        DB::commit();

        UserActivityLogger::log('created fsic request', Auth::id(), $request);
        
        return response()->json([
            'success' => true,
            'message' => 'FSIC request submitted successfully!',
            'data' => $fsicRequest
        ], 201);
        
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('FSIC Request Error: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to submit request: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Get FSIC request details
     */
    public function show($id)
    {
        try {
            $fsicRequest = FsicRequest::with([
                    'inspection.checklist',
                    'inspection.inspector.user',
                    'approval.approver.user.role',
                    'approval.approver.station',
                ])
                ->where('user_id', Auth::user()->user_id)
                ->findOrFail($id);
            
            // Get inspection data if exists
            $inspectionData = null;
            if ($fsicRequest->inspection) {
                $checklist = $fsicRequest->inspection->checklist;
                $checklistLabels = [
                    'fire_protection' => 'Fire Protection',
                    'alarm_systems' => 'Alarm Systems',
                    'standard_material_used' => 'Standard Material Used',
                    'electrical' => 'Electrical',
                    'housekeeping' => 'Housekeeping',
                    'hazards' => 'Hazards',
                    'planning' => 'Planning',
                ];

                $checklistItems = [];
                foreach ($checklistLabels as $column => $label) {
                    $checklistItems[] = [
                        'key' => $column,
                        'label' => $label,
                        'passed' => $checklist ? (int) ($checklist->{$column} ?? 0) === 1 : null,
                    ];
                }

                $inspectionData = [
                    'inspection_date' => $fsicRequest->inspection->inspection_date,
                    'is_compliant' => $fsicRequest->inspection->is_compliant,
                    'inspection_remarks' => $fsicRequest->inspection->inspection_remarks,
                    'inspector_name' => trim(($fsicRequest->inspection->inspector?->user?->first_name ?? '') . ' ' . ($fsicRequest->inspection->inspector?->user?->last_name ?? '')) ?: null,
                    'inspector_rank' => $fsicRequest->inspection->inspector?->rank,
                    'checklist_remarks' => $checklist?->remarks,
                    'checklist_items' => $checklistItems,
                ];
            }
            
            // Get approval data if exists
            $approvalData = null;
            if ($fsicRequest->approval) {
                $approvalData = [
                    'approval_date' => $fsicRequest->approval->approval_date,
                    'is_approved' => $fsicRequest->approval->is_approved,
                    'approval_remarks' => $fsicRequest->approval->approval_remarks,
                    'fsic_number' => $fsicRequest->approval->fsic_number,
                    'date_issued' => $fsicRequest->approval->date_issued,
                    'valid_until' => $fsicRequest->approval->valid_until,
                    'approver_name' => trim(($fsicRequest->approval->approver?->user?->first_name ?? '') . ' ' . ($fsicRequest->approval->approver?->user?->last_name ?? '')) ?: null,
                    'approver_rank' => $fsicRequest->approval->approver?->rank,
                    'approver_role' => $fsicRequest->approval->approver?->user?->role?->role,
                    'station_name' => $fsicRequest->approval->approver?->station?->station_name,
                ];
            }

            UserActivityLogger::log('viewed fsic request', Auth::id(), request());
            
            return response()->json([
                'success' => true,
                'id' => $fsicRequest->id,
                'request_code' => $fsicRequest->request_code,
                'date_requested' => $fsicRequest->date_requested,
                'establishment_type' => $fsicRequest->establishment_type,
                'business_name' => $fsicRequest->business_name,
                'owner_name' => $fsicRequest->owner_name,
                'business_type' => $fsicRequest->business_type,
                'building_address' => $fsicRequest->building_address,
                'barangay' => $fsicRequest->barangay,
                'city' => $fsicRequest->city,
                'province' => $fsicRequest->province,
                'dti_sec_registration_no' => $fsicRequest->dti_sec_registration_no,
                'mayors_permit_no' => $fsicRequest->mayors_permit_no,
                'tin_number' => $fsicRequest->tin_number,
                'status' => $fsicRequest->status,
                'created_at' => $fsicRequest->created_at,
                'updated_at' => $fsicRequest->updated_at,
                'inspection' => $inspectionData,
                'approval' => $approvalData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found'
            ], 404);
        }
    }

    /**
     * Update FSIC request (for drafts or edits)
     */
    public function update(Request $request, $id)
    {
        try {
            $fsicRequest = FsicRequest::where('user_id', Auth::user()->user_id)
                ->where('status', 'pending')
                ->findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'business_name' => 'sometimes|string|max:255',
                'owner_name' => 'sometimes|string|max:255',
                'business_type' => 'nullable|string|max:255',
                'building_address' => 'sometimes|string|max:500',
                'dti_sec_registration_no' => 'nullable|string|max:255',
                'mayors_permit_no' => 'nullable|string|max:255',
                'tin_number' => 'nullable|string|max:255',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $fsicRequest->update($request->only([
                'business_name',
                'owner_name',
                'business_type',
                'building_address',
                'dti_sec_registration_no',
                'mayors_permit_no',
                'tin_number'
            ]));

            $fsicRequest->refresh();

            UserActivityLogger::log('updated fsic request', Auth::id(), $request);
            
            return response()->json([
                'success' => true,
                'message' => 'FSIC request updated successfully!',
                'data' => $fsicRequest
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete FSIC request (only if pending/draft)
     */
    public function destroy($id)
    {
        try {
            $fsicRequest = FsicRequest::where('user_id', Auth::user()->user_id)
                ->whereIn('status', ['pending'])
                ->findOrFail($id);
            
            $fsicRequest->delete();

            UserActivityLogger::log('deleted fsic request', Auth::id(), request());
            
            return response()->json([
                'success' => true,
                'message' => 'FSIC request cancelled successfully!'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's FSIC requests (for API)
     */
    public function myRequests()
    {
        $requests = FsicRequest::with('approval')
            ->where('user_id', Auth::user()->user_id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    public function claim($id)
    {
        try {
            DB::beginTransaction();

            $fsicRequest = FsicRequest::with('approval')
                ->where('user_id', Auth::user()->user_id)
                ->findOrFail($id);

            if ($fsicRequest->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved requests can be claimed.',
                ], 422);
            }

            if (!$fsicRequest->approval) {
                return response()->json([
                    'success' => false,
                    'message' => 'Approval record not found for this request.',
                ], 404);
            }

            if ($fsicRequest->approval->date_issued) {
                return response()->json([
                    'success' => false,
                    'message' => 'This certificate has already been claimed.',
                ], 422);
            }

            $claimedAt = now();
            $fsicRequest->approval->update([
                'date_issued' => $claimedAt,
                'valid_until' => $claimedAt->copy()->addYear(),
            ]);

            DB::commit();

            UserActivityLogger::log('claimed fsic', Auth::id(), request());

            return response()->json([
                'success' => true,
                'message' => 'FSIC certificate claimed successfully.',
                'data' => $fsicRequest->fresh(['approval', 'inspection']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to claim certificate: ' . $e->getMessage(),
            ], 500);
        }
    }
}
