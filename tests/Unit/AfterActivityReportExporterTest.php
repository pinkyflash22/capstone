<?php

namespace Tests\Unit;

use App\Models\AfterActivityReport;
use App\Models\Station;
use App\Models\StationOfficer;
use App\Models\User;
use App\Services\AfterActivityReportExporter;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use ZipArchive;

class AfterActivityReportExporterTest extends TestCase
{
    public function test_exporter_fills_the_supplied_bfp_aar_template(): void
    {
        $preparedUser = new User(['first_name' => 'Juan', 'last_name' => 'Dela Cruz']);
        $prepared = new StationOfficer();
        $prepared->setRelation('user', $preparedUser);

        $approvedUser = new User(['first_name' => 'Maria', 'last_name' => 'Santos']);
        $approved = new StationOfficer();
        $approved->setRelation('user', $approvedUser);

        $report = new AfterActivityReport([
            'activity_title' => 'Community Rescue Operation',
            'activity_date' => '2026-08-30',
            'memorandum_for' => 'City Fire Marshal',
            'references' => 'Mission Order 2026-10',
            'background' => 'A rescue was required after heavy rainfall.',
            'significance' => 'The operation protected residents.',
            'activity_details' => 'BFP personnel evacuated three families safely.',
            'participants' => ['FO1 Juan Dela Cruz — Team Leader', 'Barangay volunteers'],
            'deployed_resources' => ['Engine 1 — 1 unit'],
            'issues_challenges' => 'Flooded access road.',
            'discussion_assessment' => 'The response was timely and coordinated.',
            'recommendations' => 'Pre-position rescue equipment.',
            'report_completed_at' => '2026-08-31 09:00:00',
            'review_status' => 'approved',
            'approval_signer_name' => 'Maria Santos',
            'approval_signature_hash' => str_repeat('a', 64),
        ]);
        $report->after_activity_report_id = 99;
        $report->created_at = Carbon::parse('2026-08-31 09:00:00');
        $report->setRelation('station', new Station(['station_name' => 'Central Fire Station']));
        $report->setRelation('preparedBy', $prepared);
        $report->setRelation('approvedBy', $approved);
        $report->setRelation('proofImages', collect());

        $exporter = app(AfterActivityReportExporter::class);
        $path = $exporter->buildDocx($report);

        try {
            $archive = new ZipArchive();
            $this->assertTrue($archive->open($path) === true);
            $xml = (string) $archive->getFromName('word/document.xml');
            $archive->close();

            $this->assertStringContainsString('AFTER ACTIVITY REPORT ON 2026 Community Rescue Operation', $xml);
            $this->assertStringContainsString('BFP personnel evacuated three families safely.', $xml);
            $this->assertStringContainsString('Barangay volunteers', $xml);
            $this->assertStringContainsString('Engine 1', $xml);
            $this->assertStringContainsString('JUAN DELA CRUZ', $xml);
            $this->assertStringContainsString('MARIA SANTOS', $xml);
            $this->assertStringContainsString('No pictures attached.', $xml);
        } finally {
            @unlink($path);
        }
    }
}
