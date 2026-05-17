<?php

namespace App\Http\Controllers;

use App\Models\IncidentStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncidentStatusController extends Controller
{
    public function index()
    {
        $incidentStatuses = IncidentStatus::withCount('incidents')->paginate(15);
        return view('admin.incidents.statuses.index', compact('incidentStatuses'));
    }

    public function create()
    {
        return view('admin.incidents.statuses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|string|unique:incident_status,status|max:255',
        ]);

        IncidentStatus::create($validated);

        return redirect()->route('incident-statuses.index')
            ->with('success', 'Incident status created successfully.');
    }

    public function show(IncidentStatus $incidentStatus)
    {
        $incidentStatus->load('incidents');
        return view('admin.incidents.statuses.show', compact('incidentStatus'));
    }

    public function edit(IncidentStatus $incidentStatus)
    {
        return view('admin.incidents.statuses.edit', compact('incidentStatus'));
    }

    public function update(Request $request, IncidentStatus $incidentStatus)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                'max:255',
                Rule::unique('incident_status', 'status')->ignore($incidentStatus->incident_status_id, 'incident_status_id'),
            ],
        ]);

        $incidentStatus->update($validated);

        return redirect()->route('incident-statuses.index')
            ->with('success', 'Incident status updated successfully.');
    }

    public function destroy(IncidentStatus $incidentStatus)
    {
        if ($incidentStatus->incidents()->count() > 0) {
            return back()->with('error', 'Cannot delete incident status with existing incidents.');
        }

        $incidentStatus->delete();

        return redirect()->route('incident-statuses.index')
            ->with('success', 'Incident status deleted successfully.');
    }
}