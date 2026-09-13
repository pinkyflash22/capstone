<?php

namespace App\Http\Controllers\staff;

use App\Http\Controllers\Controller;
use App\Models\ProgressInvestigationReport;
use App\Models\StationOfficer;
use App\Services\ProgressInvestigationPirExporter;
use Illuminate\Http\Request;
use Throwable;

class ProgressInvestigationPirExportController extends Controller
{
    public function downloadWord(Request $request, int $progressReportId, ProgressInvestigationPirExporter $exporter)
    {
        $report = $this->resolveAccessibleProgressReport($request, $progressReportId);
        try {
            $path = $exporter->buildDocx($report);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The Progress Investigation Report Word file could not be generated. Please try again.');
        }

        return response()->download(
            $path,
            $exporter->buildFilename($report, 'docx'),
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
            ]
        )->deleteFileAfterSend(true);
    }

    private function resolveAccessibleProgressReport(Request $request, int $progressReportId): ProgressInvestigationReport
    {
        $userId = $request->user()?->user_id;
        abort_unless($userId, 403);

        if (strtolower(trim((string) ($request->user()?->role?->role ?? ''))) === 'provincial admin') {
            return ProgressInvestigationReport::query()->with([
                'spotInvestigation.incident.marker', 'spotInvestigation.incident.barangay.city.province',
                'spotInvestigation.investigator.user', 'investigator.user', 'investigator.station.address.barangay.city.province',
                'fireOrigin', 'notedBy.user', 'approvedBy.user',
            ])->findOrFail($progressReportId);
        }

        $stationOfficer = StationOfficer::query()
            ->where('user_id', $userId)
            ->firstOrFail();

        $stationOfficerIds = StationOfficer::query()
            ->where('station_id', $stationOfficer->station_id)
            ->pluck('station_officer_id');

        return ProgressInvestigationReport::query()
            ->with([
                'spotInvestigation.incident.marker',
                'spotInvestigation.incident.barangay.city.province',
                'spotInvestigation.investigator.user',
                'investigator.user',
                'investigator.station.address.barangay.city.province',
                'fireOrigin',
                'notedBy.user',
                'approvedBy.user',
            ])
            ->where('progress_investigation_report_id', $progressReportId)
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->firstOrFail();
    }
}
