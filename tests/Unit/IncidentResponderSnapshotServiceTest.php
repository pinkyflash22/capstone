<?php

namespace Tests\Unit;

use App\Services\IncidentResponderSnapshotService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IncidentResponderSnapshotServiceTest extends TestCase
{
    #[Test]
    public function it_merges_responder_snapshots_without_erasing_or_duplicating_people(): void
    {
        $service = new IncidentResponderSnapshotService;

        $result = $service->mergeResponders(
            [
                ['station_officer_id' => 10, 'station_id' => 1],
            ],
            [
                ['station_officer_id' => '10', 'station_id' => '1'],
                ['station_officer_id' => 11, 'station_id' => 1],
            ]
        );

        $this->assertSame([
            ['station_officer_id' => 10, 'station_id' => 1],
            ['station_officer_id' => 11, 'station_id' => 1],
        ], $result);
    }

    #[Test]
    public function it_discards_malformed_responder_entries(): void
    {
        $service = new IncidentResponderSnapshotService;

        $result = $service->mergeResponders(
            [['station_officer_id' => 10]],
            [null, 'invalid', ['station_officer_id' => 12, 'station_id' => 2]]
        );

        $this->assertSame([
            ['station_officer_id' => 12, 'station_id' => 2],
        ], $result);
    }
}
