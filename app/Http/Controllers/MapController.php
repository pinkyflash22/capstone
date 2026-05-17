<?php
namespace App\Http\Controllers;
use App\Models\Marker;
use Illuminate\Http\Request;
class MapController extends Controller
{
    public function index()
    {
        return view('markers.map');
    }

     public function store(Request $request)
    {
        Marker::create([
            'title' => $request->title,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return redirect()->back()->with('success', 'Marker saved!');
    }

    //    public function show()
    // {
       
    // }
}