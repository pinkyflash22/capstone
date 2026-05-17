<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Station;
use App\Models\Hydrant;
use App\Models\Marker;
use App\Models\User;
use App\Models\BoundaryLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistics
        $totalIncidents = Incident::count();
        $totalStations = Station::count();
        $totalHydrants = Hydrant::count();
        $totalMarkers = Marker::count();
        $totalFirefighters = User::whereHas('role', function($query) {
            $query->where('role', 'like', '%firefighter%');
        })->count();

        // Incidents by type
        $incidentsByType = Incident::with('incidentType')
            ->select('incident_type_id', DB::raw('count(*) as total'))
            ->groupBy('incident_type_id')
            ->get();

        // Recent incidents
        $recentIncidents = Incident::with('marker', 'incidentType', 'incidentStatus')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Incidents by month (last 12 months)
        $incidentsByMonth = Incident::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('count(*) as total')
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return view('dashboard', compact(
            'totalIncidents',
            'totalStations',
            'totalHydrants',
            'totalMarkers',
            'totalFirefighters',
            'incidentsByType',
            'recentIncidents',
            'incidentsByMonth'
        ));
    }

    public function map()
    {
        $markers = Marker::with('category', 'status')->get();
        $stations = Station::with('marker')->get();
        $hydrants = Hydrant::with('marker')->get();
        $boundaries = BoundaryLocation::with('station')->get();
        
        return view('map', compact('markers', 'stations', 'hydrants', 'boundaries'));
    }
}
