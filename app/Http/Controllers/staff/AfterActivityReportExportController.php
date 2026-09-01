<?php

namespace App\Http\Controllers\staff;

use App\Http\Controllers\Controller;
use App\Models\AfterActivityReport;
use App\Models\StationOfficer;
use App\Services\AfterActivityReportExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Support\Facades\URL;

class AfterActivityReportExportController extends Controller
{
    public function downloadWord(Request $request, AfterActivityReport $afterActivityReport, AfterActivityReportExporter $exporter)
    {
        $stationId = StationOfficer::query()
            ->where('user_id', $request->user()?->user_id)
            ->value('station_id');
        abort_unless($stationId && (int) $afterActivityReport->station_id === (int) $stationId, 404);

        $afterActivityReport->load([
            'station.address.barangay.city.province',
            'preparedBy.user',
            'approvedBy.user',
            'proofImages',
        ]);

        try {
            $path = $exporter->buildDocx($afterActivityReport);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The After Activity Report Word file could not be generated.');
        }

        return response()->download($path, $exporter->buildFilename($afterActivityReport), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ])->deleteFileAfterSend(true);
    }

    public function downloadPdf(Request $request, AfterActivityReport $afterActivityReport)
    {
        $this->authorizeAccess($request, $afterActivityReport);
        $afterActivityReport->load(['station.address.barangay.city.province', 'preparedBy.user', 'approvedBy.user', 'reviewedBy', 'proofImages', 'reviewHistory.reviewer']);
        $filename = preg_replace('/\.docx$/', '.pdf', app(AfterActivityReportExporter::class)->buildFilename($afterActivityReport));
        $verificationUrl = null;
        $verificationQr = null;
        if ($afterActivityReport->isApproved()) {
            $verificationUrl = URL::signedRoute('after-activity-reports.verify', [
                'afterActivityReport' => $afterActivityReport,
                'approval' => $afterActivityReport->approval_signature_hash,
            ]);
            $verificationQr = (new SvgWriter())->write(new QrCode(
                data: $verificationUrl,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 220,
                margin: 8,
            ))->getDataUri();
        }

        return Pdf::loadView('firefighters.after-activity-report-pdf', [
            'report' => $afterActivityReport,
            'verificationUrl' => $verificationUrl,
            'verificationQr' => $verificationQr,
        ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function verify(Request $request, AfterActivityReport $afterActivityReport)
    {
        $provided = (string) $request->query('approval', '');
        $matchesRecord = filled($afterActivityReport->approval_signature_hash)
            && hash_equals((string) $afterActivityReport->approval_signature_hash, $provided);
        $valid = $matchesRecord && $afterActivityReport->approvalSignatureIsValid();

        $afterActivityReport->load(['station', 'preparedBy.user', 'reviewedBy']);

        return response()->view('firefighters.after-activity-report-verification', [
            'report' => $afterActivityReport,
            'valid' => $valid,
        ], $valid ? 200 : 422);
    }

    private function authorizeAccess(Request $request, AfterActivityReport $report): void
    {
        $role = strtolower((string) $request->user()?->role?->role);
        if ($role === 'provincial admin') {
            return;
        }
        $stationId = StationOfficer::query()->where('user_id', $request->user()?->user_id)->value('station_id');
        abort_unless($stationId && (int) $report->station_id === (int) $stationId, 404);
    }
}
