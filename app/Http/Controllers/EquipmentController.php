<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    private const EQUIPMENT_CATEGORIES = [
        'Personal Protective Equipment',
        'firefighting equipment',
        'rescue equipment',
        'communication equipment',
        'vehicles',
    ];

    private const EQUIPMENT_TYPES = [
        'disposable',
        'repairable',
    ];

    public function index()
    {
        // $equipments = Equipment::with('category')->paginate(15);
        // return view('admin.equipment.index', compact('equipments'));
    }

    public function create()
    {
        return view('admin.equipment.create', [
            'categories' => self::EQUIPMENT_CATEGORIES,
            'types' => self::EQUIPMENT_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipment_name' => 'required|string|unique:equipments,equipment_name|max:255',
            'category' => ['nullable', Rule::in(self::EQUIPMENT_CATEGORIES)],
            'type' => ['nullable', Rule::in(self::EQUIPMENT_TYPES)],
        ]);

        $equipmentdetails = Equipment::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Equipment added successfully',
            'data' => $equipmentdetails
        ]);
    }

    public function show(Equipment $equipment)
    {
        return view('admin.equipment.show', compact('equipment'));
    }

    public function edit(Equipment $equipment)
    {
        return view('admin.equipment.edit', [
            'equipment' => $equipment,
            'categories' => self::EQUIPMENT_CATEGORIES,
            'types' => self::EQUIPMENT_TYPES,
        ]);
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
            'category' => ['nullable', Rule::in(self::EQUIPMENT_CATEGORIES)],
            'type' => ['nullable', Rule::in(self::EQUIPMENT_TYPES)],
        ]);

        $equipment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Equipment updated successfully.',
            'data' => $equipment->fresh(),
        ]);
    }

    public function destroy(Equipment $equipment)
    {
        if ($equipment->stationEquipments()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete equipment assigned to stations.',
            ], 422);
        }

        $equipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Equipment deleted successfully.',
        ]);
    }
}
