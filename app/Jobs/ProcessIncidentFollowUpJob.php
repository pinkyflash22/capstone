<?php

namespace App\Jobs;

use App\Services\IncidentAutoAssignmentService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIncidentFollowUpJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $incidentId)
    {
    }

    public function handle(IncidentAutoAssignmentService $incidentAutoAssignmentService): void
    {
        try {
            $incidentAutoAssignmentService->assignSingleIncidentById($this->incidentId);
        } catch (\Throwable $exception) {
            Log::warning('Incident auto-assignment follow-up failed: ' . $exception->getMessage(), [
                'incident_id' => $this->incidentId,
            ]);

            throw $exception;
        }
    }
}
