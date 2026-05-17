<?php

// namespace App\Http\Controllers\Admin;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use App\Models\Station;

// class StationController extends Controller
// {
//     /**
//      * Display a listing of the resource.
//      */
//     public function index()
//     {
//             // $allStation = Station::all();
//            $allStation = Station::with('coordinate.marker')->get();
//             return response()->json([
//             'stationData' => $allStation
//         ]);
//     }

//     /**
//      * Show the form for creating a new resource.
//      */
//     public function create()
//     {
//         //
//     }

//     /**
//      * Store a newly created resource in storage.
//      */
//     public function store(Request $request)
//     {
//          $request->validate([
//             //model dito 
//             'station_name' => 'required|string' ,
//             'station_chief' => 'required|string',
//             'coordinate_id' => 'required|exists:coordinates,coordinate_id',
            
//         ]);

//         $stations = Station::create([
//             //first 'lat' ito ung column table, ung nasaloob ng input is ito ung nasa model
//             'station_name' => $request->input('station_name'),
//             'station_chief' => $request->input('station_chief'),
//             'coordinate_id' => $request->input('coordinate_id'),
//         ]);

//         return response()->json([
//             'message' => 'station saved',
//             'coordinate_id' => $stations->coordinate_id
//         ]);
//     }

//     /**
//      * Display the specified resource.
//      */
//     public function show(string $id)
//     {
//         //
//     }

//     /**
//      * Show the form for editing the specified resource.
//      */
//     public function edit(string $id)
//     {
//         //
//     }

//     /**
//      * Update the specified resource in storage.
//      */
//     public function update(Request $request, string $id)
//     {
//         //
//     }

//     /**
//      * Remove the specified resource from storage.
//      */
//     public function destroy(string $id)
//     {
//         //
//     }
// }
