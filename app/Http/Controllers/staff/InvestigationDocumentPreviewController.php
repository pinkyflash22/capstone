<?php

namespace App\Http\Controllers\staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FinalInvestigationReportController;
use App\Models\{FinalInvestigationReport, FireOrigin, Incident, InvestigationReportImage, ProgressInvestigationReport, SpotInvestigationReport, StationOfficer, Team};
use App\Services\{DocxProofImageAppender, FinalInvestigationFirExporter, InvestigationDocumentPreviewRenderer, InvestigationReportImageService, ProgressInvestigationPirExporter, SpotInvestigationSirExporter};
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use ZipArchive;

class InvestigationDocumentPreviewController extends Controller
{
    public function show(Request $request, string $type, int $reportId, InvestigationDocumentPreviewRenderer $renderer)
    {
        abort_unless(in_array($type, ['spot', 'progress', 'final'], true), 404);
        $officer = StationOfficer::where('user_id', $request->user()->user_id)->firstOrFail();
        abort_unless($officer->station_id, 403);
        $officerIds = StationOfficer::where('station_id', $officer->station_id)->pluck('station_officer_id');

        $report = match ($type) {
            'spot' => SpotInvestigationReport::with([
                'incident.marker', 'incident.barangay.city.province', 'incident.context',
                'investigator.user', 'investigator.station.address.barangay.city.province',
                'notedBy.user', 'approvedBy.user', 'proofImages',
            ])->whereIn('station_officer_id', $officerIds)->findOrFail($reportId),
            'progress' => ProgressInvestigationReport::with([
                'spotInvestigation.incident.marker', 'spotInvestigation.incident.barangay.city.province',
                'spotInvestigation.investigator.user', 'investigator.user',
                'investigator.station.address.barangay.city.province', 'fireOrigin',
                'notedBy.user', 'approvedBy.user', 'proofImages',
            ])->whereIn('station_officer_id', $officerIds)->findOrFail($reportId),
            'final' => FinalInvestigationReport::with([
                'progressInvestigation.spotInvestigation.incident.marker',
                'progressInvestigation.spotInvestigation.incident.barangay.city.province',
                'progressInvestigation.spotInvestigation.investigator.user',
                'progressInvestigation.fireOrigin', 'investigator.user',
                'investigator.station.address.barangay.city.province',
                'notedBy.user', 'approvedBy.user', 'proofImages',
            ])->whereIn('station_officer_id', $officerIds)->findOrFail($reportId),
        };

        return $this->renderSavedReport($type, $report, $renderer);
    }

    public function __invoke(Request $request, string $type, InvestigationDocumentPreviewRenderer $renderer)
    {
        abort_unless(in_array($type, ['spot', 'progress', 'final'], true), 404);
        $officer = StationOfficer::with(['user', 'station.address.barangay.city.province'])
            ->where('user_id', $request->user()->user_id)->firstOrFail();
        abort_unless($officer->station_id, 403);
        $officerIds = StationOfficer::where('station_id', $officer->station_id)->pluck('station_officer_id');

        $model = match ($type) {
            'spot' => new SpotInvestigationReport(),
            'progress' => new ProgressInvestigationReport(),
            'final' => new FinalInvestigationReport(),
        };
        // Allow incomplete drafts, but never accept ownership/review fields from the client.
        $fields = array_diff($model->getFillable(), [
            'station_officer_id', 'weather_context', 'review_status', 'review_comments',
            'reviewed_by_user_id', 'reviewed_at',
        ]);
        $rules = array_fill_keys($fields, 'nullable|string|max:50000');
        foreach (['incident_id', 'spot_investigation_id', 'progress_investigation_id', 'parent_progress_investigation_id', 'noted_by_station_officer_id', 'approved_by_station_officer_id'] as $field) {
            if (isset($rules[$field])) $rules[$field] = 'nullable|integer|min:1';
        }
        foreach (['dead', 'injured', 'affected_establishments_count', 'estimated_damage', 'estimated_area', 'total_damage'] as $field) {
            if (isset($rules[$field])) $rules[$field] = 'nullable|numeric|min:0|max:999999999999';
        }
        foreach (['fire_started', 'fire_out', 'report_completed_at', 'mission_order_date', 'alarm_at'] as $field) {
            if (isset($rules[$field])) $rules[$field] = 'nullable|date';
        }
        if ($type === 'progress') {
            $rules['fire_origin_id'] = ['nullable', 'regex:/^(other|[0-9]+)$/'];
            $rules['fire_origin_other'] = 'nullable|string|max:255';
            $rules['report_stage'] = 'nullable|in:single,first,second';
        }
        if ($type === 'final') {
            $rules['substantiating_documents'] = 'nullable|array|max:30';
            $rules['substantiating_documents.*'] = 'string|max:255';
        }
        $data = $request->validate([...$rules, ...InvestigationReportImageService::validationRules()]);
        $attributes = Arr::only($data, $fields);

        if ($type === 'spot') {
            $incident = null;
            if (! empty($attributes['incident_id'])) {
                // Same station-team scope as the My Station incident selector.
                $teamIds = Team::where(function ($query) use ($officerIds) {
                    foreach (['commander_driver', 'pumper', 'team_leader', 'firefighter_officer_1', 'firefighter_officer_2', 'firefighter_officer_3', 'rescue_radio'] as $column) {
                        $query->orWhereIn($column, $officerIds);
                    }
                })->select('team_id');
                $incident = Incident::with(['marker', 'barangay.city.province', 'context'])
                    ->whereIn('team_id', $teamIds)->findOrFail($attributes['incident_id']);
            }
            $model->setRelation('incident', $incident);
            $attributes['report_completed_at'] = now();
        } elseif ($type === 'progress') {
            $spot = ! empty($attributes['spot_investigation_id'])
                ? SpotInvestigationReport::with(['incident.marker', 'incident.barangay.city.province', 'incident.context'])
                    ->whereIn('station_officer_id', $officerIds)->findOrFail($attributes['spot_investigation_id']) : null;
            if (! empty($attributes['parent_progress_investigation_id'])) {
                ProgressInvestigationReport::whereIn('station_officer_id', $officerIds)
                    ->where('spot_investigation_id', $spot?->getKey())
                    ->findOrFail($attributes['parent_progress_investigation_id']);
            }
            $originId = $attributes['fire_origin_id'] ?? null;
            $origin = $originId === 'other'
                ? new FireOrigin(['origin' => $data['fire_origin_other'] ?? ''])
                : ($originId ? FireOrigin::findOrFail($originId) : null);
            if ($originId === 'other') $attributes['fire_origin_id'] = null;
            $model->setRelation('spotInvestigation', $spot)->setRelation('fireOrigin', $origin);
        } else {
            $progress = ! empty($attributes['progress_investigation_id'])
                ? ProgressInvestigationReport::with([
                    'spotInvestigation.incident.marker', 'spotInvestigation.incident.barangay.city.province',
                    'fireOrigin', 'investigator.station.address.barangay.city.province',
                ])->whereIn('station_officer_id', $officerIds)->findOrFail($attributes['progress_investigation_id']) : null;
            if ($progress) {
                // Reuse exactly the defaults applied by submission; this method performs no writes.
                $attributes = app(FinalInvestigationReportController::class)->mergeDerivedDefaults(
                    array_replace(array_fill_keys(['office_address', 'alarm_at', 'establishment_burned', 'fire_victims', 'origin_of_fire', 'cause_of_fire'], null), $attributes),
                    $progress
                );
            }
            $model->setRelation('progressInvestigation', $progress);
        }
        $model->fill($attributes);
        $model->station_officer_id = $officer->getKey();
        $model->created_at = now();
        $model->setRelation('investigator', $officer);
        $model->setRelation('proofImages', collect());
        foreach (['notedBy' => 'noted_by_station_officer_id', 'approvedBy' => 'approved_by_station_officer_id'] as $relation => $field) {
            $selected = ! empty($attributes[$field])
                ? StationOfficer::with('user')->whereIn('station_officer_id', $officerIds)->findOrFail($attributes[$field]) : null;
            $model->setRelation($relation, $selected);
        }

        $docx = $pdf = null;
        try {
            $exporter = app(match ($type) {
                'spot' => SpotInvestigationSirExporter::class,
                'progress' => ProgressInvestigationPirExporter::class,
                'final' => FinalInvestigationFirExporter::class,
            });
            $docx = $exporter->buildDocx($model);
            $files = array_values($request->file('proof_images', []));
            if ($files) {
                $images = collect($files)->map(function ($file, $index) {
                    $image = new InvestigationReportImage(['file_name' => $file->getClientOriginalName()]);
                    $image->investigation_report_image_id = $index;
                    return $image;
                });
                $zip = new ZipArchive();
                if ($zip->open($docx) !== true) throw new \RuntimeException('Unable to open preview document.');
                try {
                    app(DocxProofImageAppender::class)->append($zip, $images, 'PHOTO DOCUMENTATION',
                        fn ($image) => file_get_contents($files[$image->investigation_report_image_id]->getRealPath()));
                } finally {
                    $zip->close();
                }
            }
            $pdf = $renderer->render($docx);

            return response(file_get_contents($pdf), 200, [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
                'X-Investigation-Preview-Renderer' => 'server',
            ]);
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'The Word template preview could not be rendered. Please retry. If it persists, check the server document renderer.'], 503);
        } finally {
            foreach ([$docx, $pdf] as $path) {
                if ($path && is_file($path)) unlink($path);
            }
        }
    }

    private function renderSavedReport(string $type, $report, InvestigationDocumentPreviewRenderer $renderer)
    {
        $docx = $pdf = null;
        try {
            $exporter = app(match ($type) {
                'spot' => SpotInvestigationSirExporter::class,
                'progress' => ProgressInvestigationPirExporter::class,
                'final' => FinalInvestigationFirExporter::class,
            });
            $docx = $exporter->buildDocx($report);
            $pdf = $renderer->render($docx);

            return response(file_get_contents($pdf), 200, [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
                'X-Investigation-Preview-Renderer' => 'server',
            ]);
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'The saved report preview could not be rendered. Please retry.'], 503);
        } finally {
            foreach ([$docx, $pdf] as $path) {
                if ($path && is_file($path)) unlink($path);
            }
        }
    }
}
