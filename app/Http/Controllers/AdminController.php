<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Marker;
use App\Models\MarkerCategory;
use App\Models\StationOfficer;

use App\Models\Incident;
use App\Models\IncidentStatus;

use App\Models\Role;
use App\Models\User;
use App\Models\Station;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use App\Models\FullAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        $categories = MarkerCategory::all();
        $mapmarker = Marker::with('category','stations')->get();
        $incidents = Incident::with(['marker','incidentType'])->get();
        $alerts = Incident::with('marker')->get();
        $stations = Station::all();
        $provinces = Province::orderBy('province_name')->get();
        $stationperson = StationOfficer::with('station','station.users')->get();
        // Get all users with role 'chief'
        $chiefRole = Role::where('role', 'chief')->first();
        $chiefs = $chiefRole ? User::where('role_id', $chiefRole->role_id)->get() : collect();
        $hydrantTypes = \App\Models\HydrantType::all();
        return view('admin.dashboard', compact('categories', 'mapmarker', 'incidents', 'alerts', 'stations', 'chiefs', 'provinces','stationperson','hydrantTypes'));
    }

    //for reports

    public function reports()
    {
        $categories = MarkerCategory::all();
        $mapmarker = Marker::with('category','stations')->get();
        $incidents = Incident::with(['marker','incidentType', 'incidentStatus','team.station'])->get();
        $alerts = Incident::with('marker')->get();
        $stations = Station::with(['marker.status', 'users'])->get();
        $provinces = Province::orderBy('province_name')->get();
        $stationperson = StationOfficer::with('station','station.users')->get();
        // Get all users with role 'chief'
        $chiefRole = Role::where('role', 'chief')->first();
        $incidentStatuses = IncidentStatus::all();

        $chiefs = $chiefRole ? User::where('role_id', $chiefRole->role_id)->get() : collect();
        return view('admin.reports', compact('categories','incidentStatuses', 'mapmarker', 'incidents', 'alerts', 'stations', 'chiefs', 'provinces','stationperson'));

    }

    public function findex(){
         $categories = MarkerCategory::all();
        $mapmarker = Marker::with('category','stations')->get();
        $incidents = Incident::with(['marker','incidentType'])->get();
        $alerts = Incident::with('marker')->get();
        $stations = Station::all();
        $provinces = Province::orderBy('province_name')->get();
        $stationperson = StationOfficer::with('station','station.users')->get();
        // Get all users with role 'chief'
        $chiefRole = Role::where('role', 'chief')->first();
        $chiefs = $chiefRole ? User::where('role_id', $chiefRole->role_id)->get() : collect();
        return view('firefighters.dashboard', compact('categories', 'mapmarker', 'incidents', 'alerts', 'stations', 'chiefs', 'provinces','stationperson'));

    }

    public function alerts(){
        $incidents = Incident::all();
        return view('admin.alerts',compact('incidents'));
    }
    public function maps()
    {   
        $mapmarker = Marker::with('category')->get();
        return view('admin.incidentmap', compact('mapmarker'));
    }

    /**
     * Get cities by province (AJAX)
     */
    public function getCities($provinceId)
    {
        $cities = City::where('province_id', $provinceId)
            ->orderBy('city_name')
            ->get();
        
        return response()->json($cities);
    }

    /**
     * Get barangays by city (AJAX)
     */
    public function getBarangays($cityId)
    {
        $barangays = Barangay::where('city_id', $cityId)
            ->orderBy('barangay_name')
            ->get();
        
        return response()->json($barangays);
    }
public function storeFullAddress(Request $request)
{
    try {
        $address = FullAddress::create([
            'street' => $request->street,
            'barangay_id' => $request->barangay_id,
            'postal_id' => $request->postal_id,
        ]);
        
        return response()->json([
            'success' => true,
            'address_id' => $address->address_id,
            'message' => 'Address created successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error creating address: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Store marker with station and address
     */
    public function storeMarker(Request $request)
    {
      try {
        DB::beginTransaction();

        // Create the marker
        $marker = Marker::create([
            'title' => $request->title,
            'description' => $request->description,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'markerCategory_id' => $request->markerCategory_id,
            'status_id' => 1, // Default active status
        ]);

        // If station category and station data exists
        if ($request->has('station_data')) {
            $stationData = $request->station_data;
            
            // Create station with the address_id from the request
            $station = Station::create([
                'station_name' => $stationData['station_name'],
                'station_code' => $stationData['station_code'],
                'contact_number' => $stationData['contact_number'],
                'email' => $stationData['email'],
                'address_id' => $stationData['address_id'] ?? null, // Use the address_id from the request
                'marker_id' => $marker->marker_id,
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Marker and station saved successfully',
            'marker' => $marker
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
        // try {
        //     DB::beginTransaction();

        //     // Create the marker
        //     $marker = Marker::create([
        //         'title' => $request->title,
        //         'description' => $request->description,
        //         'lat' => $request->lat,
        //         'lng' => $request->lng,
        //         'markerCategory_id' => $request->markerCategory_id,
        //         'status_id' => 1, // Default active status
        //     ]);

        //     $addressId = null;

        //     // If station category and station data exists
        //     if ($request->has('station_data')) {
        //         $stationData = $request->station_data;
                
        //         // Create full address if address data is provided
        //         if (!empty($stationData['street']) && !empty($stationData['barangay_id'])) {
        //             $address = FullAddress::create([
        //                 'street' => $stationData['street'],
        //                 'barangay_id' => $stationData['barangay_id'],
        //                 'postal_id' => $stationData['postal_id'] ?? null,
        //             ]);
        //             $addressId = $address->address_id;
        //         }
                
        //         // Create station
        //         $station = Station::create([
        //             'station_name' => $stationData['station_name'],
        //             'station_code' => $stationData['station_code'],
        //             'station_chief' => $stationData['station_chief'] ?? null,
        //             'contact_number' => $stationData['contact_number'],
        //             'email' => $stationData['email'],
        //             'address_id' => $addressId,
        //             'marker_id' => $marker->marker_id,
        //         ]);
        //     }

        //     DB::commit();

        //     return response()->json([
        //         'success' => true,
        //         'message' => 'Marker and station saved successfully',
        //         'marker' => $marker
        //     ]);

        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Error: ' . $e->getMessage()
        //     ], 500);
        // }
    }
}
