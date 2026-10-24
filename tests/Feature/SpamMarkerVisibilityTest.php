<?php

namespace Tests\Feature;

use App\Http\Controllers\MarkerController;
use App\Models\FinalInvestigationReport;
use App\Models\HistoricalFireIncident;
use App\Models\Incident;
use App\Models\IncidentReport;
use App\Models\Marker;
use App\Models\ProgressInvestigationReport;
use App\Models\Scopes\ExcludeSpamIncidents;
use App\Models\SpotInvestigationReport;
use App\Services\OperationalMapScope;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class SpamMarkerVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Every test uses a new in-memory connection, never the application's database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('markers', function (Blueprint $table) {
            $table->id('marker_id');
            $table->string('title')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('spammed_by')->nullable();
            $table->timestamp('spammed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('incidents', function (Blueprint $table) {
            $table->id('incident_id');
            $table->unsignedBigInteger('marker_id')->nullable();
            $table->unsignedBigInteger('station_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('status')->nullable();
        });
        foreach ([
            'spot_investigation_report' => ['spot_investigation_report_id', 'incident_id'],
            'progress_investigation_report' => ['progress_investigation_report_id', 'spot_investigation_id'],
            'final_investigation_report' => ['final_investigation_report_id', 'progress_investigation_id'],
            'incident_report' => ['id', 'incident_id'],
            'historical_fire_incidents' => ['historical_fire_incident_id', 'incident_id'],
        ] as $name => [$key, $parent]) {
            Schema::create($name, function (Blueprint $table) use ($key, $parent) {
                $table->id($key);
                $table->unsignedBigInteger($parent)->nullable();
                if ($key === 'progress_investigation_report_id') {
                    $table->unsignedBigInteger('parent_progress_investigation_id')->nullable();
                }
            });
            for ($id = 1; $id <= 5; $id++) {
                $parentId = $id === 5 ? null : ($parent === 'incident_id' ? $id + 10 : $id);
                DB::table($name)->insert([$key => $id, $parent => $parentId]);
            }
        }
        foreach (['stations' => 'station_id', 'hydrants' => 'hydrant_id', 'risk_areas' => 'risk_area_id'] as $name => $key) {
            Schema::create($name, function (Blueprint $table) use ($key) {
                $table->id($key);
                $table->unsignedBigInteger('marker_id');
                if ($key !== 'station_id') {
                    $table->unsignedBigInteger('station_id')->nullable();
                }
            });
        }

        Schema::create('teams', function (Blueprint $table) {
            $table->id('team_id');
            $table->unsignedBigInteger('station_id')->nullable();
        });

        foreach ([1 => 'active', 2 => 'inactive', 3 => 'inactive', 4 => 'active'] as $id => $status) {
            DB::table('markers')->insert([
                'marker_id' => $id, 'title' => "Marker {$id}", 'status' => $status,
                'spammed_at' => $id >= 3 ? '2026-10-24 08:00:00' : null,
            ]);
            DB::table('incidents')->insert([
                'incident_id' => $id + 10, 'marker_id' => $id,
                'station_id' => 7, 'status' => $id === 2 ? 'Closed' : 'Reported',
            ]);
        }
        DB::table('incidents')->insert(['incident_id' => 15, 'marker_id' => null, 'status' => 'Closed']);
    }

    public function test_only_explicit_spam_is_listed_including_inconsistent_active_markers(): void
    {
        $this->assertSame([3, 4], Marker::spam()->orderBy('marker_id')->pluck('marker_id')->all());
        $this->assertSame([11, 12, 15], Incident::orderBy('incident_id')->pluck('incident_id')->all());
    }

    public function test_report_and_historical_counts_exclude_spam_but_preserve_unlinked_records(): void
    {
        foreach ([IncidentReport::class, SpotInvestigationReport::class, ProgressInvestigationReport::class,
            FinalInvestigationReport::class, HistoricalFireIncident::class] as $model) {
            $key = (new $model)->getKeyName();
            $this->assertSame([1, 2, 5], $model::orderBy($key)->pluck($key)->all(), $model);
            $this->assertSame(3, $model::count(), $model);
        }
    }

    public function test_raw_analytics_queries_and_or_conditions_cannot_reintroduce_spam(): void
    {
        $query = ExcludeSpamIncidents::filter(DB::table('incidents'), 'incidents');
        $this->assertSame([11, 12, 15], $query->orderBy('incident_id')->pluck('incident_id')->all());
        $this->assertSame([11, 12], Incident::where('incident_id', 11)->orWhere('station_id', 7)
            ->orderBy('incident_id')->pluck('incident_id')->all());
    }

    public function test_station_review_can_find_spam_incidents_despite_the_default_filter(): void
    {
        $scope = $this->createMock(OperationalMapScope::class);
        $scope->method('hasProvinceWideAccess')->willReturn(false);
        $scope->method('stationIds')->willReturn(collect([7]));
        $scope->method('coveredBarangayIds')->willReturn(collect());
        $method = new ReflectionMethod(MarkerController::class, 'spamMarkerQuery');
        $query = $method->invoke(new MarkerController, Request::create('/'), $scope);
        $this->assertSame([3, 4], $query->orderBy('marker_id')->pluck('marker_id')->all());
    }

    public function test_progress_report_self_relationship_queries_keep_the_spam_filter(): void
    {
        DB::table('progress_investigation_report')->insert([
            ['progress_investigation_report_id' => 6, 'spot_investigation_id' => 1, 'parent_progress_investigation_id' => 1],
            ['progress_investigation_report_id' => 7, 'spot_investigation_id' => 3, 'parent_progress_investigation_id' => 3],
        ]);

        $this->assertSame([1], ProgressInvestigationReport::has('secondProgressInvestigation')
            ->pluck('progress_investigation_report_id')->all());
    }

    public function test_actual_mark_and_restore_actions_hide_and_return_the_linked_reports(): void
    {
        $controller = new MarkerController;
        $response = $controller->destroy(1);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull(Marker::findOrFail(1)->spammed_at);
        $this->assertNull(Incident::find(11));
        $this->assertNull(FinalInvestigationReport::find(1));

        $scope = $this->createMock(OperationalMapScope::class);
        $scope->method('hasProvinceWideAccess')->willReturn(true);
        $request = Request::create('/map/markers/1/restore', 'PATCH');
        $response = $controller->restoreSpam($request, 1, $scope);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertNull(Marker::findOrFail(1)->spammed_at);
        $this->assertSame('active', Marker::findOrFail(1)->status);
        $this->assertNotNull(Incident::find(11));
        $this->assertNotNull(FinalInvestigationReport::find(1));
    }
}
