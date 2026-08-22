<?php

namespace App\Services;

use App\Models\InvestigationReportImage;
use App\Support\MediaPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class InvestigationReportImageService
{
    public const MAX_TOTAL_IMAGE_KILOBYTES = 25600;

    public function cachedSubmissionResponse(Request $request, string $operation): ?array
    {
        $key = $this->submissionCacheKey($request, $operation);
        $cached = $key ? Cache::get($key) : null;

        return is_array($cached) ? $cached : null;
    }

    public function rememberSubmissionResponse(Request $request, string $operation, array $payload): array
    {
        $key = $this->submissionCacheKey($request, $operation);
        if ($key) {
            Cache::put($key, $payload, now()->addMinutes(15));
        }

        return $payload;
    }

    public static function validationRules(): array
    {
        return [
            'proof_images' => [
                'nullable',
                'array',
                'max:20',
                function (string $attribute, mixed $files, \Closure $fail): void {
                    $totalBytes = collect(is_array($files) ? $files : [])
                        ->sum(fn ($file) => is_object($file) && method_exists($file, 'getSize') ? (int) $file->getSize() : 0);

                    if ($totalBytes > self::MAX_TOTAL_IMAGE_KILOBYTES * 1024) {
                        $fail('The uploaded proof images exceed the 25 MB limit. Remove or replace images before submitting the report.');
                    }
                },
            ],
            'proof_images.*' => ['file', 'image', 'mimes:jpg,jpeg,png', 'max:' . self::MAX_TOTAL_IMAGE_KILOBYTES],
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

    private function submissionCacheKey(Request $request, string $operation): ?string
    {
        $requestId = trim((string) $request->header('X-Upload-Request-ID'));
        if (! preg_match('/^[A-Za-z0-9-]{16,100}$/', $requestId)) {
            return null;
        }

        $userId = (string) ($request->user()?->user_id ?? 'guest');

        return 'investigation-report-upload:'.hash('sha256', "{$userId}|{$operation}|{$requestId}");
    }
}
