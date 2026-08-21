<?php

namespace App\Http\Controllers\staff;

use App\Http\Controllers\Controller;
use App\Models\FinalInvestigationReport;
use App\Models\InvestigationReportImage;
use App\Models\ProgressInvestigationReport;
use App\Models\SpotInvestigationReport;
use App\Models\StationOfficer;
use App\Support\MediaPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestigationReportImageController extends Controller
{
    public function destroy(Request $request, string $reportType, int $reportId, int $imageId): JsonResponse
    {
        $reportModel = match ($reportType) {
            'spot' => SpotInvestigationReport::class,
            'progress' => ProgressInvestigationReport::class,
            'final' => FinalInvestigationReport::class,
            default => null,
        };

        abort_unless($reportModel, 404);

        $stationId = StationOfficer::query()
            ->where('user_id', $request->user()?->user_id)
            ->value('station_id');

        abort_unless($stationId, 403);

        $report = $reportModel::query()
            ->whereKey($reportId)
            ->whereHas('investigator', fn ($query) => $query->where('station_id', $stationId))
            ->firstOrFail();

        $image = InvestigationReportImage::query()
            ->whereKey($imageId)
            ->where('report_type', $reportType)
            ->where('report_id', $report->getKey())
            ->firstOrFail();

        MediaPath::delete($image->file_path);
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Proof image deleted successfully.',
            'image_id' => $imageId,
        ]);
    }
}
