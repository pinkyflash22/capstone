<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Equipments\EquipmentCategory;
use App\Models\Equipments\Equipment;
use App\Models\Station;
use App\Models\Personnel;



class EquipmentCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
          $equipmentcategory = EquipmentCategory::all();
        $equipments = Equipment::all();
        $activeStations = Station::with('person','coordinate.marker') ->get();
        $personnels = Personnel::with('station') ->get();

        // $countId = $personnels->stationBelong;
    //   foreach ($personnels as $person) {
    //      $countId =  $person->stationBelong;
        
    //     }
    //      $countperson = Personnel::with('station')
    //     ->where('stationBelong', $countId)
    //     ->get();



        return view('admin.resource-management', compact('equipmentcategory',
        'equipments','activeStations','personnels'));
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
            'category_name' => 'required|string|max:50' ,
        ]);

        $equipmentCategories = EquipmentCategory::create([
            //first 'lat' ito ung column table, ung nasaloob ng input is ito ung nasa model
            'category_name' => $request->input('category_name'),
        ]);

        return response()->json([
            'message' => 'category saved',
            'category_name' => $equipmentCategories->category_name,
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
