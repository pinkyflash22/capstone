<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

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
        'lat' => 'required|numeric|between:-90,90',
        'lon' => 'required|numeric|between:-180,180',
    ]);

    $normalizedLat = round((float) $validated['lat'], 5);
    $normalizedLon = round((float) $validated['lon'], 5);
    $cacheKey = sprintf('geoapify:reverse:v1:%0.5f:%0.5f', $normalizedLat, $normalizedLon);

    if (Cache::has($cacheKey)) {
        return response()
            ->json(Cache::get($cacheKey))
            ->header('X-Reverse-Geocode-Cache', 'HIT');
    }

    $limiterKey = 'geoapify-reverse:' . ($request->user()?->getAuthIdentifier() ?: $request->ip());
    $maxAttempts = 30;
    $decaySeconds = 60;

    if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
        return response()->json([
            'error' => 'Reverse geocoding is temporarily rate limited',
            'retry_after' => RateLimiter::availableIn($limiterKey),
        ], 429);
    }

    RateLimiter::hit($limiterKey, $decaySeconds);

    $apiKey = config('services.geoapify.key');
    if (!$apiKey) {
        return response()->json([
            'error' => 'Reverse geocoding service is not configured'
        ], 500);
    }

    try {
        $response = Http::timeout(10)->retry(1, 200)->get('https://api.geoapify.com/v1/geocode/reverse', [
            'lat' => $normalizedLat,
            'lon' => $normalizedLon,
            'apiKey' => $apiKey,
        ]);

        if ($response->successful()) {
            $payload = $response->json();
            Cache::put($cacheKey, $payload, now()->addDays(30));

            return response()
                ->json($payload)
                ->header('X-Reverse-Geocode-Cache', 'MISS');
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
