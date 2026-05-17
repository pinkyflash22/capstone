<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Equipments\Equipment;
use App\Models\Equipments\EquipmentCategory;


class EquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        // $equips = Equipment::all();
        // return view('admin.resource-management', compact('equips'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            //model dito 
            'category_id' => 'required|integer|exists:equipment_categories,category_id' ,
            'name' =>'required|string|max:50'
        ]);

        $equipments = Equipment::create([
            //first 'lat' ito ung column table, ung nasaloob ng input is ito ung nasa model
            'category_id' => $request->input('category_id'),
            'name' => $request->input('name'),
         
        ]);

        return response()->json([
            'message' => 'category saved',
            'equipmentsaved' => $equipments->name,
        ]);

        return view('admin.resource-management');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
         $request->validate([
            //model dito 
            'category_name' => 'required|string|max:50' ,
        ]);

        $equipmentCategory = Equipment::create([
            //first 'lat' ito ung column table, ung nasaloob ng input is ito ung nasa model
            'category_name' => $request->input('category_name'),

        ]);

        return response()->json([
            'message' => 'station saved',
            'coordinate_id' => $equipmentCategory->category_name
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
