<?php

namespace App\Http\Controllers;

use App\Models\HydrantType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HydrantTypeController extends Controller
{
    public function index()
    {
        if (request()->expectsJson() || request()->is('api/*')) {
            $hydrantTypes = HydrantType::orderBy('type')->get();

            return response()->json([
                'success' => true,
                'data' => $hydrantTypes,
            ]);
        }

        $hydrantTypes = HydrantType::withCount('hydrants')->paginate(15);
        return view('admin.hydrants.types.index', compact('hydrantTypes'));
    }

    public function create()
    {
        return view('admin.hydrants.types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|unique:hydrant_types,type|max:255',
        ]);

        HydrantType::create($validated);

        return redirect()->route('hydrant-types.index')
            ->with('success', 'Hydrant type created successfully.');
    }

    public function show(HydrantType $hydrantType)
    {
        $hydrantType->load('hydrants');
        return view('admin.hydrants.types.show', compact('hydrantType'));
    }

    public function edit(HydrantType $hydrantType)
    {
        return view('admin.hydrants.types.edit', compact('hydrantType'));
    }

    public function update(Request $request, HydrantType $hydrantType)
    {
        $validated = $request->validate([
            'type' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hydrant_types', 'type')->ignore($hydrantType->hydrant_type_id, 'hydrant_type_id'),
            ],
        ]);

        $hydrantType->update($validated);

        return redirect()->route('hydrant-types.index')
            ->with('success', 'Hydrant type updated successfully.');
    }

    public function destroy(HydrantType $hydrantType)
    {
        if ($hydrantType->hydrants()->count() > 0) {
            return back()->with('error', 'Cannot delete hydrant type with existing hydrants.');
        }

        $hydrantType->delete();

        return redirect()->route('hydrant-types.index')
            ->with('success', 'Hydrant type deleted successfully.');
    }
}
