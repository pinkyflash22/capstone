<?php

namespace App\Http\Controllers\staff;

use App\Http\Controllers\Controller;
use App\Models\FinalInvestigationReport;
use App\Models\StationOfficer;
use App\Services\FinalInvestigationFirExporter;
use Illuminate\Http\Request;

class FinalInvestigationFirExportController extends Controller
{
    public function downloadWord(Request $request, int $finalReportId, FinalInvestigationFirExporter $exporter)
    {
        $report = $this->resolveAccessibleFinalReport($request, $finalReportId);
        $path = $exporter->buildDocx($report);

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

    private function resolveAccessibleFinalReport(Request $request, int $finalReportId): FinalInvestigationReport
    {
        $userId = $request->user()?->user_id;
        abort_unless($userId, 403);

        $stationOfficer = StationOfficer::query()
            ->where('user_id', $userId)
            ->firstOrFail();

        $stationOfficerIds = StationOfficer::query()
            ->where('station_id', $stationOfficer->station_id)
            ->pluck('station_officer_id');

        return FinalInvestigationReport::query()
            ->with([
                'progressInvestigation.spotInvestigation.incident.marker',
                'progressInvestigation.spotInvestigation.incident.barangay.city.province',
                'progressInvestigation.spotInvestigation.investigator.user',
                'progressInvestigation.fireOrigin',
                'investigator.user',
                'investigator.station.address.barangay.city.province',
                'notedBy.user',
                'approvedBy.user',
            ])
            ->where('final_investigation_report_id', $finalReportId)
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->firstOrFail();
    }
}
