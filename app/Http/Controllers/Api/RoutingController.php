<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoutingController extends Controller
{
      public function route(Request $request)
    {
        $response = Http::get('https://api.geoapify.com/v1/routing', [
            'waypoints' => $request->start . '|' . $request->end,
            'mode' => 'drive',
            'type' => 'balanced',
            'apiKey' =>env('GEOAPIFY_KEY'),
            // env('GEOAPIFY_KEY')
        ]);
        
        return response()->json($response->json());
    }

    
   public function reverse(Request $request)
{
    $validated = $request->validate([
        'lat' => 'required|numeric',
        'lon' => 'required|numeric',
    ]);

    $apiKey = env('GEOAPIFY_KEY');
    if (!$apiKey) {
        return response()->json([
            'error' => 'Reverse geocoding service is not configured'
        ], 500);
    }

    try {
        $response = Http::timeout(10)->retry(1, 200)->get('https://api.geoapify.com/v1/geocode/reverse', [
            'lat' => $validated['lat'],
            'lon' => $validated['lon'],
            'apiKey' => $apiKey,
        ]);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json([
            'error' => 'Unable to reverse geocode',
            'upstream_status' => $response->status(),
        ], 502);
    } catch (\Throwable $e) {
        Log::error('Reverse geocode failed', [
            'lat' => $validated['lat'] ?? null,
            'lon' => $validated['lon'] ?? null,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'error' => 'Reverse geocoding request failed'
        ], 500);
    }
}
    // public function show(){
    //     return view('markers.map');
    // }
}
