<?php

namespace App\Http\Controllers;

use App\Models\IncidentType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

class IncidentTypeController extends Controller
{
    public function index()
    {
        if (!Schema::hasTable('incident_type')) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $incidentTypes = IncidentType::all();
        return response()->json([
            'success' => true,
            'data' => $incidentTypes,
        ]);
    }

    public function create()
    {
        return view('admin.incidents.types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|unique:incident_type,type|max:255',
        ]);

        IncidentType::create($validated);

        return redirect()->route('incident-types.index')
            ->with('success', 'Incident type created successfully.');
    }

    public function show(IncidentType $incidentType)
    {
        $incidentType->load('incidents');
        return view('admin.incidents.types.show', compact('incidentType'));
    }

    public function edit(IncidentType $incidentType)
    {
        return view('admin.incidents.types.edit', compact('incidentType'));
    }

    public function update(Request $request, IncidentType $incidentType)
    {
        $validated = $request->validate([
            'type' => [
                'required',
                'string',
                'max:255',
                Rule::unique('incident_type', 'type')->ignore($incidentType->incident_type_id, 'incident_type_id'),
            ],
        ]);

        $incidentType->update($validated);

        return redirect()->route('incident-types.index')
            ->with('success', 'Incident type updated successfully.');
    }

    public function destroy(IncidentType $incidentType)
    {
        if ($incidentType->incidents()->count() > 0) {
            return back()->with('error', 'Cannot delete incident type with existing incidents.');
        }

        $incidentType->delete();

        return redirect()->route('incident-types.index')
            ->with('success', 'Incident type deleted successfully.');
    }
}
