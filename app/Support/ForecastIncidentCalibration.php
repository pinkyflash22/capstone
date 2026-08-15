<?php

namespace App\Support;

final class ForecastIncidentCalibration
{
    /**
     * Blend the raw model total with observed pace and same-month history.
     * The returned range is intended for planning, not as a guaranteed count.
     */
    public static function calibrate(
        int $rawModelTotal,
        int $observedCurrentMonth,
        bool $isCurrentMonth,
        int $daysInMonth,
        int $elapsedDays,
        array $historicalSameMonthTotals = []
    ): array {
        $rawModelTotal = max(0, $rawModelTotal);
        $observedCurrentMonth = max(0, $observedCurrentMonth);
        $daysInMonth = max(1, $daysInMonth);
        $elapsedDays = max(1, min($daysInMonth, $elapsedDays));

        $historicalTotals = collect($historicalSameMonthTotals)
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => max(0, (int) round((float) $value)))
            ->values();
        $historicalAverage = $historicalTotals->isNotEmpty()
            ? round((float) $historicalTotals->avg(), 2)
            : 0.0;
        $paceProjection = round(($observedCurrentMonth / $elapsedDays) * $daysInMonth, 2);

        if ($observedCurrentMonth > 0) {
            $weights = $isCurrentMonth
                ? ['model' => 0.25, 'pace' => 0.55, 'history' => 0.20]
                : ['model' => 0.30, 'pace' => 0.45, 'history' => 0.25];
        } elseif ($historicalTotals->isNotEmpty()) {
            $weights = ['model' => 0.40, 'pace' => 0.0, 'history' => 0.60];
        } else {
            $weights = ['model' => 1.0, 'pace' => 0.0, 'history' => 0.0];
        }

        $blended = ($rawModelTotal * $weights['model'])
            + ($paceProjection * $weights['pace'])
            + ($historicalAverage * $weights['history']);
        $minimum = $isCurrentMonth ? $observedCurrentMonth : 0;
        $plausibilityBasis = max($observedCurrentMonth, $paceProjection, $historicalAverage, 1);
        $plausibleCeiling = max($minimum, (int) ceil(($plausibilityBasis * 1.6) + 2));
        $midpoint = max($minimum, min($plausibleCeiling, (int) round($blended)));

        $uncertainty = max(
            1,
            (int) ceil($midpoint * 0.25),
            (int) ceil(abs($rawModelTotal - $midpoint) * 0.15)
        );
        $lower = max($minimum, $midpoint - $uncertainty);
        $upper = max($midpoint, min($plausibleCeiling, $midpoint + $uncertainty));

        return [
            'raw_model_total' => $rawModelTotal,
            'calibrated_midpoint' => $midpoint,
            'range' => ['lower' => $lower, 'upper' => $upper],
            'observed_current_month' => $observedCurrentMonth,
            'pace_projection' => $paceProjection,
            'historical_same_month_average' => $historicalAverage,
            'historical_same_month_totals' => $historicalTotals->all(),
            'days_in_month' => $daysInMonth,
            'elapsed_days' => $elapsedDays,
            'weights' => $weights,
            'method' => 'model_pace_history_blend_v1',
        ];
    }
}
