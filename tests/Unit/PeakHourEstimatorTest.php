<?php

namespace Tests\Unit;

use App\Support\PeakHourEstimator;
use PHPUnit\Framework\TestCase;

class PeakHourEstimatorTest extends TestCase
{
    public function test_it_reports_no_clear_peak_for_sparse_data(): void
    {
        $result = PeakHourEstimator::estimate([
            ['hour' => 3, 'weight' => 3],
            ['hour' => 14, 'weight' => 3],
            ['hour' => 18, 'weight' => 3],
        ]);

        $this->assertSame('unclear', $result['status']);
        $this->assertSame('No clear peak yet', $result['label']);
        $this->assertSame([], $result['hours']);
        $this->assertSame('insufficient_data', $result['reason']);
    }

    public function test_it_does_not_claim_a_peak_when_top_windows_are_tied(): void
    {
        $result = PeakHourEstimator::estimate([
            ['hour' => 3, 'weight' => 2],
            ['hour' => 4, 'weight' => 2],
            ['hour' => 13, 'weight' => 2],
            ['hour' => 14, 'weight' => 2],
            ['hour' => 20, 'weight' => 1],
        ]);

        $this->assertSame('unclear', $result['status']);
        $this->assertSame('tied_or_weak_signal', $result['reason']);
    }

    public function test_verified_and_historical_afternoon_evidence_produces_a_broad_window(): void
    {
        $result = PeakHourEstimator::estimate([
            ['hour' => 13, 'weight' => 3],
            ['hour' => 14, 'weight' => 3],
            ['hour' => 13, 'weight' => 1.5],
            ['hour' => 15, 'weight' => 1.5],
            ['hour' => 3, 'weight' => 0.75],
            ['hour' => 4, 'weight' => 0.75],
        ]);

        $this->assertSame('clear', $result['status']);
        $this->assertSame(['12:00–14:59'], $result['hours']);
        $this->assertGreaterThan(0, $result['confidence']);
    }
}
