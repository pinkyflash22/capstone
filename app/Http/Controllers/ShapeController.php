<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shape;

class ShapeController extends Controller
{
    // Save a new shape
    public function store(Request $request)
    {
        try {
            $data = $request->json()->all();

            if (!isset($data['geojson'])) {
                return response()->json(['error' => 'GeoJSON missing'], 400);
            }

            $shape = Shape::create([
                'name' => $data['name'] ?? 'My Shape',
                'geojson' => json_encode($data['geojson']),
                // 'station_id' => $data['station_id'] ?? null,
                'station_id' => 'STN-0004',

            ]);

            return response()->json(['success' => true, 'shape' => $shape]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Update a shape
    public function update(Request $request, $id)
    {
        try {
            $data = $request->json()->all();
            
            $shape = Shape::findOrFail($id);
            
            $shape->update([
                'name' => $data['name'] ?? $shape->name,
                'geojson' => isset($data['geojson']) ? json_encode($data['geojson']) : $shape->geojson,
            ]);

            return response()->json(['success' => true, 'shape' => $shape]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a shape
    public function destroy($id)
    {
        try {
            $shape = Shape::findOrFail($id);
            $shape->delete();

            return response()->json(['success' => true, 'message' => 'Shape deleted']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Get all shapes
    public function index()
    {
        try {
            $shapes = Shape::all()->map(function ($shape) {
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
}