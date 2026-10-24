<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class GisDemoController extends Controller
{
    public function mapping(Request $request)
    {
        return response()->view('demos.mapping-comparison', [
            'library' => $request->route('library') ?: 'all',
            'presentation' => $request->query('presentation') === 'matched' ? 'matched' : 'showcase',
        ])
            ->header('Cache-Control', 'no-store');
    }

    public function index(Request $request)
    {
        return response()->view('demos.gis-comparison', ['provider' => $request->route('provider', 'both')])
            ->header('Cache-Control', 'no-store');
    }

    public function openRouteServiceRoute(Request $request)
    {
        $data = $request->validate([
            'start_lat' => ['required', 'numeric', 'between:-90,90'],
            'start_lng' => ['required', 'numeric', 'between:-180,180'],
            'end_lat' => ['required', 'numeric', 'between:-90,90'],
            'end_lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $key = trim((string) config('gis_demo.openrouteservice_key'));
        if ($key === '') {
            return response()->json(['message' => 'OpenRouteService needs one API key. Sign in at openrouteservice.org and set OPENROUTESERVICE_API_KEY locally.'], 503);
        }

        try {
            $response = Http::withHeaders(['Authorization' => $key])
                ->accept('application/geo+json, application/json')->connectTimeout(5)->timeout(20)
                ->post('https://api.heigit.org/openrouteservice/v2/directions/driving-car/geojson', [
                    'coordinates' => [
                        [(float) $data['start_lng'], (float) $data['start_lat']],
                        [(float) $data['end_lng'], (float) $data['end_lat']],
                    ],
                    'units' => 'm', 'instructions' => true,
                ]);

            $payload = $response->json();
            $route = data_get($payload, 'features.0');
            $summary = data_get($route, 'properties.summary');
            $geometry = data_get($route, 'geometry');
            if (! $response->successful() || empty($geometry['coordinates']) || ! is_numeric($summary['distance'] ?? null) || ! is_numeric($summary['duration'] ?? null)) {
                $message = match ($response->status()) {
                    401, 403 => 'OpenRouteService rejected the API key. Check that your key is active.',
                    429 => 'OpenRouteService request quota reached. Please try again later.',
                    default => 'OpenRouteService could not return a drivable route for these markers.',
                };
                return response()->json(['message' => $message], 502);
            }

            return response()->json([
                'provider' => 'openrouteservice', 'type' => 'FeatureCollection',
                'attribution' => data_get($payload, 'metadata.attribution'),
                'features' => [[
                    'type' => 'Feature', 'geometry' => $geometry,
                    'properties' => ['distance' => (float) $summary['distance'], 'time' => (float) $summary['duration'], 'provider' => 'openrouteservice'],
                ]],
            ])->header('Cache-Control', 'no-store');
        } catch (\Throwable $exception) {
            // Do not log request exceptions: they may contain authenticated headers.
            return response()->json(['message' => 'The OpenRouteService demo service is currently unreachable. Please try again.'], 502);
        }
    }

    public function osrmRoute(Request $request)
    {
        $data = $request->validate([
            'start_lat' => ['required', 'numeric', 'between:-90,90'],
            'start_lng' => ['required', 'numeric', 'between:-180,180'],
            'end_lat' => ['required', 'numeric', 'between:-90,90'],
            'end_lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        // Shared across viewers: stay below the public endpoint's 1 req/s limit.
        if (! RateLimiter::attempt('gis-demo:osrm:upstream', 1, fn () => true, 2)) {
            return response()->json(['message' => 'Please wait two seconds before requesting another OSRM route.'], 429);
        }

        $coordinates = sprintf('%F,%F;%F,%F', $data['start_lng'], $data['start_lat'], $data['end_lng'], $data['end_lat']);

        try {
            $response = Http::withUserAgent('UrbiztondoCapstone-GISDemo/1.0 (academic routing comparison)')
                ->acceptJson()->connectTimeout(5)->timeout(20)
                ->get('https://routing.openstreetmap.de/routed-car/route/v1/driving/'.$coordinates, [
                    'overview' => 'full', 'geometries' => 'geojson', 'steps' => 'true', 'generate_hints' => 'false',
                ]);

            $payload = $response->json();
            $route = data_get($payload, 'routes.0');
            if (! $response->successful() || data_get($payload, 'code') !== 'Ok' || empty($route['geometry']['coordinates'])) {
                return response()->json(['message' => 'OSRM could not return a drivable route for these markers.'], 502);
            }

            return response()->json([
                'provider' => 'osrm',
                'type' => 'FeatureCollection',
                'features' => [[
                    'type' => 'Feature',
                    'geometry' => $route['geometry'],
                    'properties' => [
                        'distance' => $route['distance'],
                        'time' => $route['duration'],
                        'provider' => 'osrm',
                    ],
                ]],
            ])->header('Cache-Control', 'no-store');
        } catch (\Throwable $exception) {
            return response()->json(['message' => 'The OSRM demo service is currently unreachable. Please try again.'], 502);
        }
    }
}
