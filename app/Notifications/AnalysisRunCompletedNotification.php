<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnalysisRunCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $runId,
        private readonly bool $successful,
        private readonly string $message,
        private readonly string $url
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! $this->successful
            && filled($notifiable->email ?? null)
            && config('services.ai_prediction.failure_email_enabled', true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('PyroSense forecast needs attention')
            ->greeting('Forecast run stopped')
            ->line($this->message)
            ->line('Your previous saved forecast was kept and remains available.')
            ->action('Review forecast', url($this->url))
            ->line('Run ID: '.$this->runId);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'analysis_run',
            'title' => $this->successful ? 'Forecast ready' : 'Forecast stopped',
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->successful ? 'fa-solid fa-chart-line' : 'fa-solid fa-triangle-exclamation',
            'meta' => [
                'run_id' => $this->runId,
                'status' => $this->successful ? 'completed' : 'failed',
            ],
            'created_at' => now()->toIso8601String(),
        ];
    }
}
