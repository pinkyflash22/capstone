<?php

namespace Tests\Unit;

use App\Services\BoundaryMatcherService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RiskAreaBoundaryMatchingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('barangay_halls');
        Schema::dropIfExists('boundary_location');
        Schema::dropIfExists('barangays');

        Schema::create('barangays', function (Blueprint $table) { $table->id('barangay_id'); $table->string('barangay_name'); $table->timestamps(); });
        Schema::create('barangay_halls', function (Blueprint $table) { $table->id('barangay_hall_id'); $table->unsignedBigInteger('barangay_id'); $table->string('hall_name'); $table->timestamps(); });
        Schema::create('boundary_location', function (Blueprint $table) { $table->id(); $table->string('name'); $table->json('geojson'); $table->unsignedBigInteger('station_id')->nullable(); $table->unsignedBigInteger('barangay_id')->nullable(); $table->string('boundary_type'); $table->string('source')->nullable(); $table->json('properties')->nullable(); $table->timestamps(); });

        DB::table('barangays')->insert(['barangay_id' => 7, 'barangay_name' => 'Matched Barangay']);
        DB::table('barangay_halls')->insert(['barangay_hall_id' => 9, 'barangay_id' => 7, 'hall_name' => 'Matched Hall']);
        DB::table('boundary_location')->insert([
            'name' => 'Matched Boundary',
            'barangay_id' => 7,
            'boundary_type' => 'barangay',
            'geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [[[120, 14], [121, 14], [121, 15], [120, 15], [120, 14]]]]),
        ]);
    }

    public function test_marker_coordinates_resolve_the_linked_barangay_hall(): void
    {
        $hall = app(BoundaryMatcherService::class)->locateBarangayHall(14.5, 120.5);

        $this->assertSame(9, $hall?->barangay_hall_id);
        $this->assertNull(app(BoundaryMatcherService::class)->locateBarangayHall(10, 110));
    }

    public function test_risk_marker_location_updates_reassign_related_records_by_boundary(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/MarkerController.php'));

        $this->assertStringContainsString('locateBarangayHall($lat, $lng)', $controller);
        $this->assertStringContainsString("'barangay_hall_id' => \$matchedHall->barangay_hall_id", $controller);
    }
}
