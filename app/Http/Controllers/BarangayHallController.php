<?php

namespace App\Http\Controllers;

use App\Models\BarangayHall;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BarangayHallController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'barangay_id' => [
                'required',
                'exists:barangays,barangay_id',
                Rule::unique('barangay_halls', 'barangay_id'),
            ],
            'marker_id' => 'required|exists:markers,marker_id',
            'hall_name' => 'required|string|max:255',
            'address_id' => 'nullable|exists:full_addresses,address_id',
            'contact_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'captain_user_id' => 'nullable|exists:barangay_officers,barangay_officer_id',
        ]);

        $hall = BarangayHall::create($validated);

        return response()->json([
            'success' => true,
            'data' => $hall->fresh(['barangay', 'address.barangay.city.province', 'captain.user', 'marker']),
        ]);
    }

    public function getByMarker($markerId)
    {
        $hall = BarangayHall::with(['barangay', 'address.barangay.city.province', 'captain.user', 'marker'])
            ->where('marker_id', $markerId)
            ->first();

        if (! $hall) {
            return response()->json(['error' => 'Barangay hall not found'], 404);
        }

        return response()->json($hall);
    }

    public function updateByMarker(Request $request, $markerId)
    {
        $hall = BarangayHall::where('marker_id', $markerId)->first();

        if (! $hall) {
            return response()->json(['error' => 'Barangay hall not found'], 404);
        }

        $validated = $request->validate([
            'barangay_id' => [
                'nullable',
                'exists:barangays,barangay_id',
                Rule::unique('barangay_halls', 'barangay_id')->ignore($hall->barangay_hall_id, 'barangay_hall_id'),
            ],
            'hall_name' => 'nullable|string|max:255',
            'address_id' => 'nullable|exists:full_addresses,address_id',
            'contact_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'captain_user_id' => 'nullable|exists:barangay_officers,barangay_officer_id',
        ]);

        $hall->update($validated);

        return response()->json([
            'success' => true,
            'data' => $hall->fresh(['barangay', 'address.barangay.city.province', 'captain.user', 'marker']),
        ]);
    }
}
