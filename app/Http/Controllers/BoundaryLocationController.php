<?php

namespace App\Http\Controllers;

use App\Exceptions\BoundaryUnavailableException;
use App\Models\Barangay;
use App\Models\BoundaryLocation;
use App\Models\City;
use App\Models\Station;
use App\Models\StationOfficer;
use App\Services\BoundaryMatcherService;
use App\Services\BoundaryManagementScope;
use App\Services\GeoapifyBoundaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BoundaryLocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'boundary_type' => ['nullable', 'string', 'max:50'],
            'barangay_id' => ['nullable', 'exists:barangays,barangay_id'],
            'station_id' => ['nullable', 'exists:stations,station_id'],
        ]);

        $cacheKey = 'map:shapes:index:' . BoundaryLocation::mapCacheVersion() . ':' . md5(json_encode($validated));

        $shapes = Cache::remember($cacheKey, now()->addMinutes(3), function () use ($validated) {
            return BoundaryLocation::query()
                ->with([
                    'station:station_id,station_name,marker_id',
                    'barangay:barangay_id,barangay_name,city_id',
                    'barangay.city:city_id,city_name,province_id',
                ])
                ->when(! empty($validated['boundary_type']), fn ($query) => $query->where('boundary_type', $validated['boundary_type']))
                ->when(! empty($validated['barangay_id']), fn ($query) => $query->where('barangay_id', $validated['barangay_id']))
                ->when(! empty($validated['station_id']), fn ($query) => $query->where('station_id', $validated['station_id']))
                ->orderBy('name')
                ->get()
                ->map(fn (BoundaryLocation $shape) => $this->serializeBoundary($shape))
                ->values()
                ->all();
        });

        return response()->json($shapes)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function latest(): JsonResponse
    {
        return response()->json([
            'signature' => BoundaryLocation::mapCacheVersion(),
            'count' => BoundaryLocation::query()->count(),
            'updated_at' => BoundaryLocation::query()->max('updated_at'),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function create()
    {
        $stations = Station::orderBy('station_name')->get();

        return view('admin.boundaries.create', compact('stations'));
    }

    public function store(Request $request, BoundaryManagementScope $scope): JsonResponse
    {
        $user = $scope->assertManager(Auth::user());
        $validated = $this->validateBoundaryPayload($request);
        $payload = $this->normalizeBoundaryPayload($validated);
        $barangayId = $this->requiredBarangayId($payload);
        $scope->assertBarangayInAssignedCity($user, $barangayId);
        $payload['station_id'] = $scope->stationForBarangay($user, $barangayId);
        $this->assertNoCompetingBoundary($scope, $user, $barangayId);

        $shape = BoundaryLocation::create($payload);

        return response()->json([
            'success' => true,
            'shape' => $this->serializeBoundary($shape->fresh(['station', 'barangay.city'])),
            'boundary_version' => BoundaryLocation::mapCacheVersion(),
        ], 201);
    }

    public function show(BoundaryLocation $boundary)
    {
        $boundary->load(['station', 'barangay.city']);

        return view('admin.boundaries.show', compact('boundary'));
    }

    public function edit(BoundaryLocation $boundary)
    {
        $stations = Station::orderBy('station_name')->get();

        return view('admin.boundaries.edit', compact('boundary', 'stations'));
    }

    public function update(Request $request, int $id, BoundaryManagementScope $scope): JsonResponse
    {
        $user = $scope->assertManager(Auth::user());
        $boundary = BoundaryLocation::query()->findOrFail($id);
        $scope->stationForBoundary($user, $boundary);
        $validated = $this->validateBoundaryPayload($request, true);
        $payload = $this->normalizeBoundaryPayload($validated, $boundary);
        $barangayId = (int) ($payload['barangay_id'] ?? $boundary->barangay_id);
        $scope->assertBarangayInAssignedCity($user, $barangayId);
        $payload['station_id'] = $scope->stationForBarangay(
            $user,
            $barangayId,
            $barangayId === (int) $boundary->barangay_id && $boundary->station_id
                ? (int) $boundary->station_id
                : null,
        );
        $this->assertNoCompetingBoundary($scope, $user, $barangayId, (int) $boundary->getKey());

        $boundary->update($payload);

        return response()->json([
            'success' => true,
            'shape' => $this->serializeBoundary($boundary->fresh(['station', 'barangay.city'])),
            'boundary_version' => BoundaryLocation::mapCacheVersion(),
        ]);
    }

    public function destroy(int $id, BoundaryManagementScope $scope): JsonResponse
    {
        $user = $scope->assertManager(Auth::user());
        $shape = BoundaryLocation::query()->findOrFail($id);
        $scope->stationForBoundary($user, $shape);
        $shape->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shape deleted',
            'boundary_version' => BoundaryLocation::mapCacheVersion(),
        ]);
    }

    public function getMapBoundaries(): JsonResponse
    {
        return $this->index(request());
    }

    public function cityOptions(BoundaryManagementScope $scope): JsonResponse
    {
        $user = $scope->assertManager(Auth::user());
        $assignedCity = $scope->assignedCity($user);
        $cities = collect($assignedCity ? [$assignedCity] : [])
            ->map(function (City $city) {
                return [
                    'city_id' => $city->city_id,
                    'city_name' => $city->city_name,
                    'province_id' => $city->province_id,
                    'province_name' => $city->province?->province_name,
                ];
            })
            ->values();

        return response()->json($cities)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function editorContext(BoundaryManagementScope $scope): JsonResponse
    {
        $user = $scope->assertManager(Auth::user());
        $stationOfficer = StationOfficer::query()
            ->with(['station.address.barangay.city'])
            ->where('user_id', $user->getKey())
            ->orderBy('station_officer_id')
            ->first();
        $city = $scope->assignedCity($user);

        return response()->json([
            'default_city_id' => $city?->city_id,
            'default_city_name' => $city?->city_name,
            'locked_to_station_city' => (bool) $city,
            'city_source' => $stationOfficer?->station?->address?->barangay?->city ? 'station_address' : ($city ? 'station_coverage' : null),
            'station_id' => $stationOfficer?->station_id,
            'station_name' => $stationOfficer?->station?->station_name,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function availableBarangays(int $cityId, BoundaryManagementScope $scope): JsonResponse
    {
        $user = $scope->assertManager(Auth::user());
        $assignedCity = $scope->assignedCity($user);
        abort_unless($assignedCity && (int) $assignedCity->city_id === $cityId, 403, 'Only the assigned station municipality can be used.');
        $coveredBarangayIds = $scope->coveredBarangayIds($user);
        $existingBoundaryBarangayIds = BoundaryLocation::query()
            ->where('boundary_type', 'barangay')
            ->whereNotNull('barangay_id')
            ->pluck('barangay_id')
            ->filter()
            ->values();

        $barangays = Barangay::query()
            ->where('city_id', $cityId)
            ->whereIn('barangay_id', $coveredBarangayIds->all())
            ->orderBy('barangay_name')
            ->get(['barangay_id', 'barangay_name', 'city_id'])
            ->map(fn (Barangay $barangay) => [
                'barangay_id' => (int) $barangay->barangay_id,
                'barangay_name' => $barangay->barangay_name,
                'city_id' => (int) $barangay->city_id,
                'has_boundary' => $existingBoundaryBarangayIds->contains($barangay->barangay_id),
            ])
            ->values();

        return response()->json($barangays)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function importBarangayBoundary(
        Request $request,
        int $barangayId,
        GeoapifyBoundaryService $geoapifyBoundaryService,
        BoundaryManagementScope $scope,
    ): JsonResponse {
        $user = $scope->assertManager(Auth::user());
        $existingBoundary = BoundaryLocation::query()
            ->where('barangay_id', $barangayId)
            ->where('boundary_type', 'barangay')
            ->first();
        $stationId = $existingBoundary
            ? $scope->stationForBoundary($user, $existingBoundary)
            : $scope->stationForBarangay($user, $barangayId);

        $request->validate([
            'geometry' => ['nullable', Rule::in(['geometry_1000', 'geometry_5000', 'geometry_10000'])],
        ]);

        try {
            $boundary = $geoapifyBoundaryService->syncBarangayBoundary(
                $barangayId,
                $stationId,
                Auth::id(),
                (string) $request->input('geometry', config('services.geoapify.boundary_geometry', 'geometry_1000')),
                true,
            );
        } catch (BoundaryUnavailableException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Barangay boundary imported successfully.',
            'shape' => $this->serializeBoundary($boundary->fresh(['station', 'barangay.city'])),
            'boundary_version' => BoundaryLocation::mapCacheVersion(),
        ]);
    }

    public function locatePoint(Request $request, BoundaryMatcherService $boundaryMatcherService): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $boundary = $boundaryMatcherService->locateBarangayBoundary(
            (float) $validated['lat'],
            (float) $validated['lng']
        );

        if (! $boundary) {
            return response()->json([
                'success' => true,
                'matched' => false,
                'boundary' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'matched' => true,
            'boundary' => $this->serializeBoundary($boundary),
        ]);
    }

    protected function validateBoundaryPayload(Request $request, bool $isUpdate = false): array
    {
        $baseRules = [
            'name' => [$isUpdate ? 'sometimes' : 'nullable', 'string', 'max:255'],
            'station_id' => ['nullable', 'exists:stations,station_id'],
            'barangay_id' => ['nullable', 'exists:barangays,barangay_id'],
            'barangay_name' => ['nullable', 'string', 'max:255'],
            'boundary_type' => ['nullable', Rule::in(['custom', 'barangay', 'station'])],
            'source' => ['nullable', Rule::in(['manual', 'geoapify'])],
            'external_place_id' => ['nullable', 'string', 'max:255'],
            'properties' => ['nullable', 'array'],
        ];

        if ($request->has('geojson') || ! $isUpdate) {
            $baseRules['geojson'] = ['required'];
        } else {
            $baseRules['geojson'] = ['sometimes'];
        }

        return $request->validate($baseRules);
    }

    protected function normalizeBoundaryPayload(array $validated, ?BoundaryLocation $existing = null): array
    {
        $payload = [];

        if (array_key_exists('name', $validated)) {
            $payload['name'] = $validated['name'] ?: ($existing?->name ?: 'My Shape');
        } elseif (! $existing) {
            $payload['name'] = 'My Shape';
        }

        if (array_key_exists('geojson', $validated)) {
            $payload['geojson'] = $this->normalizeGeojson($validated['geojson']);
        }

        foreach (['station_id', 'barangay_id', 'boundary_type', 'source', 'external_place_id'] as $field) {
            if (array_key_exists($field, $validated)) {
                $payload[$field] = $validated[$field];
            }
        }

        if (! empty($validated['barangay_name']) && empty($payload['barangay_id'])) {
            $matchedBarangay = Barangay::query()
                ->where('barangay_name', 'like', $validated['barangay_name'])
                ->orWhere('barangay_name', 'like', '%' . $validated['barangay_name'] . '%')
                ->orderBy('barangay_name')
                ->first();

            if ($matchedBarangay) {
                $payload['barangay_id'] = $matchedBarangay->barangay_id;
                $payload['boundary_type'] = $payload['boundary_type'] ?? 'barangay';
            }
        }

        if (array_key_exists('properties', $validated)) {
            $payload['properties'] = $validated['properties'];
        }

        $payload['boundary_type'] = $payload['boundary_type']
            ?? $existing?->boundary_type
            ?? (! empty($payload['barangay_id']) ? 'barangay' : (! empty($payload['station_id']) ? 'station' : 'custom'));

        $payload['source'] = $payload['source'] ?? $existing?->source ?? 'manual';

        return $payload;
    }

    protected function normalizeGeojson(mixed $geojson): array
    {
        if (is_string($geojson)) {
            $decoded = json_decode($geojson, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        if (is_array($geojson)) {
            return $geojson;
        }

        abort(422, 'GeoJSON payload is invalid.');
    }

    protected function serializeBoundary(BoundaryLocation $shape): array
    {
        return [
            'id' => $shape->id,
            'name' => $shape->name,
            'geojson' => $shape->geojson,
            'station_id' => $shape->station_id,
            'barangay_id' => $shape->barangay_id,
            'boundary_type' => $shape->boundary_type,
            'source' => $shape->source,
            'external_place_id' => $shape->external_place_id,
            'properties' => $shape->properties,
            'station' => $shape->station,
            'barangay' => $shape->barangay,
            'created_at' => $shape->created_at,
            'updated_at' => $shape->updated_at,
        ];
    }

    protected function requiredBarangayId(array $payload): int
    {
        if (empty($payload['barangay_id'])) {
            throw ValidationException::withMessages([
                'barangay_id' => 'Select a barangay so station coverage can be verified.',
            ]);
        }

        return (int) $payload['barangay_id'];
    }

    protected function assertNoCompetingBoundary(
        BoundaryManagementScope $scope,
        \App\Models\User $user,
        int $barangayId,
        ?int $exceptBoundaryId = null,
    ): void {
        $existing = BoundaryLocation::query()
            ->where('barangay_id', $barangayId)
            ->when($exceptBoundaryId, fn ($query) => $query->whereKeyNot($exceptBoundaryId))
            ->first();

        if (! $existing) {
            return;
        }

        // Produces a 403 when the existing record belongs to another station.
        $scope->stationForBoundary($user, $existing);

        throw ValidationException::withMessages([
            'barangay_id' => 'This barangay already has a boundary. Edit the existing polygon instead.',
        ]);
    }
}
