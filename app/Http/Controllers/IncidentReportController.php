<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncidentReport;
class IncidentReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
         $request->validate([
            'incident_id' => 'required|exists:incident,incident_id',
            'victims' => 'nullable',
            'severity' => 'required|in:low,medium,code blue, high, code red',
            'dateEnded' => 'nullable',
        ]);
        // $accountId = Auth::user()->account->account_id;

        $incident = IncidentReport::create([
            'marker_id' => $request->marker_id,
            'station_id' => $request->station_id,
            'status' => $request->status,
            'incident_id' => $request->incident_id,
            'victims' => $request->victims,
            'severity' => $request->severity,
            'dateEnded' => $request->dateEnded,
        ]);

        return response()->json([
            'success' => true,
            'reported' => $incident
        ]);

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
