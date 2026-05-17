<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::withCount('firefighters')->paginate(15);
        return view('admin.incidents.shifts.index', compact('shifts'));
    }

    public function create()
    {
        return view('admin.incidents.shifts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift_name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        Shift::create($validated);

        return redirect()->route('shifts.index')
            ->with('success', 'Shift created successfully.');
    }

    public function show(Shift $shift)
    {
        $shift->load('firefighters');
        return view('admin.incidents.shifts.show', compact('shift'));
    }

    public function edit(Shift $shift)
    {
        return view('admin.incidents.shifts.edit', compact('shift'));
    }

    public function update(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'shift_name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $shift->update($validated);

        return redirect()->route('shifts.index')
            ->with('success', 'Shift updated successfully.');
    }

    public function destroy(Shift $shift)
    {
        if ($shift->firefighters()->count() > 0) {
            return back()->with('error', 'Cannot delete shift with assigned firefighters.');
        }

        $shift->delete();

        return redirect()->route('shifts.index')
            ->with('success', 'Shift deleted successfully.');
    }
}