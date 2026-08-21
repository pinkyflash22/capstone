<?php

namespace App\Http\Controllers;

use App\Services\PredictedRiskAreaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FireRiskAreaOverlayController extends Controller
{
    protected const DEFAULT_PROXIMITY_METERS = 100;

    public function __construct(
        protected PredictedRiskAreaService $predictedRiskAreaService
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

        $stationId = isset($validated['station_id']) ? (int) $validated['station_id'] : null;
        $evidenceAreas = $this->predictedRiskAreaService
            ->buildPredictedAreas($stationId, self::DEFAULT_PROXIMITY_METERS)
            ->map(function (array $area) {
                $area['overlay_type'] = 'verified_evidence';
                $area['geometry_type'] = 'heatmap';
                $area['geometry_precision'] = 'original_evidence_points';
                return $area;
            });
        $forecastAreas = $this->predictedRiskAreaService->buildForecastAreas($stationId);
        $areas = $evidenceAreas
            ->concat($forecastAreas)
            ->filter(fn (array $area) => $levels->contains($area['risk_level']))
            ->values()
            ->all();

        return response()->json([
            'data' => $areas,
            'meta' => [
                'levels' => $levels->all(),
                'count' => count($areas),
                'source' => 'verified_evidence_and_saved_barangay_forecasts',
                'cluster_proximity_meters' => self::DEFAULT_PROXIMITY_METERS,
                'forecast_count' => $forecastAreas->count(),
                'evidence_count' => $evidenceAreas->count(),
            ],
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
