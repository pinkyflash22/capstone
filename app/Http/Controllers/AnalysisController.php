<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    public function index()
    {
        $analyses = Analysis::with('station')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return view('admin.analyses.index', compact('analyses'));
    }

    public function create()
    {
        return view('admin.analyses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sector' => 'nullable|string|max:100',
            'period' => 'nullable|string|max:50',
            'risk_level' => 'nullable|string|in:Low,Medium,High,Critical|max:20',
            'confidence_data' => 'nullable|string|max:255',
            'confidence_score' => 'nullable|integer|min:0|max:100',
            'analysis_data' => 'nullable|json',
            'recommendations' => 'nullable|json',
            'predictions' => 'nullable|json',
            'incident_count' => 'required|integer|min:0',
            'probability' => 'nullable|string|max:255',
            'station_id' => 'nullable|exists:stations,station_id',
        ]);

        if (!isset($validated['confidence_data']) && isset($validated['confidence_score'])) {
            $validated['confidence_data'] = (string) $validated['confidence_score'];
        }

        unset($validated['confidence_score']);

        Analysis::create($validated);

        return redirect()->route('analyses.index')
            ->with('success', 'Analysis created successfully.');
    }

    public function show(Analysis $analysis)
    {
        $analysis->load('station');
        return view('admin.analyses.show', compact('analysis'));
    }

    public function edit(Analysis $analysis)
    {
        return view('admin.analyses.edit', compact('analysis'));
    }

    public function update(Request $request, Analysis $analysis)
    {
        $validated = $request->validate([
            'sector' => 'nullable|string|max:100',
            'period' => 'nullable|string|max:50',
            'risk_level' => 'nullable|string|in:Low,Medium,High,Critical|max:20',
            'confidence_data' => 'nullable|string|max:255',
            'confidence_score' => 'nullable|integer|min:0|max:100',
            'analysis_data' => 'nullable|json',
            'recommendations' => 'nullable|json',
            'predictions' => 'nullable|json',
            'incident_count' => 'required|integer|min:0',
            'probability' => 'nullable|string|max:255',
            'station_id' => 'nullable|exists:stations,station_id',
        ]);

        if (!isset($validated['confidence_data']) && isset($validated['confidence_score'])) {
            $validated['confidence_data'] = (string) $validated['confidence_score'];
        }

        unset($validated['confidence_score']);

        $analysis->update($validated);

        return redirect()->route('analyses.index')
            ->with('success', 'Analysis updated successfully.');
    }

    public function destroy(Analysis $analysis)
    {
        $analysis->delete();

        return redirect()->route('analyses.index')
            ->with('success', 'Analysis deleted successfully.');
    }
}
