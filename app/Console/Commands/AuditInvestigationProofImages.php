<?php

namespace App\Console\Commands;

use App\Models\FinalInvestigationReport;
use App\Models\InvestigationReportImage;
use App\Models\ProgressInvestigationReport;
use App\Models\SpotInvestigationReport;
use App\Support\MediaPath;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AuditInvestigationProofImages extends Command
{
    private const DELETE_CONFIRMATION = 'DELETE-ORPHANED-PROOF-IMAGES';

    protected $signature = 'reports:audit-proof-images
        {--delete : Delete confirmed orphaned database records}
        {--confirm= : Required confirmation phrase when using --delete}
        {--limit=100 : Maximum problem rows to display}';

    protected $description = 'Audit investigation-report proof images without changing report content or storage files';

    /** @var array<string, Filesystem> */
    private array $disks = [];

    public function handle(): int
    {
        $delete = (bool) $this->option('delete');
        if ($delete && $this->option('confirm') !== self::DELETE_CONFIRMATION) {
            $this->error('Cleanup was not started. Pass --confirm='.self::DELETE_CONFIRMATION.' with --delete.');

            return self::FAILURE;
        }

        $summary = [
            'checked' => 0,
            'healthy' => 0,
            'missing_file' => 0,
            'missing_path' => 0,
            'missing_report' => 0,
            'unknown_type' => 0,
            'unsupported_path' => 0,
            'storage_error' => 0,
            'deleted' => 0,
        ];
        $problems = [];
        $deletionCandidates = [];
        $displayLimit = max(1, min(1000, (int) $this->option('limit')));

        InvestigationReportImage::query()
            ->orderBy('investigation_report_image_id')
            ->chunkById(100, function ($images) use (&$summary, &$problems, &$deletionCandidates, $displayLimit): void {
                foreach ($images as $image) {
                    $summary['checked']++;
                    [$status, $detail, $safeToDelete] = $this->inspect($image);
                    $summary[$status]++;

                    if ($status !== 'healthy' && count($problems) < $displayLimit) {
                        $problems[] = [
                            $image->investigation_report_image_id,
                            $image->report_type,
                            $image->report_id,
                            $status,
                            $detail,
                        ];
                    }

                    if ($safeToDelete) {
                        $deletionCandidates[] = (int) $image->investigation_report_image_id;
                    }
                }
            }, 'investigation_report_image_id');

        if ($problems !== []) {
            $this->table(['Image ID', 'Report type', 'Report ID', 'Status', 'Details'], $problems);
        }

        if ($delete && $summary['storage_error'] > 0) {
            $this->error('Cleanup was blocked because storage errors occurred. No records were deleted.');

            return self::FAILURE;
        }

        if ($delete && $deletionCandidates !== []) {
            InvestigationReportImage::query()
                ->whereIn('investigation_report_image_id', $deletionCandidates)
                ->delete();
            $summary['deleted'] = count($deletionCandidates);
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], collect($summary)->map(fn ($count, $label) => [str_replace('_', ' ', $label), $count])->values()->all());

        if (! $delete && $deletionCandidates !== []) {
            $this->warn(count($deletionCandidates).' confirmed orphan(s) found. This was a dry run; nothing was deleted.');
            $this->line('After reviewing the results, cleanup requires: php artisan reports:audit-proof-images --delete --confirm='.self::DELETE_CONFIRMATION);
        } elseif ($delete) {
            $this->info($summary['deleted'].' orphaned database record(s) deleted. Report files and valid proof images were not changed.');
        } else {
            $this->info('Audit complete. No database records or files were changed.');
        }

        return $summary['storage_error'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @return array{string, string, bool} */
    private function inspect(InvestigationReportImage $image): array
    {
        $reportType = (string) $image->report_type;
        if (! in_array($reportType, ['spot', 'progress', 'final'], true)) {
            return ['unknown_type', 'The report type is not recognized and requires manual review.', false];
        }

        if (! $this->reportExists($reportType, (int) $image->report_id)) {
            return ['missing_report', 'The parent report no longer exists.', true];
        }

        $storedPath = trim((string) $image->file_path);
        if ($storedPath === '') {
            return ['missing_path', 'No storage path is recorded.', true];
        }

        if (str_starts_with($storedPath, 'http://') || str_starts_with($storedPath, 'https://')) {
            return ['unsupported_path', 'Absolute URLs are not deleted automatically.', false];
        }

        $diskName = MediaPath::diskFor($storedPath);
        $path = (string) MediaPath::stripPrefix($storedPath);

        try {
            $exists = $this->disk($diskName)->exists($path);
        } catch (Throwable $exception) {
            report($exception);

            return ['storage_error', $diskName.' storage could not be checked.', false];
        }

        return $exists
            ? ['healthy', 'Stored file is available.', false]
            : ['missing_file', $diskName.'://'.$path.' was not found.', true];
    }

    private function reportExists(string $type, int $reportId): bool
    {
        if ($reportId <= 0) {
            return false;
        }

        return match ($type) {
            'spot' => SpotInvestigationReport::query()->whereKey($reportId)->exists(),
            'progress' => ProgressInvestigationReport::query()->whereKey($reportId)->exists(),
            'final' => FinalInvestigationReport::query()->whereKey($reportId)->exists(),
            default => false,
        };
    }

    private function disk(string $name): Filesystem
    {
        if (isset($this->disks[$name])) {
            return $this->disks[$name];
        }

        $configuration = config("filesystems.disks.{$name}");
        if (! is_array($configuration)) {
            throw new \RuntimeException("The {$name} storage disk is not configured.");
        }

        // A dedicated throw-enabled adapter distinguishes a genuinely missing
        // object from credentials, network, or provider failures.
        $configuration['throw'] = true;

        return $this->disks[$name] = Storage::build($configuration);
    }
}
