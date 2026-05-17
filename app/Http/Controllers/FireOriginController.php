<?php

namespace App\Http\Controllers;

use App\Models\FireOrigin;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FireOriginController extends Controller
{
    public function index()
    {
        $fireOrigins = FireOrigin::withCount('progressInvestigations')->paginate(15);
        return view('admin.investigations.fire-origins.index', compact('fireOrigins'));
    }

    public function create()
    {
        return view('admin.investigations.fire-origins.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'origin' => 'required|string|unique:fire_origin,origin|max:255',
        ]);

        FireOrigin::create($validated);

        return redirect()->route('fire-origins.index')
            ->with('success', 'Fire origin created successfully.');
    }

    public function show(FireOrigin $fireOrigin)
    {
        $fireOrigin->load('progressInvestigations');
        return view('admin.investigations.fire-origins.show', compact('fireOrigin'));
    }

    public function edit(FireOrigin $fireOrigin)
    {
        return view('admin.investigations.fire-origins.edit', compact('fireOrigin'));
    }

    public function update(Request $request, FireOrigin $fireOrigin)
    {
        $validated = $request->validate([
            'origin' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fire_origin', 'origin')->ignore($fireOrigin->fire_origin_id, 'fire_origin_id'),
            ],
        ]);

        $fireOrigin->update($validated);

        return redirect()->route('fire-origins.index')
            ->with('success', 'Fire origin updated successfully.');
    }

    public function destroy(FireOrigin $fireOrigin)
    {
        if ($fireOrigin->progressInvestigations()->count() > 0) {
            return back()->with('error', 'Cannot delete fire origin with existing investigations.');
        }

        $fireOrigin->delete();

        return redirect()->route('fire-origins.index')
            ->with('success', 'Fire origin deleted successfully.');
    }
}