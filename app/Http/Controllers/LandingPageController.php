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
