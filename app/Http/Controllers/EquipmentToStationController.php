<?php

namespace App\Http\Controllers;

use App\Models\EquipmentToStation;
use App\Models\Station;
use App\Models\Equipment;
use Illuminate\Http\Request;

class EquipmentToStationController extends Controller
{
    public function index()
    {
        $equipmentStations = EquipmentToStation::with('station', 'equipment')
            ->paginate(15);
        return view('admin.equipment.stations.index', compact('equipmentStations'));
    }

    public function create()
    {
        $stations = Station::all();
        $equipments = Equipment::all();
        return view('admin.equipment.stations.create', compact('stations', 'equipments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'station_id' => 'required|exists:stations,station_id',
            'equipment_id' => 'required|exists:equipments,equipment_id',
            'available_quantity' => 'required|integer|min:0',
            'damaged_quantity' => 'required|integer|min:0',
            'maintenance_quantity' => 'required|integer|min:0',
        ]);

        EquipmentToStation::create($validated);

        return redirect()->route('equipment-stations.index')
            ->with('success', 'Equipment assigned to station successfully.');
    }

    public function show(EquipmentToStation $equipmentStation)
    {
        $equipmentStation->load('station', 'equipment');
        return view('admin.equipment.stations.show', compact('equipmentStation'));
    }

    public function edit(EquipmentToStation $equipmentStation)
    {
        $stations = Station::all();
        $equipments = Equipment::all();
        return view('admin.equipment.stations.edit', compact('equipmentStation', 'stations', 'equipments'));
    }

    public function update(Request $request, EquipmentToStation $equipmentStation)
    {
        $validated = $request->validate([
            'station_id' => 'required|exists:stations,station_id',
            'equipment_id' => 'required|exists:equipments,equipment_id',
            'available_quantity' => 'required|integer|min:0',
            'damaged_quantity' => 'required|integer|min:0',
            'maintenance_quantity' => 'required|integer|min:0',
        ]);

        $equipmentStation->update($validated);

        return redirect()->route('equipment-stations.index')
            ->with('success', 'Equipment allocation updated successfully.');
    }

    public function destroy(EquipmentToStation $equipmentStation)
    {
        $equipmentStation->delete();

        return redirect()->route('equipment-stations.index')
            ->with('success', 'Equipment allocation removed successfully.');
    }
}