<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HydrantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
            'location_id' => 'required|exists:coordinates,coordinate_id' ,
            'station_id' => 'required|exists:stations,station_id',
            
        ]);

        $hydrants = Hydrant::create([
            //first 'lat' ito ung column table, ung nasaloob ng input is ito ung nasa model
            'location_id' => $request->input('location_id'),
            'station_id' => $request->input('station_id'),
        ]);

        return response()->json([
            'message' => 'Hydrant saved',
        'hydrant_id' => $hydrant->hydrant_id,
        'station_connected' => $hydrant->station_id
        ]);
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
