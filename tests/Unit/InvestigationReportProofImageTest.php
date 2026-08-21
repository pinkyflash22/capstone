<?php

namespace Tests\Unit;

use App\Models\InvestigationReportImage;
use App\Services\DocxProofImageAppender;
use App\Services\InvestigationReportImageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

class InvestigationReportProofImageTest extends TestCase
{
    #[Test]
    public function report_forms_and_server_rules_allow_multiple_three_mb_images(): void
    {
        $view = file_get_contents(resource_path('views/firefighters/mystation.blade.php'));
        $script = file_get_contents(resource_path('js/firefighter/station-shell.js'));
        $rules = InvestigationReportImageService::validationRules();

        $this->assertGreaterThanOrEqual(3, substr_count($view, 'name="proof_images[]"'));
        $this->assertStringContainsString('multiple', $view);
        $this->assertStringContainsString('name="proof_images[]"', $script);
        $this->assertContains('max:3072', $rules['proof_images.*']);
    }

    #[Test]
    public function proof_images_have_previews_and_station_protected_individual_deletion(): void
    {
        $script = file_get_contents(resource_path('js/firefighter/station-shell.js'));
        $styles = file_get_contents(resource_path('css/firefighters/station-shell.css'));
        $route = Route::getRoutes()->getByName('firefighter.investigation-reports.proof-images.destroy');

        $this->assertStringContainsString('URL.createObjectURL(file)', $script);
        $this->assertStringContainsString('new DataTransfer()', $script);
        $this->assertStringContainsString('data-delete-proof-image', $script);
        $this->assertStringContainsString("method: 'DELETE'", $script);
        $this->assertStringContainsString('.proof-image-grid', $styles);
        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('role:firefighter', $route->gatherMiddleware());
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

        $zip->close();
        @unlink($path);
    }
}
