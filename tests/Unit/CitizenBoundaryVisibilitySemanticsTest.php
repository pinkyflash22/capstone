<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CitizenBoundaryVisibilitySemanticsTest extends TestCase
{
    public function test_citizen_boundary_feed_includes_same_city_barangay_boundaries(): void
    {
        $controller = file_get_contents(__DIR__.'/../../app/Http/Controllers/BoundaryLocationController.php');

        $this->assertStringContainsString("->whereHas('barangay'", $controller);
        $this->assertStringContainsString("->whereIn('city_id', \$cityIds->all())", $controller);
        $this->assertStringContainsString('Citizens and barangay users are already restricted', $controller);
    }
}
