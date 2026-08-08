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

        $levels = collect(explode(',', (string) ($validated['levels'] ?? 'medium,high')))
            ->map(fn ($level) => strtolower(trim($level)))
            ->filter(fn ($level) => in_array($level, ['medium', 'high'], true))
            ->values();

        if ($levels->isEmpty()) {
            $levels = collect(['medium', 'high']);
        }

        $areas = $this->predictedRiskAreaService
            ->buildPredictedAreas($validated['station_id'] ?? null, self::DEFAULT_PROXIMITY_METERS)
            ->filter(fn (array $area) => $levels->contains($area['risk_level']))
            ->values()
            ->all();

        return response()->json([
            'data' => $areas,
            'meta' => [
                'levels' => $levels->all(),
                'count' => count($areas),
                'source' => 'predicted_clustered_evidence',
                'cluster_proximity_meters' => self::DEFAULT_PROXIMITY_METERS,
            ],
        ]);
    }
}
