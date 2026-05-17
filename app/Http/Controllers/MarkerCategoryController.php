<?php

namespace App\Http\Controllers;

use App\Models\MarkerCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarkerCategoryController extends Controller
{
    public function index()
    {
        $categories = MarkerCategory::withCount('markers')->paginate(15);
        return view('admin.map.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.map.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'marker_category' => 'required|string|unique:marker_categories,marker_category|max:255',
        ]);

        MarkerCategory::create($validated);

        return redirect()->route('marker-categories.index')
            ->with('success', 'Marker category created successfully.');
    }

    public function show(MarkerCategory $markerCategory)
    {
        $markerCategory->load('markers');
        return view('admin.map.categories.show', compact('markerCategory'));
    }

    public function edit(MarkerCategory $markerCategory)
    {
        return view('admin.map.categories.edit', compact('markerCategory'));
    }

    public function update(Request $request, MarkerCategory $markerCategory)
    {
        $validated = $request->validate([
            'marker_category' => [
                'required',
                'string',
                'max:255',
                Rule::unique('marker_categories', 'marker_category')->ignore($markerCategory->markerCategory_id, 'markerCategory_id'),
            ],
        ]);

        $markerCategory->update($validated);

        return redirect()->route('marker-categories.index')
            ->with('success', 'Marker category updated successfully.');
    }

    public function destroy(MarkerCategory $markerCategory)
    {
        if ($markerCategory->markers()->count() > 0) {
            return back()->with('error', 'Cannot delete category with existing markers.');
        }

        $markerCategory->delete();

        return redirect()->route('marker-categories.index')
            ->with('success', 'Marker category deleted successfully.');
    }

    public function categories()
    {
         $categories = MarkerCategory::all();
         return response()->json($categories);
    }
}