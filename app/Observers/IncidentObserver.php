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

    public function updated(Incident $incident): void
    {
        if (! $incident->wasChanged('status')) {
            return;
        }

        if (! in_array(strtolower(trim((string) $incident->status)), ['controlled', 'closed', 'completed'], true)) {
            return;
        }

        try {
            app(\App\Services\IncidentResourceDispatchService::class)->releaseIncidentUnits($incident);
            app(IncidentAutoAssignmentService::class)->assignRecentUnassignedIncidents(24 * 60);
        } catch (\Throwable $e) {
            Log::error('Incident resource release after closure failed: ' . $e->getMessage(), [
                'incident_id' => $incident->incident_id,
            ]);
        }
    }
}
