<?php

namespace App\Http\Controllers\Api;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Marker;
use App\Support\MarkerFeedBuilder;

class MarkerController extends Controller
{
    // GET markers
    public function index()
    {
        $markers = Cache::remember('map:markers:index:v3_public', now()->addSeconds(20), function () {
            return MarkerFeedBuilder::buildForViewer(null)->values()->all();
        });

        return response()->json($markers);
    }

    // SAVE marker
    public function store(Request $request)//: RedirectResponse
    {
        //storing papunta database
        $request->validate([
            //model dito 
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'markerCategory_id' => 'required|exists:marker_categories,markerCategory_id',
            'multimedia_id' => 'nullable|exists:multimedias,multimedia_id',
            
        ]);

        $marker = Marker::create([
            //first 'lat' ito ung column table, ung nasaloob ng input is ito ung nasa model
            'lat' => $request->input('lat'),
            'lng' => $request->input('lng'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'markerCategory_id' => $request->input('markerCategory_id'),
            'multimedia_id' => $request->input('multimedia_id'),
        ]);

        Cache::forget('map:markers:index:v2');
        Cache::forget('map:markers:index:v3_public');

        return response()->json([
            'message' => 'marker saved',
            'marker_id' => $marker->marker_id
        ]);
    }
     public function update(Request $request, string $id)
    {
        $marker = Marker::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'markerCategory_id' => 'nullable|exists:marker_categories,markerCategory_id',
        ]);

        $marker->update($request->all());
        Cache::forget('map:markers:index:v2');
        Cache::forget('map:markers:index:v3_public');
        return redirect()->back()->with('success', 'Marker updated!');
    }
    public function destroy($id){
         $marker = Marker::findOrFail($id);
    $marker->delete();
    Cache::forget('map:markers:index:v2');
    Cache::forget('map:markers:index:v3_public');

    return response()->json([
        'message' => 'Marker deleted successfully'
    ]);
    }
    public function latest()
    {
        $latestMarker = \App\Models\Marker::query()
            ->orderByDesc('updated_at')
            ->orderByDesc('marker_id')
            ->first();

        return response()->json([
            'marker_id' => $latestMarker?->marker_id,
            'updated_at' => optional($latestMarker?->updated_at)->toISOString(),
            'count' => \App\Models\Marker::count(),
        ]);
    }

    //  public function image()
    // {
    //     $markers = \App\Models\Multimedia::latest()->get();

    //     return response()->json($markers);
    // }
}
