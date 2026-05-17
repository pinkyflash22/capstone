<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    public function index()
    {
        // $equipments = Equipment::with('category')->paginate(15);
        // return view('admin.equipment.index', compact('equipments'));
    }

    public function create()
    {
        $categories = EquipmentCategory::all();
        return view('admin.equipment.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipment_name' => 'required|string|unique:equipments,equipment_name|max:255',
            'equipment_category_id' => 'required|exists:equipment_categories,equipment_category_id',
        ]);

        $equipmentdetails = Equipment::create($validated);
        $equipments = Equipment::all();
        // return redirect()->route('admin.resource-management.index')->with('success', 'addedd!');
        return response()->json([
            'success' => true,
            'message' => 'Equipment added successfully',
            'data' => $equipmentdetails
        ]);
        // return response()->json(['success'=>true,'data'=>$equipmentdetails]);
    }

    public function show(Equipment $equipment)
    {
        $equipment->load('category', 'stationEquipments.station');
        return view('admin.equipment.show', compact('equipment'));
    }

    public function edit(Equipment $equipment)
    {
        $categories = EquipmentCategory::all();
        return view('admin.equipment.edit', compact('equipment', 'categories'));
    }

    public function update(Request $request, Equipment $equipment)
    {
        $validated = $request->validate([
            'equipment_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('equipments', 'equipment_name')->ignore($equipment->equipment_id, 'equipment_id'),
            ],
            'equipment_category_id' => 'required|exists:equipment_categories,equipment_category_id',
        ]);

        $equipment->update($validated);

        return redirect()->route('equipments.index')
            ->with('success', 'Equipment updated successfully.');
    }

    public function destroy(Equipment $equipment)
    {
        if ($equipment->stationEquipments()->count() > 0) {
            return back()->with('error', 'Cannot delete equipment assigned to stations.');
        }

        $equipment->delete();

        return redirect()->route('equipments.index')
            ->with('success', 'Equipment deleted successfully.');
    }
}