<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\FullAddress;
use App\Models\Marker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class StationController extends Controller
{
    // public function index()
    // {
    //     // $stations = Station::with('address.barangay.city.province', 'marker')
    //     //     ->paginate(15);
    //     $stations = Station::with('marker')->get();
    //     // return view('admin.stations.index', compact('stations'));

    //     return response()->json($stations);
    // }
// In your StationController or relevant controller
public function index()
{
    try {
        $stations = Cache::remember('map:stations:index:v2', now()->addSeconds(45), function () {
            return Station::query()
                ->select([
                    'station_id',
                    'station_name',
                    'station_code',
                    'contact_number',
                    'email',
                    'address_id',
                    'marker_id',
                    'created_at',
                    'updated_at',
                ])
                ->with([
                    'marker:marker_id,title,description,lat,lng,status,category,image,markerCategory_id,created_at,updated_at',
                    'address:address_id,street,barangay_id',
                    'address.barangay:barangay_id,barangay_name,city_id',
                    'address.barangay.city:city_id,city_name,province_id',
                    'address.barangay.city.province:province_id,province_name',
                ])
                ->orderBy('station_name')
                ->get();
        });
        
        if ($stations->isEmpty()) {
            // Return empty array with success status
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No stations found'
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => $stations,
            'stationData' => $stations // For backward compatibility
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Station API error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error fetching stations',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function create()
    {
        $addresses = FullAddress::with('barangay.city.province')->get();
        $markers = Marker::all();
        return view('admin.stations.create', compact('addresses', 'markers'));
    }

// In StationController.php

public function getByMarker($markerId)
{
    $station = Station::where('marker_id', $markerId)->first();
    if (!$station) {
        return response()->json(['error' => 'Station not found'], 404);
    }
    return response()->json($station);
}

public function updateByMarker(Request $request, $markerId)
{
    $station = Station::where('marker_id', $markerId)->first();
    if (!$station) {
        return response()->json(['error' => 'Station not found'], 404);
    }
    
    $station->update($request->only(['station_name', 'station_code', 'contact_number', 'email']));
    Cache::forget('map:stations:index:v2');
    
    return response()->json(['success' => true, 'data' => $station]);
}



    public function store(Request $request)
    {
        $validated = $request->validate([
            'station_name' => 'required|string|max:255',
            'station_code' => 'required|string|unique:stations,station_code|max:50',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:stations,email',
            'address_id' => 'nullable|exists:full_addresses,address_id',
            'marker_id' => 'required|exists:markers,marker_id',
        ]);

       $data = Station::create($validated);
        Cache::forget('map:stations:index:v2');
        return response()->json(['success'=>true, 'data'=>$data]);
        // return redirect()->route('admin.dashboard')
        //     ->with('success', 'Station created successfully.');
    }

    public function show(Station $station)
    {
        $station->load('address.barangay.city.province', 'marker', 'hydrants', 'teams');
        return view('admin.stations.show', compact('station'));
    }

    public function edit(Station $station)
    {
        $addresses = FullAddress::with('barangay.city.province')->get();
        $markers = Marker::all();
        return view('admin.stations.edit', compact('station', 'addresses', 'markers'));
    }

    public function update(Request $request, Station $station)
    {
        $validated = $request->validate([
            'station_name' => 'required|string|max:255',
            'station_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('stations', 'station_code')->ignore($station->station_id, 'station_id'),
            ],
            'contact_number' => 'nullable|string|max:20',
            'email' => [
                'nullable',
                'email',
                Rule::unique('stations', 'email')->ignore($station->station_id, 'station_id'),
            ],
            'address_id' => 'nullable|exists:full_addresses,address_id',
            'marker_id' => 'required|exists:markers,marker_id',
        ]);

        $station->update($validated);
        Cache::forget('map:stations:index:v2');

        return redirect()->route('stations.index')
            ->with('success', 'Station updated successfully.');
    }

    public function destroy(Station $station)
    {
        if ($station->hydrants()->count() > 0 || 
            $station->teams()->count() > 0 ||
            $station->equipmentStations()->count() > 0) {
            return back()->with('error', 'Cannot delete station with existing related records.');
        }

        $station->delete();
        Cache::forget('map:stations:index:v2');

        return redirect()->route('stations.index')
            ->with('success', 'Station deleted successfully.');
    }
}
