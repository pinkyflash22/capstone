<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Marker;
use App\Models\StationOfficer;

use App\Models\Incident;
use App\Models\Role;
use App\Models\User;
use App\Models\Station;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use App\Models\FullAddress;
use App\Models\Post;
use App\Models\EventActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    private function getDashboardMarkerCategories(): Collection
    {
        return collect([
            (object) ['markerCategory_id' => 1, 'marker_category' => 'Fire Station'],
            (object) ['markerCategory_id' => 2, 'marker_category' => 'Hydrant'],
            (object) ['markerCategory_id' => 3, 'marker_category' => 'Fire'],
            (object) ['markerCategory_id' => 4, 'marker_category' => 'School'],
            (object) ['markerCategory_id' => 5, 'marker_category' => 'Barangay'],
            (object) ['markerCategory_id' => 6, 'marker_category' => 'Bridge'],
        ]);
    }

    private function getIncidentStatusOptions(): Collection
    {
        return collect([
            (object) ['status' => 'Reported'],
            (object) ['status' => 'Responding'],
            (object) ['status' => 'On Scene'],
            (object) ['status' => 'Controlled'],
            (object) ['status' => 'Closed'],
        ]);
    }

    public function index()
    {
        $categories = $this->getDashboardMarkerCategories();
        $mapmarker = Marker::with('stations')->get();
        $incidents = Incident::with(['marker'])->get();
        $alerts = Incident::with('marker')->get();
        $posts = Post::latest()->take(5)->get();
        $stations = Station::all();
        $provinces = Province::orderBy('province_name')->get();
        $stationperson = StationOfficer::with('station','station.users')->get();
        // Get all users with role 'chief'
        $chiefRole = Role::where('role', 'chief')->first();
        $chiefs = $chiefRole ? User::where('role_id', $chiefRole->role_id)->get() : collect();
        $hydrantTypes = collect();
        $recentAnalyses = DB::table('analysis')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($analysis) {
                $analysis->confidence_score = is_numeric($analysis->confidence_data ?? null)
                    ? (int) $analysis->confidence_data
                    : ($analysis->confidence_data ?? null);
                $analysis->predictions = json_decode($analysis->predictions ?? 'null', true);
                $analysis->recommendations = json_decode($analysis->recommendations ?? '[]', true);

                return $analysis;
            });

        return view('admin.dashboard', compact('categories', 'mapmarker', 'incidents', 'alerts', 'posts', 'stations', 'chiefs', 'provinces','stationperson','hydrantTypes', 'recentAnalyses'));
    }

    //for reports

    public function reports()
    {
        $categories = $this->getDashboardMarkerCategories();
        $mapmarker = Marker::with('stations')->get();
        $incidents = Incident::with(['marker', 'team'])->get();
        $alerts = Incident::with('marker')->get();
        $stations = Station::with(['marker', 'users'])->get();
        $provinces = Province::orderBy('province_name')->get();
        $stationperson = StationOfficer::with('station','station.users')->get();
        // Get all users with role 'chief'
        $chiefRole = Role::where('role', 'chief')->first();
        $incidentStatuses = $this->getIncidentStatusOptions();

        $chiefs = $chiefRole ? User::where('role_id', $chiefRole->role_id)->get() : collect();
        return view('admin.reports', compact('categories','incidentStatuses', 'mapmarker', 'incidents', 'alerts', 'stations', 'chiefs', 'provinces','stationperson'));

    }

    public function schedule()
    {
        $stations = Station::query()
            ->orderBy('station_name')
            ->get(['station_id', 'station_name']);

        $scheduleEvents = EventActivity::query()
            ->with('station')
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->get()
            ->map(function (EventActivity $event) {
                return [
                    'event_id' => $event->event_id,
                    'event' => $event->event,
                    'event_date' => optional($event->event_date)->toDateString(),
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'schedule' => $event->schedule,
                    'activity_type' => $event->activity_type ?: 'operations',
                    'status' => $event->status,
                    'barangay' => $event->barangay,
                    'notes' => $event->notes,
                    'station_id' => $event->station_id,
                    'station_name' => $event->station?->station_name ?: 'Unassigned Station',
                ];
            })
            ->values();

        return view('admin.schedule', [
            'scheduleEvents' => $scheduleEvents,
            'stations' => $stations,
        ]);
    }

    public function findex(){
         $categories = $this->getDashboardMarkerCategories();
        $mapmarker = Marker::with('stations')->get();
        $incidents = Incident::with(['marker'])->get();
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
        $mapmarker = Marker::all();
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
