<?php

namespace App\Observers;

use App\Models\Incident;
use App\Services\IncidentAutoAssignmentService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;

class IncidentObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Incident $incident): void
    {
        if ($incident->team_id) {
            return;
        }

        if (strtolower(trim((string) $incident->status)) !== 'reported') {
            return;
        }

        try {
            app(IncidentAutoAssignmentService::class)->assignSingleIncident($incident->loadMissing('marker'));
        } catch (\Throwable $e) {
            Log::error('Incident auto-assignment after create failed: ' . $e->getMessage(), [
                'incident_id' => $incident->incident_id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
