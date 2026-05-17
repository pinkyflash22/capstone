<?php

use App\Models\HistoricalFireIncident;
use App\Models\Incident;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
