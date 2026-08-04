<?php

namespace App\Http\Controllers;

use App\Models\BoundaryLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FireRiskAreaOverlayController extends Controller
{
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

        $signals = $this->riskSignalsByBarangay($validated['station_id'] ?? null);

        $areas = $signals->isEmpty()
            ? []
            : BoundaryLocation::query()
                ->with(['barangay:barangay_id,barangay_name,city_id', 'barangay.city:city_id,city_name'])
                ->where('boundary_type', 'barangay')
                ->whereNotNull('barangay_id')
                ->whereIn('barangay_id', $signals->keys()->all())
                ->orderBy('name')
                ->get()
                ->map(function (BoundaryLocation $boundary) use ($signals) {
                    $signal = $signals->get((int) $boundary->barangay_id);
                    $risk = $this->classifyRiskSignal($signal);

                    if (! $risk) {
                        return null;
                    }

                    return [
                        'id' => $boundary->id,
                        'barangay_id' => (int) $boundary->barangay_id,
                        'name' => $boundary->barangay?->barangay_name ?: $boundary->name,
                        'city_name' => $boundary->barangay?->city?->city_name,
                        'risk_level' => $risk['level'],
                        'risk_score' => $risk['score'],
                        'risk_label' => ucfirst($risk['level']) . ' fire risk',
                        'drivers' => $risk['drivers'],
                        'metrics' => [
                            'verified_records' => (int) $signal->verified_risk_area_count,
                            'hazards' => (int) $signal->verified_hazard_count,
                            'risks' => (int) $signal->verified_risk_count,
                            'high_assessments' => (int) $signal->high_assessment_count,
                            'moderate_assessments' => (int) $signal->moderate_assessment_count,
                            'max_risk_score' => $signal->max_risk_score !== null ? round((float) $signal->max_risk_score, 2) : null,
                            'avg_risk_score' => $signal->avg_risk_score !== null ? round((float) $signal->avg_risk_score, 2) : null,
                            'affected_households' => (int) $signal->affected_households,
                        ],
                        'geojson' => $boundary->geojson,
                    ];
                })
                ->filter(fn ($area) => $area && $levels->contains($area['risk_level']))
                ->values()
                ->all();

        return response()->json([
            'data' => $areas,
            'meta' => [
                'levels' => $levels->all(),
                'count' => count($areas),
                'source' => 'verified_risk_areas',
            ],
        ]);
    }

    protected function riskSignalsByBarangay(?int $stationId = null): Collection
    {
        return DB::table('risk_areas')
            ->join('barangay_halls', 'risk_areas.barangay_hall_id', '=', 'barangay_halls.barangay_hall_id')
            ->where(function ($query) {
                $query->where('risk_areas.review_status', 'approved')
                    ->orWhere('risk_areas.status', 'verified');
            })
            ->where(function ($query) {
                $query->whereNull('risk_areas.review_type')
                    ->orWhere('risk_areas.review_type', '!=', 'delete');
            })
            ->when($stationId, fn ($query) => $query->where('risk_areas.station_id', $stationId))
            ->selectRaw("
                barangay_halls.barangay_id,
                COUNT(*) as verified_risk_area_count,
                SUM(CASE WHEN risk_areas.entry_type = 'hazard' THEN 1 ELSE 0 END) as verified_hazard_count,
                SUM(CASE WHEN risk_areas.entry_type = 'risk' OR risk_areas.entry_type IS NULL THEN 1 ELSE 0 END) as verified_risk_count,
                SUM(CASE WHEN risk_areas.assessment_level = 'high' THEN 1 ELSE 0 END) as high_assessment_count,
                SUM(CASE WHEN risk_areas.assessment_level IN ('moderate', 'medium') THEN 1 ELSE 0 END) as moderate_assessment_count,
                MAX(risk_areas.risk_score) as max_risk_score,
                AVG(risk_areas.risk_score) as avg_risk_score,
                SUM(CASE WHEN risk_areas.blocked_hydrant IS TRUE THEN 1 ELSE 0 END) as blocked_hydrant_count,
                SUM(CASE WHEN risk_areas.combustible_materials_present IS TRUE THEN 1 ELSE 0 END) as combustible_material_count,
                SUM(CASE WHEN risk_areas.informal_settlement IS TRUE THEN 1 ELSE 0 END) as informal_settlement_count,
                SUM(CASE WHEN risk_areas.cooking_fire_prone_activity IS TRUE THEN 1 ELSE 0 END) as cooking_fire_activity_count,
                SUM(CASE WHEN risk_areas.poor_waste_disposal IS TRUE THEN 1 ELSE 0 END) as poor_waste_disposal_count,
                SUM(CASE WHEN risk_areas.previous_fire_prone_location IS TRUE THEN 1 ELSE 0 END) as previous_fire_prone_count,
                SUM(CASE WHEN risk_areas.road_accessibility IN ('difficult', 'very_difficult') THEN 1 ELSE 0 END) as poor_access_count,
                COALESCE(SUM(risk_areas.estimated_affected_households), 0) as affected_households
            ")
            ->groupBy('barangay_halls.barangay_id')
            ->get()
            ->keyBy(fn ($row) => (int) $row->barangay_id);
    }

    protected function classifyRiskSignal(object $signal): ?array
    {
        $maxScore = $signal->max_risk_score !== null ? (float) $signal->max_risk_score : 0.0;
        $avgScore = $signal->avg_risk_score !== null ? (float) $signal->avg_risk_score : 0.0;
        $pressure = (int) $signal->blocked_hydrant_count
            + (int) $signal->combustible_material_count
            + (int) $signal->informal_settlement_count
            + (int) $signal->cooking_fire_activity_count
            + (int) $signal->poor_waste_disposal_count
            + (int) $signal->previous_fire_prone_count
            + (int) $signal->poor_access_count;

        $score = max($maxScore, $avgScore);
        $level = null;

        if ($maxScore >= 70 || (int) $signal->high_assessment_count > 0 || $pressure >= 4) {
            $level = 'high';
            $score = max($score, 70);
        } elseif ($maxScore >= 50 || $avgScore >= 50 || (int) $signal->moderate_assessment_count > 0 || (int) $signal->verified_risk_area_count >= 2 || $pressure >= 2) {
            $level = 'medium';
            $score = max($score, 50);
        }

        if (! $level) {
            return null;
        }

        return [
            'level' => $level,
            'score' => round(min(100, $score), 2),
            'drivers' => $this->riskDrivers($signal, $pressure),
        ];
    }

    protected function riskDrivers(object $signal, int $pressure): array
    {
        return collect([
            ((int) $signal->high_assessment_count > 0) ? "{$signal->high_assessment_count} high field assessment(s)" : null,
            ((int) $signal->moderate_assessment_count > 0) ? "{$signal->moderate_assessment_count} moderate field assessment(s)" : null,
            ((int) $signal->verified_hazard_count > 0) ? "{$signal->verified_hazard_count} verified hazard(s)" : null,
            ((int) $signal->verified_risk_count > 0) ? "{$signal->verified_risk_count} verified risk record(s)" : null,
            $pressure > 0 ? "{$pressure} fire-risk condition signal(s)" : null,
            ((int) $signal->affected_households > 0) ? number_format((int) $signal->affected_households) . ' affected household(s)' : null,
        ])->filter()->values()->all();
    }
}
