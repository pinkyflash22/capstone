<?php

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coordinate;

class CoordinateController extends Controller
{
    public function viewData(){
        return response()->json(Coordinate::all());
    }
    public function save(Request $request){

   $validation = $request -> validate([
        'marker_id' => 'required|exists:markers,marker_id', 
        'multimedia_id' => 'required|exists:multimedia,multimedia_id', 
        'status' => 'required|in:temporary,permanent,complete,demolished'
    ]);

        $state = Coordinate::create([$validation]);

        return response()->json([
            "success" => true,
            "coordinates " => $state
        ]);
    }
}
