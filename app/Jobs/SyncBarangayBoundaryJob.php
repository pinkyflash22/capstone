<?php

namespace App\Jobs;

use App\Exceptions\BoundaryUnavailableException;
use App\Services\GeoapifyBoundaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SyncBarangayBoundaryJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $uniqueFor = 3600;

    public function __construct(
        public int $barangayId,
        public ?int $coverageStationId = null,
        public string $geometry = 'geometry_1000',
        public bool $force = false,
    ) {
    }

    public function uniqueId(): string
    {
        return (string) $this->barangayId;
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(GeoapifyBoundaryService $service): void
    {
        try {
            $service->syncBarangayBoundary(
                $this->barangayId,
                $this->coverageStationId,
                null,
                $this->geometry,
                $this->force,
            );

            $this->updateCoverageSyncState('synced');
        } catch (BoundaryUnavailableException $exception) {
            $this->updateCoverageSyncState('unavailable', $exception->getMessage());
            return;
        } catch (\Throwable $exception) {
            $this->updateCoverageSyncState('failed', $exception->getMessage());
            throw $exception;
        }
    }

    protected function updateCoverageSyncState(string $status, ?string $error = null): void
    {
        DB::table('station_barangay_coverage')
            ->where('barangay_id', $this->barangayId)
            ->when($this->coverageStationId, fn ($query) => $query->where('station_id', $this->coverageStationId))
            ->update([
                'boundary_sync_status' => $status,
                'boundary_sync_attempted_at' => now(),
                'boundary_sync_error' => $error ? mb_substr($error, 0, 1000) : null,
                'updated_at' => now(),
            ]);
    }
}
