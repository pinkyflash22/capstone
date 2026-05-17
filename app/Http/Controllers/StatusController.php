<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StatusController extends Controller
{
    public function index()
    {
        $statuses = Status::withCount('markers')->paginate(15);
        return view('admin.map.statuses.index', compact('statuses'));
    }

    public function create()
    {
        return view('admin.map.statuses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'status_name' => 'required|string|unique:statuses,status_name|max:255',
        ]);

        Status::create($validated);

        return redirect()->route('statuses.index')
            ->with('success', 'Status created successfully.');
    }

    public function show(Status $status)
    {
        $status->load('markers');
        return view('admin.map.statuses.show', compact('status'));
    }

    public function edit(Status $status)
    {
        return view('admin.map.statuses.edit', compact('status'));
    }

    public function update(Request $request, Status $status)
    {
        $validated = $request->validate([
            'status_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('statuses', 'status_name')->ignore($status->status_id, 'status_id'),
            ],
        ]);

        $status->update($validated);

        return redirect()->route('statuses.index')
            ->with('success', 'Status updated successfully.');
    }

    public function destroy(Status $status)
    {
        if ($status->markers()->count() > 0) {
            return back()->with('error', 'Cannot delete status with existing markers.');
        }

        $status->delete();

        return redirect()->route('statuses.index')
            ->with('success', 'Status deleted successfully.');
    }
}