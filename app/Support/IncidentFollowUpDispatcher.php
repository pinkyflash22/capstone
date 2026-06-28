<?php

namespace App\Support;

use App\Jobs\ProcessIncidentFollowUpJob;
use App\Models\Incident;

class IncidentFollowUpDispatcher
{
    public static function dispatchFor(?Incident $incident): void
    {
        if (! $incident?->incident_id) {
            return;
        }

        if (app()->runningInConsole() || app()->runningUnitTests()) {
            ProcessIncidentFollowUpJob::dispatchSync((int) $incident->incident_id);
            return;
        }

        ProcessIncidentFollowUpJob::dispatchAfterResponse((int) $incident->incident_id);
    }
}
