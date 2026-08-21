<?php

namespace App\Services;

use App\Models\InvestigationReportImage;
use App\Support\MediaPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class InvestigationReportImageService
{
    public const MAX_IMAGE_KILOBYTES = 3072;

    public static function validationRules(): array
    {
        return [
            'proof_images' => ['nullable', 'array'],
            'proof_images.*' => ['file', 'image', 'mimes:jpg,jpeg,png', 'max:' . self::MAX_IMAGE_KILOBYTES],
        ];
    }

    public function store(Request $request, Model $report, string $reportType): void
    {
        $files = array_values(array_filter($request->file('proof_images', [])));
        if ($files === []) {
            return;
        }

        $nextOrder = (int) $report->proofImages()->max('sort_order') + 1;

        foreach ($files as $index => $file) {
            $storedPath = MediaPath::storeForDisk(
                $file->store("investigation-reports/{$reportType}/{$report->getKey()}", 'supabase'),
                'supabase'
            );

            $report->proofImages()->create([
                'report_type' => $reportType,
                'file_path' => $storedPath,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'file_size' => (int) $file->getSize(),
                'sort_order' => $nextOrder + $index,
            ]);
        }
    }

    public static function deleteForReport(Model $report): void
    {
        $report->loadMissing('proofImages');

        $report->proofImages->each(function (InvestigationReportImage $image) {
            MediaPath::delete($image->file_path);
            $image->delete();
        });
    }

    public static function responsePayload(Model $report): array
    {
        $report->loadMissing('proofImages');
        $payload = $report->toArray();
        $payload['proof_images'] = self::serialize($report->proofImages);

        return $payload;
    }

    public static function serialize(Collection $images): array
    {
        return $images->map(fn (InvestigationReportImage $image) => [
            'id' => $image->investigation_report_image_id,
            'name' => $image->file_name,
            'url' => MediaPath::url($image->file_path),
            'size' => (int) $image->file_size,
        ])->values()->all();
    }
}
