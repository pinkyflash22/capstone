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
        $validated = $request->validate([
            'start_lat' => ['required', 'numeric', 'between:-90,90'],
            'start_lng' => ['required', 'numeric', 'between:-180,180'],
            'end_lat' => ['required', 'numeric', 'between:-90,90'],
            'end_lng' => ['required', 'numeric', 'between:-180,180'],
            'mode' => ['nullable', 'in:driving'],
        ]);

        $apiKey = trim((string) config('services.geoapify.key'));
        if ($apiKey === '') {
            return response()->json(['message' => 'Geoapify routing is not configured.'], 503);
        }

        $baseUrl = rtrim((string) config('services.geoapify.routing_base_url', 'https://api.geoapify.com/v1/routing'), '/');
        $waypoints = sprintf(
            '%F,%F|%F,%F',
            (float) $validated['start_lat'],
            (float) $validated['start_lng'],
            (float) $validated['end_lat'],
            (float) $validated['end_lng'],
        );
        $routeCacheKey = sprintf(
            'geoapify:route:v1:%0.5f:%0.5f:%0.5f:%0.5f',
            (float) $validated['start_lat'],
            (float) $validated['start_lng'],
            (float) $validated['end_lat'],
            (float) $validated['end_lng'],
        );

        try {
            $response = Http::connectTimeout((int) config('services.geoapify.connect_timeout', 5))
                ->timeout((int) config('services.geoapify.timeout', 15))
                ->retry(1, 200)
                ->acceptJson()
                ->get($baseUrl, [
                    'waypoints' => $waypoints,
                    'mode' => 'drive',
                    'format' => 'geojson',
                    'details' => 'instruction_details',
                    'apiKey' => $apiKey,
                ]);

            if (! $response->successful()) {
                Log::warning('Geoapify route request failed.', ['status' => $response->status()]);

                if ($cached = Cache::get($routeCacheKey)) {
                    return response()->json($cached)
                        ->header('X-Route-Cache', 'STALE')
                        ->header('X-Route-Provider', 'geoapify');
                }
                return response()->json(['message' => 'Geoapify routing is temporarily unavailable.'], 502);
            }

            $payload = $response->json();
            $route = data_get($payload, 'features.0');
            if (! is_array($payload) || ! is_array($route) || empty($route['geometry']['coordinates'])) {
                if ($cached = Cache::get($routeCacheKey)) {
                    return response()->json($cached)
                        ->header('X-Route-Cache', 'STALE')
                        ->header('X-Route-Provider', 'geoapify');
                }
                return response()->json(['message' => 'No drivable road route was found for those locations.'], 422);
            }

            $payload['features'][0]['properties'] = array_replace(
                (array) ($payload['features'][0]['properties'] ?? []),
                ['provider' => 'geoapify']
            );
            $payload['provider'] = 'geoapify';
            Cache::put($routeCacheKey, $payload, now()->addDays(7));

            return response()->json($payload)
                ->header('X-Route-Cache', 'MISS')
                ->header('X-Route-Provider', 'geoapify');
        } catch (\Throwable $e) {
            Log::error('Geoapify route request failed.', ['error' => $e->getMessage()]);

            if ($cached = Cache::get($routeCacheKey)) {
                return response()->json($cached)
                    ->header('X-Route-Cache', 'STALE')
                    ->header('X-Route-Provider', 'geoapify');
            }
            return response()->json(['message' => 'Geoapify routing is temporarily unavailable.'], 502);
        }
    }


    public function reverse(Request $request)
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['nullable', 'required_without:lng', 'numeric', 'between:-180,180'],
            'lng' => ['nullable', 'required_without:lon', 'numeric', 'between:-180,180'],
        ]);

        $normalizedLat = round((float) $validated['lat'], 5);
        $normalizedLon = round((float) ($validated['lon'] ?? $validated['lng']), 5);
        $cacheKey = sprintf('osm:reverse:v2:%0.5f:%0.5f', $normalizedLat, $normalizedLon);

        if (Cache::has($cacheKey)) {
            return response()
                ->json(Cache::get($cacheKey))
                ->header('X-Reverse-Geocode-Cache', 'HIT');
        }

        $limiterKey = 'osm-reverse:' . ($request->user()?->getAuthIdentifier() ?: $request->ip());
        $maxAttempts = 30;
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            return response()->json([
                'error' => 'Reverse geocoding is temporarily rate limited',
                'retry_after' => RateLimiter::availableIn($limiterKey),
            ], 429);
        }

        RateLimiter::hit($limiterKey, $decaySeconds);

        try {
            $response = Http::connectTimeout((int) config('services.nominatim.connect_timeout', 5))
                ->timeout((int) config('services.nominatim.timeout', 10))
                ->retry(1, 300)
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => 'PyroSense/1.0 ('.config('app.url').')',
                ])
                ->get(rtrim((string) config('services.nominatim.base_url', 'https://nominatim.openstreetmap.org'), '/').'/reverse', [
                    'lat' => $normalizedLat,
                    'lon' => $normalizedLon,
                    'format' => 'jsonv2',
                    'addressdetails' => 1,
                    'zoom' => 18,
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $formatted = trim((string) ($result['display_name'] ?? ''));
                $payload = [
                    'features' => $formatted === '' ? [] : [[
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [$normalizedLon, $normalizedLat],
                        ],
                        'properties' => [
                            'formatted' => $formatted,
                            'address_line1' => $result['name'] ?? null,
                            'address' => $result['address'] ?? [],
                            'provider' => 'openstreetmap_nominatim',
                        ],
                    ]],
                    'provider' => 'openstreetmap_nominatim',
                ];
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
                'lat' => $normalizedLat,
                'lon' => $normalizedLon,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Reverse geocoding request failed',
            ], 500);
        }
    }
    // public function show(){
    //     return view('markers.map');
    // }
}
