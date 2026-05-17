<?php
// app/Http/Controllers/CoordinateController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coordinate;
use App\Models\Marker;
use App\Models\Incident;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CoordinateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $coordinate = Coordinate::all();
        return response()->json(Coordinate::with(['marker', 'image'])->get());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('citizen.sections.overview');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'marker_id' => 'required|exists:markers,marker_id',
                'image_id' => 'required|exists:multimedia,multimedia_id',
                'status'  => 'required|in:temporary,permanent,complete,demolished',
            ]);

            $coordinate = Coordinate::create([
                'marker_id' => $request->marker_id,
                'image_id' => $request->image_id,
                'status' => $request->status,
                'created_at' => Carbon::now(), // Add created_at since timestamps are false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Coordinate saved successfully',
                'data' => $coordinate
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Coordinate save error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save coordinate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $coordinate = Coordinate::with(['marker', 'image'])->find($id);
        
        if (!$coordinate) {
            return response()->json([
                'success' => false,
                'message' => 'Coordinate not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $coordinate
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $coordinate = Coordinate::find($id);
            
            if (!$coordinate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Coordinate not found'
                ], 404);
            }

            $request->validate([
                'marker_id' => 'sometimes|exists:markers,marker_id',
                'image_id' => 'nullable|exists:multimedia,multimedia_id',
                'status'  => 'sometimes|in:temporary,permanent,complete,demolished',
            ]);

            $coordinate->update($request->only(['marker_id', 'image_id', 'status']));

            return response()->json([
                'success' => true,
                'message' => 'Coordinate updated successfully',
                'data' => $coordinate
            ]);

        } catch (\Exception $e) {
            Log::error('Coordinate update error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update coordinate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $coordinate = Coordinate::find($id);
            
            if (!$coordinate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Coordinate not found'
                ], 404);
            }

            $coordinate->delete();

            return response()->json([
                'success' => true,
                'message' => 'Coordinate deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Coordinate delete error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete coordinate',
                'error' => $e->getMessage()
            ], 500);
        }
    }
     public function latest()
{
    $coord = \App\Models\Coordinate::with(['marker', 'image'])
                ->latest()
                ->get();
    // $relatedIncident = \App\Models\Incident::with(['coordinate'])
    //             ->latest()
    //             ->get();

    return response()->json($coord);
}
}