<?php

namespace App\Http\Controllers;

use App\Models\Hydrant;

use App\Models\Marker;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HydrantController extends Controller
{
    public function index()
    {
        $hydrants = Hydrant::with( 'marker', 'station')
            ->get();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $hydrants,
            ]);
        }

        return view('admin.hydrants.index', compact('hydrants'));
    }

    public function create()
    {
        $hydrantTypes = HydrantType::all();
        $markers = Marker::all();
        $stations = Station::all();
        return view('admin.hydrants.create', compact('hydrantTypes', 'markers', 'stations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'hydrant_code' => 'required|string|unique:hydrants,hydrant_code|max:100',
            'hydrant_type_id' => 'nullable|exists:hydrant_types,hydrant_type_id',
            'marker_id' => 'required|exists:markers,marker_id',
            'station_id' => 'required|exists:stations,station_id',
        ]);

       $hydrantData = Hydrant::create($validated);

        return response()->json(['success' => true, 'data'=>$hydrantData]);
    }

    public function show(Hydrant $hydrant)
    {
        $hydrant->load('hydrantType', 'marker', 'station');
        return view('admin.hydrants.show', compact('hydrant'));
    }

    public function edit(Hydrant $hydrant)
    {
        $hydrantTypes = HydrantType::all();
        $markers = Marker::all();
        $stations = Station::all();
        return view('admin.hydrants.edit', compact('hydrant', 'hydrantTypes', 'markers', 'stations'));
    }

    public function update(Request $request, Hydrant $hydrant)
    {
        $validated = $request->validate([
            'hydrant_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('hydrants', 'hydrant_code')->ignore($hydrant->hydrant_id, 'hydrant_id'),
            ],
            'hydrant_type_id' => 'nullable|exists:hydrant_types,hydrant_type_id',
            'marker_id' => 'required|exists:markers,marker_id',
            'station_id' => 'required|exists:stations,station_id',
        ]);

        $hydrant->update($validated);

        return redirect()->route('hydrants.index')
            ->with('success', 'Hydrant updated successfully.');
    }

    public function getByMarker($markerId)
    {
        $hydrant = Hydrant::with(['station', 'marker', 'hydrantType'])
            ->where('marker_id', $markerId)
            ->first();

        if (!$hydrant) {
            return response()->json(['error' => 'Hydrant not found'], 404);
        }

        return response()->json($hydrant);
    }

    public function updateByMarker(Request $request, $markerId)
    {
        $hydrant = Hydrant::where('marker_id', $markerId)->first();

        if (!$hydrant) {
            return response()->json(['error' => 'Hydrant not found'], 404);
        }

        $validated = $request->validate([
            'hydrant_code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('hydrants', 'hydrant_code')->ignore($hydrant->hydrant_id, 'hydrant_id'),
            ],
            'station_id' => 'nullable|exists:stations,station_id',
            'hydrant_type_id' => 'nullable|exists:hydrant_types,hydrant_type_id',
        ]);

        $hydrant->update($validated);

        return response()->json([
            'success' => true,
            'data' => $hydrant->fresh(['station', 'marker', 'hydrantType']),
        ]);
    }

    public function destroy(Hydrant $hydrant)
    {
        $hydrant->delete();

        return redirect()->route('hydrants.index')
            ->with('success', 'Hydrant deleted successfully.');
    }
}
