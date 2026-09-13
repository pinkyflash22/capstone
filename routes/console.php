<?php

use App\Jobs\SyncBarangayBoundaryJob;
use App\Models\Incident;
use App\Services\DailyWeatherContextService;
use App\Services\ForecastArtifactStore;
use App\Services\IncidentAutoAssignmentService;
use App\Services\PredictionFeedbackSignalService;
use App\Services\StationBarangayCoverageService;
use App\Support\HistoricalFireRecordSemantics;
use App\Support\PythonRuntime;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('ai:restore-forecast-artifacts', function (ForecastArtifactStore $artifactStore) {
    $report = $artifactStore->restore();
    $message = (string) ($report['message'] ?? 'Forecast artifact restoration finished.');

    if ($report['success'] ?? false) {
        $this->info($message);

        return 0;
    }

    $this->warn($message);
    if (! empty($report['failure_summary'])) {
        $this->line((string) $report['failure_summary']);
    }

    return 1;
})->purpose('Restore the latest trained forecast model from durable object storage');

Artisan::command('coverage:sync-stations {--station=}', function (StationBarangayCoverageService $service) {
    $stationId = $this->option('station');
    $summary = $service->syncAll($stationId !== null && $stationId !== '' ? (int) $stationId : null);

    $this->info("Synchronized {$summary['barangays']} barangay coverage row(s) for {$summary['stations']} station(s).");
    foreach ($summary['unresolved'] as $item) {
        $this->warn("Station {$item['station_id']} ({$item['station_name']}): {$item['reason']}");
    }

    return empty($summary['unresolved']) ? 0 : 1;
})->purpose('Populate and refresh explicit station-to-barangay coverage from station locality.');

Artisan::command('boundaries:sync-covered {--station=} {--limit=} {--force}', function () {
    $stationId = $this->option('station');
    $limit = max(1, (int) ($this->option('limit') ?: config('services.geoapify.boundary_sync_limit', 20)));
    $force = (bool) $this->option('force');
    $geometry = (string) config('services.geoapify.boundary_geometry', 'geometry_1000');

    $candidates = DB::table('station_barangay_coverage')
        ->join('barangay_halls', 'station_barangay_coverage.barangay_id', '=', 'barangay_halls.barangay_id')
        ->join('markers', 'barangay_halls.marker_id', '=', 'markers.marker_id')
        ->leftJoin('boundary_location', function ($join) {
            $join->on('station_barangay_coverage.barangay_id', '=', 'boundary_location.barangay_id')
                ->where('boundary_location.boundary_type', '=', 'barangay');
        })
        ->where('station_barangay_coverage.is_active', true)
        ->whereNotNull('markers.lat')
        ->whereNotNull('markers.lng')
        ->when($stationId !== null && $stationId !== '', fn ($query) => $query->where('station_barangay_coverage.station_id', (int) $stationId))
        ->when(! $force, fn ($query) => $query->whereNull('boundary_location.id'))
        ->when(! $force, function ($query) {
            $query->where(function ($retryQuery) {
                $retryQuery->whereNull('station_barangay_coverage.boundary_sync_attempted_at')
                    ->orWhere('station_barangay_coverage.boundary_sync_attempted_at', '<=', now()->subDays(7));
            });
        })
        ->orderBy('station_barangay_coverage.station_id')
        ->orderBy('station_barangay_coverage.barangay_id')
        ->limit($limit)
        ->get([
            'station_barangay_coverage.station_id',
            'station_barangay_coverage.barangay_id',
        ])
        ->unique('barangay_id')
        ->values();

    if ($candidates->isEmpty()) {
        $this->info('No covered barangay boundaries need synchronization.');

        return 0;
    }

    $queued = 0;
    $failed = 0;
    foreach ($candidates as $candidate) {
        try {
            $job = new SyncBarangayBoundaryJob(
                (int) $candidate->barangay_id,
                (int) $candidate->station_id,
                $geometry,
                $force,
            );

            if (config('queue.default') === 'sync') {
                dispatch_sync($job);
            } else {
                dispatch($job);
            }
            $queued++;
        } catch (\Throwable $exception) {
            $failed++;
            report($exception);
            $this->warn("Barangay {$candidate->barangay_id} failed: {$exception->getMessage()}");
        }
    }

    $verb = config('queue.default') === 'sync' ? 'processed' : 'queued';
    $this->info(ucfirst($verb)." {$queued} barangay boundary job(s); {$failed} failed.");

    return $failed === 0 ? 0 : 1;
})->purpose('Queue Geoapify polygon synchronization for covered barangays that lack boundaries.');

Schedule::command('coverage:sync-stations')
    ->dailyAt('01:30')
    ->withoutOverlapping();

Schedule::command('boundaries:sync-covered')
    ->dailyAt('02:00')
    ->withoutOverlapping();

Artisan::command('weather:collect-daily {--date=} {--station=} {--catch-up=0}', function (DailyWeatherContextService $service) {
    $date = $this->option('date')
        ? Carbon::parse((string) $this->option('date'), 'Asia/Manila')
        : Carbon::now('Asia/Manila')->subDay();
    $station = $this->option('station');
    $stationId = $station !== null && $station !== '' ? (int) $station : null;

    try {
        $catchUpDays = max(0, min(31, (int) $this->option('catch-up')));
        $summary = $catchUpDays > 0
            ? $service->collectMissingRecentDays($catchUpDays, $stationId)
            : $service->collectDate($date, $stationId);
    } catch (\Throwable $exception) {
        $this->error($exception->getMessage());

        return 1;
    }

    $this->info(sprintf(
        'Daily weather %s: checked %d barangay(s), saved %d, failed %d.',
        $catchUpDays > 0 ? "last {$catchUpDays} completed day(s)" : $date->toDateString(),
        $summary['checked'],
        $summary['saved'],
        $summary['failed']
    ));

    foreach ($summary['failures'] as $failure) {
        $this->warn("Barangay {$failure['barangay_id']}: {$failure['message']}");
    }

    return $summary['failed'] === 0 ? 0 : 1;
})->purpose('Save the previous completed day of weather for each actively covered barangay.');

Schedule::command('weather:collect-daily')
    ->dailyAt('00:30')
    ->timezone('Asia/Manila')
    ->withoutOverlapping();

Artisan::command('incidents:dispatch-queue {--timeout=2}', function (IncidentAutoAssignmentService $assignments) {
    $timeout = max(1, (int) $this->option('timeout'));
    $expired = $assignments->expireUnansweredAssignments($timeout);
    $queue = $assignments->assignRecentUnassignedIncidents(24 * 60);

    $this->info(sprintf(
        'Expired %d assignment(s), reassigned %d, renewed %d, queued %d; assigned %d waiting incident(s).',
        $expired['expired'],
        count($expired['reassigned']),
        count($expired['renewed']),
        count($expired['queued']),
        count($queue['assigned'])
    ));

    return 0;
})->purpose('Expire unanswered incident assignments and rematch the dispatch queue.');

Schedule::command('incidents:dispatch-queue --timeout=2')->everyMinute()->withoutOverlapping();
Schedule::command('ops:monitor')->everyFiveMinutes()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ml:export-station-barangay-dataset {--path=capstone-ai/station_barangay_monthly_db.json}', function () {
    $outputPath = base_path($this->option('path'));
    $outputDirectory = dirname($outputPath);

    if (! is_dir($outputDirectory)) {
        File::makeDirectory($outputDirectory, 0755, true);
    }

    $historicalRows = DB::table('historical_data')
        ->orderBy('station_id')
        ->orderBy('year')
        ->get();

    if ($historicalRows->isEmpty()) {
        $this->error('No historical_data rows found. Unable to export a station-barangay monthly training dataset.');

        return 1;
    }

    $stationCityIds = DB::table('stations')
        ->leftJoin('full_addresses', 'stations.address_id', '=', 'full_addresses.address_id')
        ->leftJoin('barangays', 'full_addresses.barangay_id', '=', 'barangays.barangay_id')
        ->pluck('barangays.city_id', 'stations.station_id');

    $riskAreaSignalsByBarangay = DB::table('risk_areas')
        ->join('barangay_halls', 'risk_areas.barangay_hall_id', '=', 'barangay_halls.barangay_hall_id')
        ->where(function ($query) {
            $query->where('risk_areas.review_status', 'approved')
                ->orWhere('risk_areas.status', 'verified');
        })
        ->where(function ($query) {
            $query->whereNull('risk_areas.review_type')
                ->orWhere('risk_areas.review_type', '!=', 'delete');
        })
        ->selectRaw("
            barangay_halls.barangay_id,
            MAX(CASE WHEN risk_areas.near_water_source IS TRUE OR risk_areas.near_hydrant IS TRUE THEN 1 ELSE 0 END) as near_water,
            COUNT(*) as verified_risk_area_count,
            SUM(CASE WHEN risk_areas.entry_type = 'hazard' THEN 1 ELSE 0 END) as verified_hazard_count,
            SUM(CASE WHEN risk_areas.entry_type = 'risk' OR risk_areas.entry_type IS NULL THEN 1 ELSE 0 END) as verified_risk_count,
            SUM(CASE WHEN risk_areas.assessment_level = 'high' THEN 1 ELSE 0 END) as high_assessment_count,
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
        ->keyBy('barangay_id');

    $riskAreaProfilesByBarangay = DB::table('risk_areas')
        ->join('barangay_halls', 'risk_areas.barangay_hall_id', '=', 'barangay_halls.barangay_hall_id')
        ->where(function ($query) {
            $query->where('risk_areas.review_status', 'approved')
                ->orWhere('risk_areas.status', 'verified');
        })
        ->where(function ($query) {
            $query->whereNull('risk_areas.review_type')
                ->orWhere('risk_areas.review_type', '!=', 'delete');
        })
        ->select(
            'barangay_halls.barangay_id',
            'risk_areas.hazard_category',
            'risk_areas.risk_type',
            'risk_areas.condition_label',
            'risk_areas.population_density_level',
            'risk_areas.building_density_level',
            'risk_areas.road_accessibility',
            'risk_areas.near_hydrant',
            'risk_areas.near_water_source',
            'risk_areas.created_at'
        )
        ->orderByRaw("
            CASE risk_areas.assessment_level
                WHEN 'high' THEN 1
                WHEN 'moderate' THEN 2
                WHEN 'medium' THEN 2
                ELSE 3
            END
        ")
        ->orderByDesc('risk_areas.created_at')
        ->get()
        ->groupBy('barangay_id')
        ->map(fn ($rows) => $rows->first());

    $barangaysByCity = DB::table('barangays')
        ->select('barangay_id', 'barangay_name', 'city_id')
        ->orderBy('barangay_name')
        ->get()
        ->groupBy('city_id');

    $barangayCoverageByStation = [];

    foreach ($stationCityIds as $stationId => $cityId) {
        $explicitCoverage = DB::table('station_barangay_coverage')
            ->join('barangays', 'station_barangay_coverage.barangay_id', '=', 'barangays.barangay_id')
            ->where('station_barangay_coverage.station_id', $stationId)
            ->where('station_barangay_coverage.is_active', true)
            ->select('barangays.barangay_id', 'barangays.barangay_name', 'barangays.city_id')
            ->get()
            ->unique('barangay_id')
            ->values();

        if ($explicitCoverage->isNotEmpty()) {
            $barangayCoverageByStation[(int) $stationId] = $explicitCoverage;

            continue;
        }

        $stationBarangays = collect();

        if ($cityId && isset($barangaysByCity[$cityId])) {
            $stationBarangays = $stationBarangays->merge($barangaysByCity[$cityId]);
        }

        $riskAreaBarangays = DB::table('risk_areas')
            ->join('barangay_halls', 'risk_areas.barangay_hall_id', '=', 'barangay_halls.barangay_hall_id')
            ->join('barangays', 'barangay_halls.barangay_id', '=', 'barangays.barangay_id')
            ->where('risk_areas.station_id', $stationId)
            ->select('barangays.barangay_id', 'barangays.barangay_name', 'barangays.city_id')
            ->get();

        $stationBarangays = $stationBarangays->merge($riskAreaBarangays)->unique('barangay_id')->values();

        if ($stationBarangays->count() < 3) {
            $historicalBarangays = DB::table('historical_fire_incidents')
                ->join('barangays', 'historical_fire_incidents.barangay_id', '=', 'barangays.barangay_id')
                ->select('barangays.barangay_id', 'barangays.barangay_name', 'barangays.city_id')
                ->distinct()
                ->get();

            $stationBarangays = $stationBarangays->merge($historicalBarangays)->unique('barangay_id')->values();
        }

        if ($stationBarangays->count() < 3) {
            $urbiztondoBarangays = DB::table('barangays')
                ->join('cities', 'barangays.city_id', '=', 'cities.city_id')
                ->where('cities.city_name', 'Urbiztondo')
                ->select('barangays.barangay_id', 'barangays.barangay_name', 'barangays.city_id')
                ->get();

            $stationBarangays = $stationBarangays->merge($urbiztondoBarangays)->unique('barangay_id')->values();
        }

        if ($stationBarangays->isEmpty()) {
            $stationBarangays = DB::table('barangays')
                ->select('barangay_id', 'barangay_name', 'city_id')
                ->orderBy('barangay_name')
                ->limit(12)
                ->get();
        }

        $barangayCoverageByStation[(int) $stationId] = $stationBarangays->unique('barangay_id')->values();
    }

    $coveredBarangayIds = collect($barangayCoverageByStation)
        ->flatten(1)
        ->pluck('barangay_id')
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->unique()
        ->values();

    /** @var PredictionFeedbackSignalService $predictionFeedbackSignals */
    $predictionFeedbackSignals = app(PredictionFeedbackSignalService::class);
    $predictionFeedbackSignals->warmForBarangays($coveredBarangayIds);

    $allHistoricalIncidents = DB::table('historical_fire_incidents')
        ->select([
            'barangay_id',
            'incident_date',
            'incident_time',
            'incident_count',
            'estimated_damage',
            'injured_count',
            'death_count',
            'temperature',
            'humidity',
            'wind_speed',
            'weather_condition',
            'fire_cause',
        ])
        ->whereNotNull('barangay_id')
        ->get()
        ->groupBy(function ($row) {
            try {
                $date = Carbon::parse($row->incident_date);
            } catch (\Throwable $e) {
                return 'invalid';
            }

            return implode('|', [
                (int) $row->barangay_id,
                $date->year,
                strtolower($date->format('F')),
            ]);
        });

    $allDailyWeather = Schema::hasTable('daily_weather_contexts')
        ? DB::table('daily_weather_contexts')
            ->select([
                'barangay_id',
                'observation_date',
                'temperature_avg_c',
                'humidity_avg_percent',
                'wind_avg_kph',
                'dominant_condition',
            ])
            ->get()
            ->groupBy(function ($row) {
                $date = Carbon::parse($row->observation_date);

                return implode('|', [
                    (int) $row->barangay_id,
                    $date->year,
                    strtolower($date->format('F')),
                ]);
            })
        : collect();

    $records = [];

    foreach ($historicalRows as $row) {
        $stationId = (int) $row->station_id;
        $barangays = $barangayCoverageByStation[$stationId] ?? collect();

        if ($barangays->isEmpty()) {
            continue;
        }

        foreach ($barangays as $barangay) {
            try {
                $snapshotDate = Carbon::createFromFormat('F Y', trim((string) $row->month).' '.(int) $row->year)->endOfMonth();
            } catch (\Throwable $e) {
                $snapshotDate = Carbon::create((int) $row->year, 12, 31)->endOfDay();
            }

            $incidentKey = implode('|', [
                (int) $barangay->barangay_id,
                (int) $row->year,
                strtolower((string) $row->month),
            ]);

            $monthlyIncidents = $allHistoricalIncidents->get($incidentKey, collect());
            $monthlyDailyWeather = $allDailyWeather->get($incidentKey, collect());
            $incidentCount = HistoricalFireRecordSemantics::incidentCount($monthlyIncidents);
            $estimatedDamage = (float) $monthlyIncidents->sum(function ($incident) {
                return (float) ($incident->estimated_damage ?? 0);
            });
            $injuredCount = (int) $monthlyIncidents->sum('injured_count');
            $deathCount = (int) $monthlyIncidents->sum('death_count');

            $temperatureAverage = $monthlyDailyWeather->isNotEmpty()
                ? $monthlyDailyWeather->avg('temperature_avg_c')
                : $monthlyIncidents->avg('temperature');
            $humidityAverage = $monthlyDailyWeather->isNotEmpty()
                ? $monthlyDailyWeather->avg('humidity_avg_percent')
                : $monthlyIncidents->avg('humidity');
            $windAverage = $monthlyDailyWeather->isNotEmpty()
                ? $monthlyDailyWeather->avg('wind_avg_kph')
                : $monthlyIncidents->avg('wind_speed');

            $weatherMode = ($monthlyDailyWeather->isNotEmpty()
                ? $monthlyDailyWeather->pluck('dominant_condition')
                : $monthlyIncidents->pluck('weather_condition'))
                ->filter()
                ->map(fn ($value) => trim((string) $value))
                ->countBy()
                ->sortDesc()
                ->keys()
                ->first();

            $causeMode = $monthlyIncidents
                ->pluck('fire_cause')
                ->filter()
                ->map(fn ($value) => trim((string) $value))
                ->countBy()
                ->sortDesc()
                ->keys()
                ->first();

            $peakHours = $monthlyIncidents->map(function ($incident) {
                if (empty($incident->incident_time)) {
                    return null;
                }

                try {
                    $hour = (int) Carbon::parse($incident->incident_time)->format('H');
                } catch (\Throwable $e) {
                    return null;
                }

                if ($hour >= 5 && $hour < 12) {
                    return 'morning';
                }
                if ($hour >= 12 && $hour < 17) {
                    return 'afternoon';
                }
                if ($hour >= 17 && $hour < 21) {
                    return 'evening';
                }

                return 'night';
            })
                ->filter()
                ->countBy()
                ->sortDesc()
                ->keys()
                ->first();

            $riskSignals = $riskAreaSignalsByBarangay[(int) $barangay->barangay_id] ?? null;
            $riskProfile = $riskAreaProfilesByBarangay[(int) $barangay->barangay_id] ?? null;
            $verifiedRiskAreaCount = (int) ($riskSignals->verified_risk_area_count ?? 0);
            $verifiedHazardCount = (int) ($riskSignals->verified_hazard_count ?? 0);
            $verifiedRiskCount = (int) ($riskSignals->verified_risk_count ?? 0);
            $highAssessmentCount = (int) ($riskSignals->high_assessment_count ?? 0);
            $blockedHydrantCount = (int) ($riskSignals->blocked_hydrant_count ?? 0);
            $combustibleMaterialCount = (int) ($riskSignals->combustible_material_count ?? 0);
            $informalSettlementCount = (int) ($riskSignals->informal_settlement_count ?? 0);
            $cookingFireActivityCount = (int) ($riskSignals->cooking_fire_activity_count ?? 0);
            $poorWasteDisposalCount = (int) ($riskSignals->poor_waste_disposal_count ?? 0);
            $previousFireProneCount = (int) ($riskSignals->previous_fire_prone_count ?? 0);
            $poorAccessCount = (int) ($riskSignals->poor_access_count ?? 0);
            $affectedHouseholds = (int) ($riskSignals->affected_households ?? 0);
            $riskAreaPressureSignal = round(
                ($verifiedRiskAreaCount * 0.8)
                + ($verifiedHazardCount * 1.1)
                + ($highAssessmentCount * 2.5)
                + ($blockedHydrantCount * 1.8)
                + ($combustibleMaterialCount * 1.6)
                + ($informalSettlementCount * 1.3)
                + ($cookingFireActivityCount * 1.2)
                + ($poorWasteDisposalCount * 0.8)
                + ($previousFireProneCount * 1.6)
                + ($poorAccessCount * 1.4)
                + min($affectedHouseholds / 25, 5),
                4
            );

            $riskLevel = 'low';
            if ($incidentCount >= 3 || $estimatedDamage >= 250000 || $deathCount > 0 || $highAssessmentCount >= 2 || $riskAreaPressureSignal >= 7) {
                $riskLevel = 'high';
            } elseif ($incidentCount >= 1 || $estimatedDamage >= 50000 || $injuredCount > 0 || $verifiedRiskAreaCount >= 2 || $riskAreaPressureSignal >= 3) {
                $riskLevel = 'medium';
            }

            $feedbackSignals = $predictionFeedbackSignals->snapshotForBarangay((int) $barangay->barangay_id, $snapshotDate);

            $records[] = [
                'station_id' => $stationId,
                'barangay_id' => (int) $barangay->barangay_id,
                'barangay_name' => $barangay->barangay_name,
                'month' => $row->month,
                'year' => (int) $row->year,
                'incident_count' => $incidentCount,
                'estimated_damage' => round($estimatedDamage, 2),
                'injured_count' => $injuredCount,
                'death_count' => $deathCount,
                'risk_level' => $riskLevel,
                'average_temperature' => round((float) ($temperatureAverage ?? $row->average_temperature ?? 0), 2),
                'average_humidity' => $humidityAverage !== null ? round((float) $humidityAverage, 2) : null,
                'average_wind_speed' => $windAverage !== null ? round((float) $windAverage, 2) : null,
                'weather_condition' => $weatherMode ?: 'unknown',
                'season' => $row->season,
                'near_water' => (int) ($riskSignals->near_water ?? $row->near_water ?? 0),
                'near_hydrant' => (int) ($riskProfile->near_hydrant ?? 0),
                'near_water_source' => (int) ($riskProfile->near_water_source ?? 0),
                'verified_risk_area_count' => $verifiedRiskAreaCount,
                'verified_hazard_count' => $verifiedHazardCount,
                'verified_risk_count' => $verifiedRiskCount,
                'high_assessment_count' => $highAssessmentCount,
                'blocked_hydrant_count' => $blockedHydrantCount,
                'combustible_material_count' => $combustibleMaterialCount,
                'informal_settlement_count' => $informalSettlementCount,
                'cooking_fire_activity_count' => $cookingFireActivityCount,
                'poor_waste_disposal_count' => $poorWasteDisposalCount,
                'previous_fire_prone_count' => $previousFireProneCount,
                'poor_access_count' => $poorAccessCount,
                'affected_households' => $affectedHouseholds,
                'risk_area_pressure_signal' => $riskAreaPressureSignal,
                'ai_checklist_total' => (int) ($feedbackSignals['ai_checklist_total'] ?? 0),
                'ai_checklist_confirmed_count' => (int) ($feedbackSignals['ai_checklist_confirmed_count'] ?? 0),
                'ai_checklist_active_count' => (int) ($feedbackSignals['ai_checklist_active_count'] ?? 0),
                'ai_checklist_completion_ratio' => (float) ($feedbackSignals['ai_checklist_completion_ratio'] ?? 0),
                'ai_checklist_item_total' => (int) ($feedbackSignals['ai_checklist_item_total'] ?? 0),
                'ai_checklist_item_followed_ratio' => (float) ($feedbackSignals['ai_checklist_item_followed_ratio'] ?? 0),
                'linked_mitigation_total' => (int) ($feedbackSignals['linked_mitigation_total'] ?? 0),
                'linked_mitigation_completed_count' => (int) ($feedbackSignals['linked_mitigation_completed_count'] ?? 0),
                'linked_mitigation_blocked_count' => (int) ($feedbackSignals['linked_mitigation_blocked_count'] ?? 0),
                'linked_mitigation_open_count' => (int) ($feedbackSignals['linked_mitigation_open_count'] ?? 0),
                'mitigation_completion_ratio' => (float) ($feedbackSignals['mitigation_completion_ratio'] ?? 0),
                'avg_mitigation_completion_days' => (float) ($feedbackSignals['avg_mitigation_completion_days'] ?? 0),
                'mitigation_readiness_signal' => (float) ($feedbackSignals['mitigation_readiness_signal'] ?? 0),
                'hazard_category' => $riskProfile->hazard_category ?? 'unknown',
                'risk_type' => $riskProfile->risk_type ?? 'unknown',
                'condition_label' => $riskProfile->condition_label ?? 'unknown',
                'population_density_level' => $riskProfile->population_density_level ?? 'unknown',
                'building_density_level' => $riskProfile->building_density_level ?? 'unknown',
                'road_accessibility' => $riskProfile->road_accessibility ?? 'unknown',
                'density' => $row->density ?: 'medium',
                'materials' => $row->materials ?: 'mixed',
                'peak_hours' => $peakHours ?: ($row->peak_hours ?: 'unknown'),
                'accesibility' => $row->accesibility ?: 'moderate access',
                'type_incident' => $causeMode ?: ($row->type_incident ?: 'unknown'),
                'occupancy' => $row->occupancy ?: 'mixed-use',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ];
        }
    }

    $payload = [
        'source' => 'database',
        'table' => 'station_barangay_monthly_forecast',
        'record_count' => count($records),
        'generated_at' => now()->toIso8601String(),
        'schema_columns' => [
            'station_id',
            'barangay_id',
            'barangay_name',
            'month',
            'year',
            'incident_count',
            'estimated_damage',
            'injured_count',
            'death_count',
            'risk_level',
            'average_temperature',
            'average_humidity',
            'average_wind_speed',
            'weather_condition',
            'season',
            'near_water',
            'near_hydrant',
            'near_water_source',
            'verified_risk_area_count',
            'verified_hazard_count',
            'verified_risk_count',
            'high_assessment_count',
            'blocked_hydrant_count',
            'combustible_material_count',
            'informal_settlement_count',
            'cooking_fire_activity_count',
            'poor_waste_disposal_count',
            'previous_fire_prone_count',
            'poor_access_count',
            'affected_households',
            'risk_area_pressure_signal',
            'ai_checklist_total',
            'ai_checklist_confirmed_count',
            'ai_checklist_active_count',
            'ai_checklist_completion_ratio',
            'ai_checklist_item_total',
            'ai_checklist_item_followed_ratio',
            'linked_mitigation_total',
            'linked_mitigation_completed_count',
            'linked_mitigation_blocked_count',
            'linked_mitigation_open_count',
            'mitigation_completion_ratio',
            'avg_mitigation_completion_days',
            'mitigation_readiness_signal',
            'hazard_category',
            'risk_type',
            'condition_label',
            'population_density_level',
            'building_density_level',
            'road_accessibility',
            'density',
            'materials',
            'peak_hours',
            'accesibility',
            'type_incident',
            'occupancy',
            'created_at',
            'updated_at',
        ],
        'records' => $records,
    ];

    File::put($outputPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $this->info("Exported {$payload['record_count']} station-barangay monthly records to {$outputPath}");

    return 0;
})->purpose('Export a live DB-backed station + barangay monthly forecasting dataset.');

Artisan::command('ml:export-incident-dataset {--path=capstone-ai/merged_incident_dataset.json}', function () {
    $outputPath = base_path($this->option('path'));
    $outputDirectory = dirname($outputPath);

    if (! is_dir($outputDirectory)) {
        File::makeDirectory($outputDirectory, 0755, true);
    }

    $historicalRows = DB::table('historical_data')->get()->keyBy(function ($row) {
        return implode('|', [
            (string) $row->station_id,
            (string) $row->year,
            strtolower((string) $row->month),
        ]);
    });

    $incidents = Incident::query()
        ->with([
            'marker',
            'team.station',
            'spotInvestigationReport.investigator.station',
            'spotInvestigationReport.progressInvestigation.fireOrigin',
            'spotInvestigationReport.progressInvestigation.finalInvestigation',
        ])
        ->whereHas('spotInvestigationReport')
        ->get();

    $records = $incidents->map(function (Incident $incident) use ($historicalRows) {
        $spot = $incident->spotInvestigationReport;
        $progress = $spot?->progressInvestigation;
        $final = $progress?->finalInvestigation;
        $marker = $incident->marker;

        $station = $spot?->investigator?->station ?? $incident->team?->station;
        $stationId = $station?->station_id;

        $incidentMoment = $spot?->fire_started ?? $incident->created_at;
        $monthName = $incidentMoment?->format('F');
        $yearValue = $incidentMoment?->format('Y');
        $historicalKey = $stationId && $monthName && $yearValue
            ? implode('|', [(string) $stationId, (string) $yearValue, strtolower($monthName)])
            : null;
        $historical = $historicalKey ? $historicalRows->get($historicalKey) : null;

        $durationMinutes = null;
        if ($spot?->fire_started && $spot?->fire_out) {
            $durationMinutes = $spot->fire_started->diffInMinutes($spot->fire_out);
        }

        return [
            'incident_id' => $incident->incident_id,
            'station_id' => $stationId,
            'station_name' => $station?->station_name,
            'station_code' => $station?->station_code,
            'incident_status' => $incident->status,
            'incident_created_at' => optional($incident->created_at)?->toIso8601String(),
            'incident_month' => $monthName,
            'incident_year' => $yearValue ? (int) $yearValue : null,
            'marker_id' => $marker?->marker_id,
            'marker_title' => $marker?->title,
            'marker_category' => $marker?->category,
            'marker_lat' => $marker?->lat,
            'marker_lng' => $marker?->lng,
            'spot_investigation_report_id' => $spot?->spot_investigation_report_id,
            'fire_started' => optional($spot?->fire_started)?->toIso8601String(),
            'fire_out' => optional($spot?->fire_out)?->toIso8601String(),
            'duration_minutes' => $durationMinutes,
            'dead' => $spot?->dead,
            'injured' => $spot?->injured,
            'owner' => $spot?->owner,
            'spot_estimated_damage' => $spot?->estimated_damage,
            'spot_occupancy' => $spot?->occupancy,
            'estimated_area' => $spot?->estimated_area,
            'weather_context' => $spot?->weather_context,
            'progress_investigation_report_id' => $progress?->progress_investigation_report_id,
            'fire_origin' => $progress?->fireOrigin?->origin,
            'responsible_arson' => $progress?->responsible_arson,
            'fire_cause' => $progress?->fire_cause,
            'final_investigation_report_id' => $final?->final_investigation_report_id,
            'final_total_damage' => $final?->total_damage,
            'exact_address' => $final?->exact_address,
            'historical_incident_count' => $historical?->incident_count,
            'historical_risk_level' => $historical?->risk_level,
            'historical_average_temperature' => $historical?->average_temperature,
            'historical_season' => $historical?->season,
            'historical_near_water' => $historical?->near_water,
            'historical_density' => $historical?->density,
            'historical_materials' => $historical?->materials,
            'historical_peak_hours' => $historical?->peak_hours,
            'historical_accesibility' => $historical?->accesibility,
            'historical_type_incident' => $historical?->type_incident,
            'historical_occupancy' => $historical?->occupancy,
        ];
    })->values()->all();

    $payload = [
        'source' => 'database',
        'table_strategy' => [
            'primary' => 'incidents + spot/progress/final investigation',
            'context' => 'historical_data joined by station_id + incident month/year',
        ],
        'record_count' => count($records),
        'generated_at' => now()->toIso8601String(),
        'records' => $records,
    ];

    File::put($outputPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $this->info("Exported {$payload['record_count']} merged records to {$outputPath}");
})->purpose('Export a merged ML-ready incident dataset from the live database.');

Artisan::command('ai:train-barangay-model {--dataset=capstone-ai/station_barangay_monthly_demo.json} {--generate-demo}', function () {
    $datasetPath = base_path($this->option('dataset'));

    if ($this->option('generate-demo')) {
        $this->info('Generating demo station + barangay monthly dataset...');
        $pythonBinary = PythonRuntime::resolve((string) config('services.ai_prediction.python_binary', 'python3'));
        $generateProcess = Process::path(base_path())
            ->run([$pythonBinary, 'capstone-ai/generate_station_barangay_monthly_json.py']);

        if ($generateProcess->failed()) {
            $this->error('Failed to generate demo dataset.');
            $this->line($generateProcess->errorOutput() ?: $generateProcess->output());

            return 1;
        }

        $this->line(trim($generateProcess->output()));
    }

    if (! File::exists($datasetPath)) {
        $this->error("Dataset not found: {$datasetPath}");
        $this->line('Tip: rerun with --generate-demo or point --dataset to an existing JSON file.');

        return 1;
    }

    $this->info("Training barangay model using {$datasetPath}...");
    $pythonBinary = PythonRuntime::resolve((string) config('services.ai_prediction.python_binary', 'python3'));
    $trainingScript = (string) config('services.ai_prediction.training_script', 'capstone-ai/train_model.py');
    $trainProcess = Process::path(base_path())
        ->run([$pythonBinary, $trainingScript, $datasetPath]);

    if ($trainProcess->failed()) {
        $this->error('Barangay model training failed.');
        $this->line($trainProcess->errorOutput() ?: $trainProcess->output());

        return 1;
    }

    $this->line(trim($trainProcess->output()));
    $this->info('Barangay model training complete.');
    $this->line('Saved files:');
    $this->line('- capstone-ai/fire_model.pkl');
    $this->line('- capstone-ai/fire_model_metadata.json');

    return 0;
})->purpose('Train the station + barangay monthly fire-risk model.');

Artisan::command('ai:train-forecast-model {--dataset=capstone-ai/station_barangay_monthly_db.json} {--export}', function () {
    $datasetPath = base_path($this->option('dataset'));

    if ($this->option('export') || ! File::exists($datasetPath)) {
        $this->info('Exporting live station-barangay monthly dataset...');
        $exportExitCode = Artisan::call('ml:export-station-barangay-dataset', [
            '--path' => $this->option('dataset'),
        ]);

        $this->line(Artisan::output());

        if ($exportExitCode !== 0) {
            $this->error('Live dataset export failed.');

            return 1;
        }
    }

    if (! File::exists($datasetPath)) {
        $this->error("Dataset not found: {$datasetPath}");

        return 1;
    }

    $this->info("Training forecast model using {$datasetPath}...");
    $pythonBinary = PythonRuntime::resolve((string) config('services.ai_prediction.python_binary', 'python3'));
    $pythonPath = trim((string) config('services.ai_prediction.python_path', ''));
    $pythonEnvironment = $pythonPath !== '' ? ['PYTHONPATH' => $pythonPath] : [];
    if (PHP_OS_FAMILY === 'Windows') {
        foreach (['SYSTEMROOT', 'WINDIR', 'PATH', 'TEMP', 'TMP', 'APPDATA', 'LOCALAPPDATA'] as $name) {
            $value = getenv($name);
            if (is_string($value) && $value !== '') {
                $pythonEnvironment[$name] = $value;
            }
        }
    }
    $trainingScript = (string) config('services.ai_prediction.training_script', 'capstone-ai/train_model.py');
    $trainingTimeout = max(60, (int) config('services.ai_prediction.training_timeout_seconds', 240));

    try {
        $trainProcess = Process::path(base_path())
            ->env($pythonEnvironment)
            ->timeout($trainingTimeout)
            ->run([$pythonBinary, $trainingScript, $datasetPath]);
    } catch (\Throwable $error) {
        $this->error('Forecast model training process could not finish.');
        $this->line($error->getMessage());

        return 1;
    }

    if ($trainProcess->failed()) {
        $this->error('Forecast model training failed.');
        $this->line($trainProcess->errorOutput() ?: $trainProcess->output());

        return 1;
    }

    $this->line(trim($trainProcess->output()));
    $this->info('Forecast model training complete.');

    return 0;
})->purpose('Export the live monthly forecasting dataset and train the shared forecast model.');

Artisan::command('ai:predict-barangay-risk {payload?} {--file=capstone-ai/sample_station_barangay_request.json} {--prompt-payload}', function (?string $payload = null) {
    $inputArgument = $payload;

    if (! $inputArgument) {
        $inputPath = base_path($this->option('file'));
        if (! File::exists($inputPath)) {
            $this->error("Prediction input file not found: {$inputPath}");

            return 1;
        }

        $inputArgument = $inputPath;
    }

    $script = $this->option('prompt-payload')
        ? 'capstone-ai/build_ai_prompt_payload.py'
        : 'capstone-ai/predict_event.py';

    $this->info($this->option('prompt-payload')
        ? 'Building AI-ready prediction payload...'
        : 'Running barangay risk prediction...');

    $pythonBinary = PythonRuntime::resolve((string) config('services.ai_prediction.python_binary', 'python3'));
    $pythonPath = trim((string) config('services.ai_prediction.python_path', ''));
    $pythonEnvironment = $pythonPath !== '' ? ['PYTHONPATH' => $pythonPath] : [];
    if (PHP_OS_FAMILY === 'Windows') {
        foreach (['SYSTEMROOT', 'WINDIR', 'PATH', 'TEMP', 'TMP', 'APPDATA', 'LOCALAPPDATA'] as $name) {
            $value = getenv($name);
            if (is_string($value) && $value !== '') {
                $pythonEnvironment[$name] = $value;
            }
        }
    }
    $predictProcess = Process::path(base_path())
        ->env($pythonEnvironment)
        ->run([$pythonBinary, $script, $inputArgument]);

    if ($predictProcess->failed()) {
        $this->error('Prediction command failed.');
        $this->line($predictProcess->errorOutput() ?: $predictProcess->output());

        return 1;
    }

    $this->line(trim($predictProcess->output()));

    return 0;
})->purpose('Predict barangay fire risk from a JSON file or inline JSON payload.');
