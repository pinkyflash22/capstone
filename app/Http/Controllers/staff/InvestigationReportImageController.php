<?php

namespace App\Http\Controllers\staff;

use App\Http\Controllers\Controller;
use App\Models\FinalInvestigationReport;
use App\Models\InvestigationReportImage;
use App\Models\ProgressInvestigationReport;
use App\Models\SpotInvestigationReport;
use App\Models\StationOfficer;
use App\Support\MediaPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvestigationReportImageController extends Controller
{
    public function reorder(Request $request, string $reportType, int $reportId): JsonResponse
    {
        $validated = $request->validate([
            'image_ids' => ['required', 'array', 'max:100'],
            'image_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $report = $this->authorizedReport($request, $reportType, $reportId);
        $imageIds = array_map('intval', $validated['image_ids']);
        $storedIds = InvestigationReportImage::query()
            ->where('report_type', $reportType)
            ->where('report_id', $report->getKey())
            ->pluck('investigation_report_image_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $requestedIds = $imageIds;
        sort($requestedIds);
        sort($storedIds);
        abort_unless($requestedIds === $storedIds, 422, 'The proof-image order does not match this report.');

        DB::transaction(function () use ($imageIds, $reportType, $report): void {
            foreach ($imageIds as $index => $imageId) {
                InvestigationReportImage::query()
                    ->whereKey($imageId)
                    ->where('report_type', $reportType)
                    ->where('report_id', $report->getKey())
                    ->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Proof-image order saved.',
            'image_ids' => $imageIds,
        ]);
    }

    public function destroy(Request $request, string $reportType, int $reportId, int $imageId): JsonResponse
    {
        $report = $this->authorizedReport($request, $reportType, $reportId);

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

    private function authorizedReport(Request $request, string $reportType, int $reportId): Model
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

        return $reportModel::query()
            ->whereKey($reportId)
            ->whereHas('investigator', fn ($query) => $query->where('station_id', $stationId))
            ->firstOrFail();
    }
}
