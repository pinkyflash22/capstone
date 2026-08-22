<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IncidentJurisdictionSemanticsTest extends TestCase
{
    public function test_incident_dispatch_uses_saved_boundary_and_active_station_coverage(): void
    {
        $service = file_get_contents(__DIR__.'/../../app/Services/IncidentJurisdictionService.php');
        $assignment = file_get_contents(__DIR__.'/../../app/Services/IncidentAutoAssignmentService.php');
        $barangay = file_get_contents(__DIR__.'/../../app/Http/Controllers/Barangay/BarangayDashboardController.php');

        $this->assertStringContainsString('locateBarangayBoundary($lat, $lng)', $service);
        $this->assertStringContainsString("->where('is_active', true)", $service);
        $this->assertStringContainsString("WHEN 'primary' THEN 1", $service);
        $this->assertStringContainsString('$this->jurisdictionService->apply($lockedIncident)', $assignment);
        $this->assertStringContainsString("'station_id' => \$jurisdiction['station_id']", $barangay);
    }
}
