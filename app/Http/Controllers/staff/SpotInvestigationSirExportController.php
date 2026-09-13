<?php

namespace App\Http\Controllers\staff;

use App\Http\Controllers\Controller;
use App\Models\SpotInvestigationReport;
use App\Models\StationOfficer;
use App\Services\SpotInvestigationSirExporter;
use Illuminate\Http\Request;
use Throwable;

class SpotInvestigationSirExportController extends Controller
{
    public function downloadWord(Request $request, int $spotReportId, SpotInvestigationSirExporter $exporter)
    {
        $report = $this->resolveAccessibleSpotReport($request, $spotReportId);
        try {
            $path = $exporter->buildDocx($report);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The Spot Investigation Report Word file could not be generated. Please try again.');
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

    private function resolveAccessibleSpotReport(Request $request, int $spotReportId): SpotInvestigationReport
    {
        $userId = $request->user()?->user_id;
        abort_unless($userId, 403);

        if (strtolower(trim((string) ($request->user()?->role?->role ?? ''))) === 'provincial admin') {
            return SpotInvestigationReport::query()->with([
                'incident.marker', 'incident.barangay.city.province', 'incident.context', 'investigator.user',
                'investigator.station.address.barangay.city.province', 'notedBy.user', 'approvedBy.user',
            ])->findOrFail($spotReportId);
        }

        $stationOfficer = StationOfficer::query()
            ->where('user_id', $userId)
            ->firstOrFail();

        $stationOfficerIds = StationOfficer::query()
            ->where('station_id', $stationOfficer->station_id)
            ->pluck('station_officer_id');

        return SpotInvestigationReport::query()
            ->with([
                'incident.marker',
                'incident.barangay.city.province',
                'incident.context',
                'investigator.user',
                'investigator.station.address.barangay.city.province',
                'notedBy.user',
                'approvedBy.user',
            ])
            ->where('spot_investigation_report_id', $spotReportId)
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->firstOrFail();
    }
}
