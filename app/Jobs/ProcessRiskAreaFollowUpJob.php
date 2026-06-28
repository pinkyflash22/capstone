<?php

namespace App\Jobs;

use App\Models\RiskArea;
use App\Support\BarangayNotificationService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRiskAreaFollowUpJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $riskAreaId)
    {
    }

    public function handle(): void
    {
        $riskArea = RiskArea::with(['barangayHall.barangay', 'reporter'])->find($this->riskAreaId);

        if (! $riskArea) {
            return;
        }

        try {
            BarangayNotificationService::notifyRiskAreaSubmitted($riskArea);
        } catch (\Throwable $exception) {
            Log::warning('Risk-area follow-up notification failed: ' . $exception->getMessage(), [
                'risk_area_id' => $this->riskAreaId,
            ]);

            throw $exception;
        }
    }
}
