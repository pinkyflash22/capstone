<?php

namespace App\Http\Controllers;

use App\Services\PredictedRiskAreaService;
use App\Services\OperationalMapScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FireRiskAreaOverlayController extends Controller
{
    public function __construct(
        protected PredictedRiskAreaService $predictedRiskAreaService,
        protected OperationalMapScope $operationalScope,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'levels' => ['nullable', 'string', 'max:80'],
            'station_id' => ['nullable', 'exists:stations,station_id'],
        ]);

        $levels = collect(explode(',', (string) ($validated['levels'] ?? 'low,medium,high')))
            ->map(fn ($level) => strtolower(trim($level)))
            ->filter(fn ($level) => in_array($level, ['low', 'medium', 'high'], true))
            ->values();

        if ($levels->isEmpty()) {
            $levels = collect(['low', 'medium', 'high']);
        }

        $requestedStationId = isset($validated['station_id']) ? (int) $validated['station_id'] : null;
        $stationId = $this->operationalScope->authorizeRequestedStation($request->user(), $requestedStationId);
        // Heat overlays must represent a saved analysis result. Approved risk/hazard
        // reports remain marker icons; they are used only as geographic evidence
        // positions for an analysis and are never emitted as an independent heatmap.
        $overlay = $this->predictedRiskAreaService->buildForecastOverlay($stationId);
        $analysisAreas = $overlay['areas'];
        $areas = $analysisAreas
            ->filter(fn (array $area) => $levels->contains($area['risk_level']))
            ->values()
            ->all();

        return response()->json([
            'data' => $areas,
            'meta' => [
                'levels' => $levels->all(),
                'count' => count($areas),
                'source' => 'saved_current_analysis_and_forecast_results',
                'analysis_count' => $analysisAreas->count(),
                'boundary_forecast_count' => collect($areas)->where('geometry_precision', 'barangay_boundary')->count(),
                'evidence_heatmaps' => 0,
                'unmapped_count' => $overlay['unmapped']->count(),
                'unmapped_forecasts' => $overlay['unmapped']->all(),
            ],
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
