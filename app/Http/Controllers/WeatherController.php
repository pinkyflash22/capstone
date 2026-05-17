<?php

namespace App\Http\Controllers;

use App\Models\Weather;
use App\Models\Incident;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    public function index()
    {
        $weatherRecords = Weather::with('incident')->paginate(15);
        return view('admin.weather.index', compact('weatherRecords'));
    }

    public function create()
    {
        $incidents = Incident::doesntHave('weather')->get();
        return view('admin.weather.create', compact('incidents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'temperature' => 'required|numeric|min:-50|max:60',
            'humidity' => 'required|numeric|min:0|max:100',
            'wind_speed' => 'required|numeric|min:0|max:200',
            'wind_direction' => 'nullable|string|max:10',
            'incident_id' => 'required|exists:incidents,incident_id|unique:weather,incident_id',
        ]);

        Weather::create($validated);

        return redirect()->route('weather.index')
            ->with('success', 'Weather record created successfully.');
    }

    public function show(Weather $weather)
    {
        $weather->load('incident');
        return view('admin.weather.show', compact('weather'));
    }

    public function edit(Weather $weather)
    {
        $incidents = Incident::all();
        return view('admin.weather.edit', compact('weather', 'incidents'));
    }

    public function update(Request $request, Weather $weather)
    {
        $validated = $request->validate([
            'temperature' => 'required|numeric|min:-50|max:60',
            'humidity' => 'required|numeric|min:0|max:100',
            'wind_speed' => 'required|numeric|min:0|max:200',
            'wind_direction' => 'nullable|string|max:10',
            'incident_id' => 'required|exists:incidents,incident_id|unique:weather,incident_id,' . $weather->weather_id . ',weather_id',
        ]);

        $weather->update($validated);

        return redirect()->route('weather.index')
            ->with('success', 'Weather record updated successfully.');
    }

    public function destroy(Weather $weather)
    {
        if ($weather->spotInvestigationReports()->count() > 0) {
            return back()->with('error', 'Cannot delete weather record linked to investigation reports.');
        }

        $weather->delete();

        return redirect()->route('weather.index')
            ->with('success', 'Weather record deleted successfully.');
    }
}