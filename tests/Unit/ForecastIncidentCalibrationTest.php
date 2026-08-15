<?php

namespace Tests\Unit;

use App\Support\ForecastIncidentCalibration;
use PHPUnit\Framework\TestCase;

class ForecastIncidentCalibrationTest extends TestCase
{
    public function test_it_reduces_an_aggressive_raw_sum_using_pace_and_history(): void
    {
        $result = ForecastIncidentCalibration::calibrate(
            rawModelTotal: 26,
            observedCurrentMonth: 5,
            isCurrentMonth: true,
            daysInMonth: 31,
            elapsedDays: 15,
            historicalSameMonthTotals: [0, 0, 1],
        );

        $this->assertSame(12, $result['calibrated_midpoint']);
        $this->assertSame(['lower' => 9, 'upper' => 15], $result['range']);
        $this->assertSame(26, $result['raw_model_total']);
        $this->assertSame(10.33, $result['pace_projection']);
        $this->assertSame(0.33, $result['historical_same_month_average']);
    }

    public function test_current_month_range_never_falls_below_observed_incidents(): void
    {
        $result = ForecastIncidentCalibration::calibrate(
            rawModelTotal: 1,
            observedCurrentMonth: 5,
            isCurrentMonth: true,
            daysInMonth: 31,
            elapsedDays: 31,
            historicalSameMonthTotals: [0, 0, 0],
        );

        $this->assertGreaterThanOrEqual(5, $result['calibrated_midpoint']);
        $this->assertGreaterThanOrEqual(5, $result['range']['lower']);
        $this->assertGreaterThanOrEqual($result['range']['lower'], $result['range']['upper']);
    }

    public function test_next_month_uses_current_pace_without_counting_it_as_observed(): void
    {
        $result = ForecastIncidentCalibration::calibrate(
            rawModelTotal: 20,
            observedCurrentMonth: 4,
            isCurrentMonth: false,
            daysInMonth: 30,
            elapsedDays: 15,
            historicalSameMonthTotals: [1, 2, 0],
        );

        $this->assertSame(0, min($result['range']['lower'], 0));
        $this->assertGreaterThan(0, $result['calibrated_midpoint']);
        $this->assertLessThan(20, $result['calibrated_midpoint']);
    }
}
