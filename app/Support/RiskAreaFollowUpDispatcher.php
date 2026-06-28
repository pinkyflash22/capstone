<?php

namespace App\Support;

use App\Jobs\ProcessRiskAreaFollowUpJob;
use App\Models\RiskArea;

class RiskAreaFollowUpDispatcher
{
    public static function dispatchFor(?RiskArea $riskArea): void
    {
        if (! $riskArea?->risk_area_id) {
            return;
        }

        if (app()->runningInConsole() || app()->runningUnitTests()) {
            ProcessRiskAreaFollowUpJob::dispatchSync((int) $riskArea->risk_area_id);
            return;
        }

        ProcessRiskAreaFollowUpJob::dispatchAfterResponse((int) $riskArea->risk_area_id);
    }
}
