<?php

namespace App\Observers;

use App\Models\BarangayPredictionChecklist;
use App\Support\BarangayNotificationService;

class BarangayPredictionChecklistObserver
{
    public function created(BarangayPredictionChecklist $checklist): void
    {
        BarangayNotificationService::notifyChecklistPublished($checklist);
    }
}
