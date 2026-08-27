<?php

namespace Tests\Unit;

use App\Models\InvestigationReportImage;
use App\Models\SpotInvestigationReport;
use App\Services\DocxProofImageAppender;
use App\Services\InvestigationReportImageService;
use App\Services\SpotInvestigationSirExporter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

class InvestigationReportProofImageTest extends TestCase
{
    #[Test]
    public function production_word_templates_are_present_and_not_gitignored(): void
    {
        $templates = [
            'sir-template-follow.docx',
            'progress-investigation-template-edited.docx',
            'final-investigation-template-edited.docx',
        ];

        foreach ($templates as $template) {
            $path = storage_path('app/private/templates/'.$template);

            $this->assertFileExists($path, $template.' must be included in deployments.');
            $this->assertGreaterThan(0, filesize($path), $template.' must not be empty.');

            $archive = new ZipArchive();
            $this->assertTrue($archive->open($path) === true, $template.' must be a valid DOCX archive.');
            $this->assertNotFalse($archive->getFromName('word/document.xml'), $template.' must contain its Word document XML.');
            $archive->close();
        }

        $privateStorageIgnore = file_get_contents(storage_path('app/private/.gitignore'));
        foreach ($templates as $template) {
            $this->assertStringContainsString('!templates/'.$template, $privateStorageIgnore);
        }

        $dockerfile = file_get_contents(base_path('Dockerfile'));
        foreach ($templates as $template) {
            $this->assertStringContainsString('storage/app/private/templates/'.$template, $dockerfile);
        }

        foreach ([
            'SpotInvestigationSirExportController.php',
            'ProgressInvestigationPirExportController.php',
            'FinalInvestigationFirExportController.php',
        ] as $controller) {
            $source = file_get_contents(app_path('Http/Controllers/staff/'.$controller));
            $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $source);
            $this->assertStringContainsString("'X-Content-Type-Options' => 'nosniff'", $source);
        }
    }

    #[Test]
    public function report_forms_and_server_rules_allow_multiple_images_up_to_twenty_five_mb_combined(): void
    {
        $view = file_get_contents(resource_path('views/firefighters/mystation.blade.php'));
        $script = file_get_contents(resource_path('js/firefighter/station-shell.js'));
        $rules = InvestigationReportImageService::validationRules();

        $this->assertGreaterThanOrEqual(3, substr_count($view, 'name="proof_images[]"'));
        $this->assertStringContainsString('multiple', $view);
        $this->assertStringContainsString('name="proof_images[]"', $script);
        $this->assertContains('max:25600', $rules['proof_images.*']);
        $this->assertContains('max:20', $rules['proof_images']);

        $valid = Validator::make(['proof_images' => [
            UploadedFile::fake()->image('scene-1.jpg')->size(10000),
            UploadedFile::fake()->image('scene-2.jpg')->size(10000),
        ]], $rules);
        $tooLarge = Validator::make(['proof_images' => [
            UploadedFile::fake()->image('scene-1.jpg')->size(13000),
            UploadedFile::fake()->image('scene-2.jpg')->size(13000),
        ]], $rules);

        $this->assertFalse($valid->fails());
        $this->assertTrue($tooLarge->fails());
    }

    #[Test]
    public function proof_images_have_previews_and_station_protected_individual_deletion(): void
    {
        $view = file_get_contents(resource_path('views/firefighters/mystation.blade.php'));
        $script = file_get_contents(resource_path('js/firefighter/station-shell.js'));
        $styles = file_get_contents(resource_path('css/firefighters/station-shell.css'));
        $controller = file_get_contents(app_path('Http/Controllers/staff/FirefighterController.php'));
        $route = Route::getRoutes()->getByName('firefighter.investigation-reports.proof-images.destroy');

        $this->assertStringContainsString('URL.createObjectURL(file)', $script);
        $this->assertStringContainsString('new DataTransfer()', $script);
        $this->assertStringContainsString('MAX_PROOF_UPLOAD_BYTES = 25 * 1024 * 1024', $script);
        $this->assertStringContainsString("xhr.upload.addEventListener('progress'", $script);
        $this->assertStringContainsString('exceed the 25 MB threshold', $script);
        $this->assertStringContainsString('data-delete-proof-image', $script);
        $this->assertStringContainsString("method: 'DELETE'", $script);
        $this->assertStringContainsString('.proof-image-grid', $styles);
        $this->assertStringContainsString('proofImageDeleteTemplate: @json($proofImageDeleteTemplate)', $view);
        $this->assertStringNotContainsString("proofImageDeleteTemplate: @json(route('firefighter.investigation-reports.proof-images.destroy', [", $view);
        $this->assertStringContainsString("'proofImageDeleteTemplate' => route('firefighter.investigation-reports.proof-images.destroy'", $controller);
        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('role:firefighter', $route->gatherMiddleware());
    }

    #[Test]
    public function proof_images_are_compressed_retried_and_saved_in_document_order(): void
    {
        $view = file_get_contents(resource_path('views/firefighters/mystation.blade.php'));
        $script = file_get_contents(resource_path('js/firefighter/station-shell.js'));
        $styles = file_get_contents(resource_path('css/firefighters/station-shell.css'));
        $imageController = file_get_contents(app_path('Http/Controllers/staff/InvestigationReportImageController.php'));
        $relation = file_get_contents(app_path('Models/Concerns/HasInvestigationProofImages.php'));
        $route = Route::getRoutes()->getByName('firefighter.investigation-reports.proof-images.order');

        $this->assertStringContainsString('createImageBitmap(file)', $script);
        $this->assertStringContainsString('const maximumDimension = 2400', $script);
        $this->assertStringContainsString('Connection interrupted', $script);
        $this->assertStringContainsString("xhr.setRequestHeader('X-Upload-Request-ID'", $script);
        $this->assertStringContainsString('data-existing-proof-image', $script);
        $this->assertStringContainsString('persistExistingProofImageOrder', $script);
        $this->assertStringContainsString('.proof-image-drag', $styles);
        $this->assertStringContainsString('proofImageOrderTemplate: @json($proofImageOrderTemplate)', $view);
        $this->assertStringContainsString("'sort_order' => \$index + 1", $imageController);
        $this->assertStringContainsString("->orderBy('sort_order')", $relation);
        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('role:firefighter', $route->gatherMiddleware());

        Cache::flush();
        $request = Request::create('/upload', 'POST');
        $request->headers->set('X-Upload-Request-ID', '12345678-1234-1234-1234-123456789012');
        $request->setUserResolver(fn () => (object) ['user_id' => 17]);
        $service = app(InvestigationReportImageService::class);
        $payload = ['success' => true, 'data' => ['id' => 91]];

        $this->assertNull($service->cachedSubmissionResponse($request, 'spot:create'));
        $service->rememberSubmissionResponse($request, 'spot:create', $payload);
        $this->assertSame($payload, $service->cachedSubmissionResponse($request, 'spot:create'));
    }

    #[Test]
    public function a_submission_still_succeeds_when_the_optional_response_cache_is_unavailable(): void
    {
        $request = Request::create('/upload', 'POST');
        $request->headers->set('X-Upload-Request-ID', '12345678-1234-1234-1234-123456789012');
        $request->setUserResolver(fn () => (object) ['user_id' => 17]);
        $payload = ['success' => true, 'data' => ['id' => 91]];

        Cache::shouldReceive('get')->once()->andThrow(new \RuntimeException('Cache unavailable'));
        Cache::shouldReceive('put')->once()->andThrow(new \RuntimeException('Cache unavailable'));

        $service = app(InvestigationReportImageService::class);

        $this->assertNull($service->cachedSubmissionResponse($request, 'spot:create'));
        $this->assertSame($payload, $service->rememberSubmissionResponse($request, 'spot:create', $payload));

        Cache::swap(new Repository(new ArrayStore()));
    }

    #[Test]
    public function spot_report_uses_submission_time_alarm_options_and_compact_help(): void
    {
        $view = file_get_contents(resource_path('views/firefighters/mystation.blade.php'));
        $script = file_get_contents(resource_path('js/firefighter/station-shell.js'));
        $controller = file_get_contents(app_path('Http/Controllers/SpotInvestigationReportController.php'));

        $this->assertStringContainsString('Recorded automatically when this report is submitted.', $view);
        $this->assertStringContainsString('report-form-help__tooltip', $view);
        $this->assertStringContainsString('<select name="highest_alarm_level"', $view);
        $this->assertStringContainsString('<option value="5th Alarm">5th Alarm</option>', $view);
        $this->assertStringContainsString('narrative-action-row', $view);
        $this->assertStringContainsString("\$validated['report_completed_at'] = now();", $controller);
        $this->assertStringContainsString("data.report_completed_at || new Date().toISOString()", $script);
    }

    #[Test]
    public function docx_appender_starts_photo_documentation_on_a_new_page(): void
    {
        Storage::fake('supabase');
        $imageBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        Storage::disk('supabase')->put('investigation-reports/spot/1/proof.png', $imageBytes);

        $image = new InvestigationReportImage([
            'file_path' => 'supabase://investigation-reports/spot/1/proof.png',
            'file_name' => 'Scene proof.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($imageBytes),
            'sort_order' => 1,
        ]);
        $image->setAttribute('investigation_report_image_id', 91);

        $path = tempnam(sys_get_temp_dir(), 'proof_docx_') . '.docx';
        copy(storage_path('app/private/templates/sir-template-follow.docx'), $path);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        app(DocxProofImageAppender::class)->append($zip, collect([$image]));
        $zip->close();

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $documentXml = $zip->getFromName('word/document.xml');
        $relationshipsXml = $zip->getFromName('word/_rels/document.xml.rels');

        $this->assertStringContainsString('<w:pageBreakBefore/>', $documentXml);
        $this->assertStringContainsString('PHOTO DOCUMENTATION', $documentXml);
        $this->assertStringContainsString('Scene proof.png', $documentXml);
        $this->assertStringContainsString('rIdProofImage91_1', $relationshipsXml);
        $this->assertNotFalse($zip->locateName('word/media/proof-image-91-1.png'));
        $this->assertTrue((new \DOMDocument())->loadXML($documentXml));
        $this->assertTrue((new \DOMDocument())->loadXML($relationshipsXml));

        $zip->close();
        @unlink($path);
    }

    #[Test]
    public function a_missing_proof_image_does_not_corrupt_or_abort_the_word_report(): void
    {
        Storage::fake('supabase');

        $image = new InvestigationReportImage([
            'file_path' => 'supabase://investigation-reports/spot/1/missing.png',
            'file_name' => 'Missing scene proof.png',
            'mime_type' => 'image/png',
            'file_size' => 2048,
            'sort_order' => 1,
        ]);
        $image->setAttribute('investigation_report_image_id', 92);

        $path = tempnam(sys_get_temp_dir(), 'missing_proof_docx_') . '.docx';
        copy(storage_path('app/private/templates/sir-template-follow.docx'), $path);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        app(DocxProofImageAppender::class)->append($zip, collect([$image]));
        $this->assertTrue($zip->close());

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path, ZipArchive::CHECKCONS) === true);
        $documentXml = $zip->getFromName('word/document.xml');

        $this->assertIsString($documentXml);
        $this->assertStringContainsString('Proof image 1 could not be embedded: Missing scene proof.png', $documentXml);
        $this->assertTrue((new \DOMDocument())->loadXML($documentXml));

        $zip->close();
        @unlink($path);
    }

    #[Test]
    public function the_complete_spot_export_with_a_proof_image_is_a_consistent_docx_package(): void
    {
        Storage::fake('supabase');
        $imageBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        Storage::disk('supabase')->put('investigation-reports/spot/44/proof.png', $imageBytes);

        $image = new InvestigationReportImage([
            'file_path' => 'supabase://investigation-reports/spot/44/proof.png',
            'file_name' => 'Complete export proof.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($imageBytes),
            'sort_order' => 1,
        ]);
        $image->setAttribute('investigation_report_image_id', 93);

        $report = new SpotInvestigationReport([
            'incident_id' => 12,
            'report_completed_at' => now(),
            'details_of_investigation' => 'Initial findings were documented.',
            'disposition' => 'Report retained for follow-up.',
        ]);
        $report->setAttribute('spot_investigation_report_id', 44);
        $report->setRelation('incident', null);
        $report->setRelation('investigator', null);
        $report->setRelation('notedBy', null);
        $report->setRelation('approvedBy', null);
        $report->setRelation('proofImages', collect([$image]));

        $path = app(SpotInvestigationSirExporter::class)->buildDocx($report);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path, ZipArchive::CHECKCONS) === true);
        $documentXml = $zip->getFromName('word/document.xml');
        $relationshipsXml = $zip->getFromName('word/_rels/document.xml.rels');
        $this->assertIsString($documentXml);
        $this->assertIsString($relationshipsXml);
        $this->assertTrue((new \DOMDocument())->loadXML($documentXml));
        $this->assertTrue((new \DOMDocument())->loadXML($relationshipsXml));
        $this->assertStringContainsString('Complete export proof.png', $documentXml);
        $this->assertStringContainsString('rIdProofImage93_1', $relationshipsXml);
        $this->assertNotFalse($zip->locateName('word/media/proof-image-93-1.png'));

        $zip->close();
        @unlink($path);
    }
}
