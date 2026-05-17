<?php

namespace App\Http\Controllers;

use App\Models\BoundaryLocation;
use App\Models\Station;
use Illuminate\Http\Request;

class BoundaryLocationController extends Controller
{
    public function index()
    {
       try {
            $shapes = BoundaryLocation::all()->map(function ($shape) {
                $shape->geojson = json_decode($shape->geojson);
                return $shape;
            });

            return response()->json($shapes);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function create()
    {
        $stations = Station::all();
        return view('admin.boundaries.create', compact('stations'));
    }

    public function store(Request $request)
    {

    
    //     $validated = $request->validate([
    //         'name' => 'nullable|string|max:255',
    //         'geojson' => 'required|json',
    //         'station_id' => 'nullable|exists:stations,station_id',
    //     ]);
    //      if (is_array($validated['geojson'])) {
    //     $validated['geojson'] = json_encode($validated['geojson']);
    // }
    //   $data = BoundaryLocation::create($validated);

    //    return response()->json([
    //     'success' => true,
    //     'data' => $data
    // ], 201);

      try {
            $data = $request->json()->all();

            if (!isset($data['geojson'])) {
                return response()->json(['error' => 'GeoJSON missing'], 400);
            }

            $shape = BoundaryLocation::create([
                'name' => $data['name'] ?? 'My Shape',
                'geojson' => json_encode($data['geojson']),
                'station_id' => $data['station_id'] ?? null,
                // 'station_id' => 1,

            ]);

            return response()->json(['success' => true, 'shape' => $shape]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(BoundaryLocation $boundary)
    {
        $boundary->load('station');
        return view('admin.boundaries.show', compact('boundary'));
    }

    public function edit(BoundaryLocation $boundary)
    {
        $stations = Station::all();
        return view('admin.boundaries.edit', compact('boundary', 'stations'));
    }

    public function update(Request $request, BoundaryLocation $boundary)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'geojson' => 'required|json',
            'station_id' => 'required|exists:stations,station_id',
        ]);

        $boundary->update($validated);

        return redirect()->route('boundaries.index')
            ->with('success', 'Boundary location updated successfully.');
    }

    public function destroy($id)
    {
        try {
            $shape = BoundaryLocation::findOrFail($id);
            $shape->delete();

            return response()->json(['success' => true, 'message' => 'Shape deleted']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getMapBoundaries()
    {
        $boundaries = BoundaryLocation::with('station')->get();
        return response()->json($boundaries);
    }
}