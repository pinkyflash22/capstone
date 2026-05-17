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

    public function getStations()
    {
        $stations = Marker::where('category', 'station')
            ->select('marker_id', 'title', 'description', 'lat', 'lng')
            ->get()
            ->map(function ($station) {
                return [
                    'id' => $station->marker_id,
                    'name' => $station->title,
                    'address' => $station->description ?? $station->title,
                    'lat' => (float) $station->lat,
                    'lng' => (float) $station->lng,
                    'phone' => $station->phone ?? '(075) 123-4567'
                    // 'personnel' => $station->personnel ?? rand(20, 35),
                    // 'equipment' => $station->equipment ?? rand(2, 5) . ' fire trucks',
                    // 'captain' => $station->captain ?? 'Capt. ' . ['Reyes', 'Santos', 'Villanueva', 'Mendoza', 'Fernandez'][rand(0,4)]
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
            ->select('marker_id', 'title', 'description', 'lat', 'lng',)
            ->get()
            ->map(function ($hydrant) {
                return [
                    'marker_id' => $hydrant->marker_id,
                    'name' => $hydrant->title,
                    'address' => $hydrant->description ?? $hydrant->title,
                    'lat' => (float) $hydrant->lat,
                    'lng' => (float) $hydrant->lng
                    // 'status' => $hydrant->status ?? 'operational'
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $hydrants
        ]);
    }
}
