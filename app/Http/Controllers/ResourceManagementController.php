<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EquipmentCategory;
use App\Models\Equipment;
use App\Models\Station;
use App\Models\User;
use App\Models\Role;
use App\Models\StationOfficer;
use App\Models\Team;
use App\Models\Province;
use App\Models\Barangay;
use App\Models\BoundaryLocation;
use Illuminate\Support\Facades\Schema;



class ResourceManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $routeName = request()->routeIs('admin.*')
            ? 'admin.resource-management.index'
            : 'station-admin.resource-management.index';

        return redirect()->route($routeName);
    }

    protected function buildBoundaryBarangayList()
    {
        $supportsBarangayBoundaries = Schema::hasColumn('boundary_location', 'barangay_id')
            && Schema::hasColumn('boundary_location', 'boundary_type');

        $barangays = Barangay::query()
            ->with($supportsBarangayBoundaries ? ['city', 'boundaryLocations'] : ['city'])
            ->orderBy('barangay_name')
            ->get();

        return $barangays->map(function (Barangay $barangay) {
            $boundary = null;

            if (isset($barangay->boundaryLocations)) {
                $boundary = $barangay->boundaryLocations
                    ->where('boundary_type', 'barangay')
                    ->sortByDesc('updated_at')
                    ->first();
            }

            $boundaryState = $this->buildBoundaryState($barangay, $boundary);

            return [
                'barangay_id' => $barangay->barangay_id,
                'barangay_name' => $barangay->barangay_name,
                'city_name' => $barangay->city?->city_name,
                'has_boundary' => $boundaryState['has_boundary'],
                'boundary_is_valid' => $boundaryState['is_valid'],
                'boundary_status' => $boundaryState['status'],
                'boundary_status_label' => $boundaryState['label'],
                'boundary_status_icon' => $boundaryState['icon'],
                'boundary_source' => $boundary?->source,
                'boundary_updated_at' => $boundary?->updated_at,
                'boundary_updated_at_human' => $boundary?->updated_at?->diffForHumans(),
                'boundary_name' => $boundary?->name,
                'external_place_id' => $boundary?->external_place_id,
            ];
        })->values();
    }

    protected function buildBoundaryState(Barangay $barangay, ?BoundaryLocation $boundary): array
    {
        if (! $boundary) {
            return [
                'has_boundary' => false,
                'is_valid' => false,
                'status' => 'missing',
                'label' => 'Needs Sync',
                'icon' => 'fa-clock',
            ];
        }

        $properties = (array) ($boundary->properties ?? []);
        $geoapifyFeature = (array) ($properties['geoapify_feature'] ?? []);
        $geoapifyName = trim((string) ($geoapifyFeature['name'] ?? ''));
        $expectedBarangay = trim((string) $barangay->barangay_name);
        $expectedCity = trim((string) ($barangay->city?->city_name ?? ''));

        if (
            $boundary->source === 'geoapify'
            && $geoapifyName !== ''
            && strcasecmp($geoapifyName, $expectedBarangay) !== 0
            && ($expectedCity === '' || strcasecmp($geoapifyName, $expectedCity) === 0)
        ) {
            return [
                'has_boundary' => true,
                'is_valid' => false,
                'status' => 'invalid',
                'label' => 'Invalid Boundary',
                'icon' => 'fa-triangle-exclamation',
            ];
        }

        return [
            'has_boundary' => true,
            'is_valid' => true,
            'status' => 'synced',
            'label' => 'Boundary Ready',
            'icon' => 'fa-circle-check',
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
