<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\BoundaryLocation;
use App\Models\Station;
use App\Services\BoundaryMatcherService;
use App\Services\GeoapifyBoundaryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BoundaryLocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'boundary_type' => ['nullable', 'string', 'max:50'],
            'barangay_id' => ['nullable', 'exists:barangays,barangay_id'],
            'station_id' => ['nullable', 'exists:stations,station_id'],
        ]);

        $shapes = BoundaryLocation::query()
            ->with(['station', 'barangay.city'])
            ->when(! empty($validated['boundary_type']), fn ($query) => $query->where('boundary_type', $validated['boundary_type']))
            ->when(! empty($validated['barangay_id']), fn ($query) => $query->where('barangay_id', $validated['barangay_id']))
            ->when(! empty($validated['station_id']), fn ($query) => $query->where('station_id', $validated['station_id']))
            ->orderBy('name')
            ->get()
            ->map(fn (BoundaryLocation $shape) => $this->serializeBoundary($shape));

        return response()->json($shapes);
    }

    public function create()
    {
        $stations = Station::orderBy('station_name')->get();

        return view('admin.boundaries.create', compact('stations'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateBoundaryPayload($request);

        $shape = BoundaryLocation::create($this->normalizeBoundaryPayload($validated));

        return response()->json([
            'success' => true,
            'shape' => $this->serializeBoundary($shape->fresh(['station', 'barangay.city'])),
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

    public function update(Request $request, int $id): JsonResponse
    {
        $boundary = BoundaryLocation::query()->findOrFail($id);
        $validated = $this->validateBoundaryPayload($request, true);

        $boundary->update($this->normalizeBoundaryPayload($validated, $boundary));

        return response()->json([
            'success' => true,
            'shape' => $this->serializeBoundary($boundary->fresh(['station', 'barangay.city'])),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $shape = BoundaryLocation::query()->findOrFail($id);
        $shape->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shape deleted',
        ]);
    }

    public function getMapBoundaries(): JsonResponse
    {
        return $this->index(request());
    }

    public function importBarangayBoundary(
        Request $request,
        int $barangayId,
        GeoapifyBoundaryService $geoapifyBoundaryService
    ): JsonResponse {
        $this->authorizeBoundarySync();

        $request->validate([
            'geometry' => ['nullable', Rule::in(['geometry_1000', 'geometry_5000', 'geometry_10000'])],
        ]);

        $barangay = Barangay::query()
            ->with(['hall.marker', 'city'])
            ->findOrFail($barangayId);

        $marker = $barangay->hall?->marker;

        if (! $marker || $marker->lat === null || $marker->lng === null) {
            return response()->json([
                'success' => false,
                'message' => 'The selected barangay does not have a hall marker with coordinates yet.',
            ], 422);
        }

        $boundaryPayload = $geoapifyBoundaryService->importBarangayBoundary(
            $barangay,
            (float) $marker->lat,
            (float) $marker->lng,
            (string) $request->input('geometry', 'geometry_1000')
        );

        $feature = $boundaryPayload['feature'];
        $properties = (array) ($feature['properties'] ?? []);

        $boundary = BoundaryLocation::query()->updateOrCreate(
            [
                'barangay_id' => $barangay->barangay_id,
                'boundary_type' => 'barangay',
            ],
            [
                'name' => $barangay->barangay_name,
                'station_id' => null,
                'geojson' => $boundaryPayload['feature_collection'],
                'source' => 'geoapify',
                'external_place_id' => $properties['place_id'] ?? $properties['id'] ?? null,
                'properties' => [
                    'geoapify_feature' => $properties,
                    'imported_at' => now()->toISOString(),
                    'imported_by' => Auth::id(),
                ],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Barangay boundary imported successfully.',
            'shape' => $this->serializeBoundary($boundary->fresh(['station', 'barangay.city'])),
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

    protected function authorizeBoundarySync(): void
    {
        $user = Auth::user();
        $role = strtolower(trim((string) ($user?->role->role ?? '')));

        if (! in_array($role, ['admin', 'chief', 'station admin'], true)) {
            throw new AuthorizationException('You are not allowed to sync barangay boundaries.');
        }
    }
}
