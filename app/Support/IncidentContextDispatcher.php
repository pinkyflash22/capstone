<?php

namespace App\Support;

use App\Jobs\EnrichIncidentContextJob;
use App\Models\Incident;

class IncidentContextDispatcher
{
    public static function dispatchFor(?Incident $incident): void
    {
        if (! $incident?->incident_id) {
            return;
        }

        if (app()->runningInConsole() || app()->runningUnitTests()) {
            EnrichIncidentContextJob::dispatchSync((int) $incident->incident_id);
            return;
        }

        EnrichIncidentContextJob::dispatchAfterResponse((int) $incident->incident_id);
    }
}
