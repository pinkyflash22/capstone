<?php

namespace App\Http\Controllers;

use App\Models\EquipmentCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Equipment;
use App\Models\Station;

class EquipmentCategoryController extends Controller
{
    public function index()
    {
        // $categories = EquipmentCategory::withCount('equipments')->paginate(15);
        // $equipmentcategory  = EquipmentCategory::all();
        // $equipments = Equipment::all();
        // $activeStations = Station::all();

        // return view('admin.resource-management', compact('equipmentcategory','equipments','activeStations'));
    }

    public function create()
    {
        return view('admin.equipment.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => 'required|string|unique:equipment_categories,category_name|max:255',
        ]);

      $data = EquipmentCategory::create($validated);

        return response()->json(['success'=>true, 'data'=>$data]);
    }

    public function show(EquipmentCategory $equipmentCategory)
    {
        $equipmentCategory->load('equipments');
        return view('admin.equipment.categories.show', compact('equipmentCategory'));
    }

    public function edit(EquipmentCategory $equipmentCategory)
    {
        return view('admin.equipment.categories.edit', compact('equipmentCategory'));
    }

    public function update(Request $request, EquipmentCategory $equipmentCategory)
    {
        $validated = $request->validate([
            'category_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('equipment_categories', 'category_name')->ignore($equipmentCategory->equipment_category_id, 'equipment_category_id'),
            ],
        ]);

        $equipmentCategory->update($validated);

        return redirect()->route('equipment-categories.index')
            ->with('success', 'Equipment category updated successfully.');
    }

    public function destroy(EquipmentCategory $equipmentCategory)
    {
        if ($equipmentCategory->equipments()->count() > 0) {
            return back()->with('error', 'Cannot delete category with existing equipment.');
        }

        $equipmentCategory->delete();

        return redirect()->route('equipment-categories.index')
            ->with('success', 'Equipment category deleted successfully.');
    }
}