<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EquipmentCategory;
use App\Models\Equipment;
use App\Models\Station;
use App\Models\User;
use App\Models\Role;
use App\Models\StationOfficer;
use App\Models\Team;
use App\Models\Province;



class ResourceManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $equipmentcategory  = EquipmentCategory::all();
        $equipments = Equipment::all();
        $stationOfficers = StationOfficer::with('user','station')->get();

        $activeStations = Station::all();
        $citizenRoleId = Role::where('role', 'citizen')->value('role_id');
        $allTeam = Team::with('commander', 'pumpers', 'teamLeader', 'officer1', 'officer2', 'officer3', 'rescueRadio')->get();
        $users = User::where('role_id', '!=', $citizenRoleId)->get();
        $allusers = User::all();
           $roles = Role::all();
        $provinces = Province::orderBy('province_name')->get();
        
        
        return view('admin.resource-management', compact('equipmentcategory','allusers','provinces','roles','equipments','activeStations','users','stationOfficers','allTeam'));
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
        //
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
