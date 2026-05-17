<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Marker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
class MarkController extends Controller
{
    // GET markers
    public function index()
    {
        //dito nilalagyan ng laman ung model from database
        //  $datas = Marker::with('category')->get();
        return response()->json(Marker::all());
    }

    // SAVE marker
    public function store(Request $request)// : RedirectResponse
    {
        //storing papunta database
        $request->validate([
            //model dito 
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'markerCategory_id' => 'nullable|exists:marker_categories,markerCategory_id',
            
        ]);

        $marker = Marker::create([
            //first 'lat' ito ung column table, ung nasaloob ng input is ito ung nasa model
            'lat' => $request->input('lat'),
            'lng' => $request->input('lng'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'markerCategory_id' => $request->input('markerCategory_id'),
        ]);

        return response()->json([
             'message' => 'marker saved',
            'data' => $marker
        ]);
    }

    public function destroy($id){
         $marker = Marker::findOrFail($id);
    $marker->delete();

    return response()->json([
        'message' => 'Marker deleted successfully'
    ]);
    }
    public function latest()
    {
        $markers = \App\Models\Marker::latest()->get();

        return response()->json($markers);
    }
}