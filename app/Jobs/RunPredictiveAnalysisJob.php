<?php

namespace App\Jobs;

use App\Http\Controllers\Chief\AnalyticController;
use App\Models\User;
use App\Notifications\AnalysisRunCompletedNotification;
use App\Support\AnalysisRunStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunPredictiveAnalysisJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 360;

    public bool $failOnTimeout = true;

    public function __construct(public string $runId, public int $requestedBy, public array $input)
    {
        // Reuse the durable forecast worker configured for production. The
        // application's default connection is intentionally "sync" there.
        $this->onConnection((string) config('services.ai_prediction.training_queue_connection', 'database'));
        $this->onQueue((string) config('services.ai_prediction.training_queue', 'forecast-training'));
    }

    public function handle(AnalyticController $controller): void
    {
        AnalysisRunStatus::put($this->runId, $this->requestedBy, 'running', 20, 'Collecting station evidence and building the forecast.');
        Auth::onceUsingId($this->requestedBy);

        $request = Request::create('/internal/forecast-analysis', 'POST', $this->input);
        $request->setUserResolver(fn () => Auth::user());
        $response = $controller->runPredictionApiSynchronously($request);
        $payload = $response->getData(true);

        if ($response->getStatusCode() >= 400 || ! ($payload['success'] ?? false)) {
            $message = $payload['message'] ?? 'Analysis failed.';
            AnalysisRunStatus::put($this->runId, $this->requestedBy, 'failed', 100, $payload['message'] ?? 'Analysis failed.', [
                'details' => $payload['details'] ?? null,
            ]);
            $this->notifyRequester(false, $message);

            return;
        }

        AnalysisRunStatus::put($this->runId, $this->requestedBy, 'completed', 100, 'Forecast, checklist drafts, and mitigation suggestions are ready.', [
            'result' => $payload,
        ]);
        $this->notifyRequester(true, 'Your forecast, checklist drafts, and mitigation suggestions are ready.');
    }

    public function failed(?Throwable $exception): void
    {
        AnalysisRunStatus::put($this->runId, $this->requestedBy, 'failed', 100, 'Analysis stopped unexpectedly. Your previous saved forecast was kept.', [
            'details' => $exception?->getMessage(),
        ]);
        $this->notifyRequester(false, 'Analysis stopped. Your previous saved forecast was kept.');
    }

    private function notifyRequester(bool $successful, string $message): void
    {
        try {
            $user = User::query()->find($this->requestedBy);
            if (! $user) {
                return;
            }

            $role = strtolower(trim((string) ($user->role?->role ?? '')));
            $url = $role === 'provincial admin' ? '/admin/analytics' : '/station-admin/analytics';

            $user->notify(new AnalysisRunCompletedNotification(
                $this->runId,
                $successful,
                $message,
                $url
            ));
        } catch (Throwable $exception) {
            // Notification storage must never cause a completed forecast to
            // retry or overwrite its successful run status.
            Log::warning('Unable to store analysis completion notification.', [
                'run_id' => $this->runId,
                'user_id' => $this->requestedBy,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
