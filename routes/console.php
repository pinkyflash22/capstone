<?php

use App\Models\HistoricalFireIncident;
use App\Models\Incident;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

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

    $riskAreaWaterByBarangay = DB::table('risk_areas')
        ->join('barangay_halls', 'risk_areas.barangay_hall_id', '=', 'barangay_halls.barangay_hall_id')
        ->selectRaw('barangay_halls.barangay_id, MAX(CASE WHEN risk_areas.near_water_source = 1 OR risk_areas.near_hydrant = 1 THEN 1 ELSE 0 END) as near_water')
        ->groupBy('barangay_halls.barangay_id')
        ->pluck('near_water', 'barangay_halls.barangay_id');

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

    $records = [];

    foreach ($historicalRows as $row) {
        $stationId = (int) $row->station_id;
        $barangays = $barangayCoverageByStation[$stationId] ?? collect();

        if ($barangays->isEmpty()) {
            continue;
        }

        foreach ($barangays as $barangay) {
            $incidentKey = implode('|', [
                (int) $barangay->barangay_id,
                (int) $row->year,
                strtolower((string) $row->month),
            ]);

            $monthlyIncidents = $allHistoricalIncidents->get($incidentKey, collect());
            $incidentCount = (int) $monthlyIncidents->sum(function ($incident) {
                return max(1, (int) ($incident->incident_count ?? 1));
            });
            $estimatedDamage = (float) $monthlyIncidents->sum(function ($incident) {
                return (float) ($incident->estimated_damage ?? 0);
            });
            $injuredCount = (int) $monthlyIncidents->sum('injured_count');
            $deathCount = (int) $monthlyIncidents->sum('death_count');

            $temperatureAverage = $monthlyIncidents->avg('temperature');
            $humidityAverage = $monthlyIncidents->avg('humidity');
            $windAverage = $monthlyIncidents->avg('wind_speed');

            $weatherMode = $monthlyIncidents
                ->pluck('weather_condition')
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

            $riskLevel = 'low';
            if ($incidentCount >= 3 || $estimatedDamage >= 250000 || $deathCount > 0) {
                $riskLevel = 'high';
            } elseif ($incidentCount >= 1 || $estimatedDamage >= 50000 || $injuredCount > 0) {
                $riskLevel = 'medium';
            }

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
                'near_water' => (int) ($riskAreaWaterByBarangay[(int) $barangay->barangay_id] ?? $row->near_water ?? 0),
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
        $generateProcess = Process::path(base_path())
            ->run(['python', 'capstone-ai/generate_station_barangay_monthly_json.py']);

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
    $trainProcess = Process::path(base_path())
        ->run(['python', 'capstone-ai/train_model.py', $datasetPath]);

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
    $trainProcess = Process::path(base_path())
        ->run(['python', 'capstone-ai/train_model.py', $datasetPath]);

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

    $predictProcess = Process::path(base_path())
        ->run(['python', $script, $inputArgument]);

    if ($predictProcess->failed()) {
        $this->error('Prediction command failed.');
        $this->line($predictProcess->errorOutput() ?: $predictProcess->output());

        return 1;
    }

    $this->line(trim($predictProcess->output()));

    return 0;
})->purpose('Predict barangay fire risk from a JSON file or inline JSON payload.');
