<?php
// app/Http/Controllers/LandingPageController.php

namespace App\Http\Controllers;

use App\Models\Marker;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function index()
    {
        // return view('landing');
    }

    public function getPublicMapResources(Request $request)
    {
        if ($request->boolean('version')) {
            return response()->json(['signature' => \App\Models\BoundaryLocation::mapCacheVersion()])
                ->header('Cache-Control', 'no-store');
        }
        // Public geography only: exclude custom operational shapes, incidents,
        // risk assessments, personnel, contact records and imported properties.
        $boundaries = \App\Models\BoundaryLocation::query()
            ->where('boundary_type', 'barangay')
            ->whereNotNull('barangay_id')
            ->get(['id', 'barangay_id', 'name', 'geojson'])
            ->map(fn ($boundary) => [
                'id' => $boundary->id,
                'barangay_id' => $boundary->barangay_id,
                'name' => $boundary->name,
                'geojson' => $this->publicBoundaryGeometry($boundary->geojson),
                'boundary_type' => 'barangay',
            ]);

        $markers = Marker::query()
            ->whereIn('category', ['barangay', 'school', 'bridge'])
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '!=', 'inactive');
            })
            ->get(['marker_id', 'title', 'category', 'lat', 'lng'])
            ->map(fn ($marker) => [
                'marker_id' => $marker->marker_id,
                'title' => $marker->title,
                'category' => $marker->category,
                'lat' => (float) $marker->lat,
                'lng' => (float) $marker->lng,
            ]);

        return response()->json([
            'markers' => $markers,
            'boundaries' => $boundaries,
            'signature' => \App\Models\BoundaryLocation::mapCacheVersion(),
            'count' => $boundaries->count(),
        ])->header('Cache-Control', 'no-store');
    }

    private function publicBoundaryGeometry(mixed $geometry): ?array
    {
        if (is_string($geometry)) {
            $geometry = json_decode($geometry, true);
        }
        if (!is_array($geometry)) return null;
        return match ($geometry['type'] ?? '') {
            'FeatureCollection' => [
                'type' => 'FeatureCollection',
                'features' => array_values(array_filter(array_map(fn ($feature) => $this->publicBoundaryGeometry($feature), $geometry['features'] ?? []))),
            ],
            'Feature' => [
                'type' => 'Feature',
                'properties' => (object) [],
                'geometry' => $this->publicBoundaryGeometry($geometry['geometry'] ?? null),
            ],
            'Polygon', 'MultiPolygon' => [
                'type' => $geometry['type'],
                'coordinates' => $geometry['coordinates'] ?? [],
            ],
            default => null,
        };
    }

    public function getStations()
    {
        $stations = Marker::where('category', 'station')
            ->with(['stations:station_id,station_name,contact_number,marker_id'])
            ->select('marker_id', 'title', 'description', 'lat', 'lng', 'status', 'updated_at')
            ->get()
            ->map(function ($station) {
                $stationRecord = $station->stations->first();

                return [
                    'id' => $station->marker_id,
                    'station_id' => $stationRecord?->station_id,
                    'name' => $stationRecord?->station_name ?: $station->title,
                    'address' => $station->description ?? $station->title,
                    'lat' => (float) $station->lat,
                    'lng' => (float) $station->lng,
                    'phone' => $stationRecord?->contact_number,
                    'status' => $station->status,
                    'updated_at' => optional($station->updated_at)->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $stations
        ]);
    }

    public function getHydrants()
    {
        $hydrants = Marker::where('category', 'hydrant')
            ->select('marker_id', 'title', 'description', 'lat', 'lng', 'status', 'updated_at')
            ->get()
            ->map(function ($hydrant) {
                return [
                    'marker_id' => $hydrant->marker_id,
                    'name' => $hydrant->title,
                    'address' => $hydrant->description ?? $hydrant->title,
                    'lat' => (float) $hydrant->lat,
                    'lng' => (float) $hydrant->lng,
                    'status' => $hydrant->status,
                    'updated_at' => optional($hydrant->updated_at)->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $hydrants
        ]);
    }
}
