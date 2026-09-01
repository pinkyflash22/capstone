<?php

namespace App\Notifications;

use App\Models\AfterActivityReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class AfterActivityReportReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        private readonly int $reportId,
        private readonly string $activityTitle,
        private readonly string $decision,
        private readonly ?string $comments,
        private readonly string $reviewerName,
    ) {
        $this->onConnection((string) config('services.aar_notifications.queue_connection', 'database'));
    }

    public static function fromReport(AfterActivityReport $report, string $decision, ?string $comments, string $reviewerName): self
    {
        return new self((int) $report->getKey(), (string) $report->activity_title, $decision, $comments, $reviewerName);
    }

    public function via(object $notifiable): array
    {
        return filled($notifiable->email ?? null) ? ['database', 'mail'] : ['database'];
    }

    public function viaQueues(): array
    {
        return ['database' => 'notifications', 'mail' => 'emails'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->decision === 'approved';

        return (new MailMessage)
            ->subject($approved ? 'Your AAR was approved' : 'Changes requested for your AAR')
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('Your After Activity Report for “'.$this->activityTitle.'” was '.($approved ? 'approved' : 'returned for changes').' by '.$this->reviewerName.'.')
            ->when(filled($this->comments), fn (MailMessage $mail) => $mail->line('Review comments: '.$this->comments))
            ->action('Open AAR records', route('firefighter.my-station', ['tab' => 'aar']))
            ->line('AAR reference: #'.$this->reportId);
    }

    public function toArray(object $notifiable): array
    {
        $approved = $this->decision === 'approved';

        return [
            'category' => 'after_activity_report',
            'title' => $approved ? 'AAR approved' : 'AAR changes requested',
            'message' => $approved
                ? 'Your AAR for “'.$this->activityTitle.'” was approved by '.$this->reviewerName.'.'
                : 'Your AAR for “'.$this->activityTitle.'” was returned for changes.'.($this->comments ? ' '.$this->comments : ''),
            'url' => route('firefighter.my-station', ['tab' => 'aar']),
            'icon' => $approved ? 'fa-solid fa-circle-check' : 'fa-solid fa-pen-to-square',
            'meta' => ['report_id' => $this->reportId, 'decision' => $this->decision],
            'created_at' => now()->toIso8601String(),
        ];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('AAR review notification permanently failed.', [
            'after_activity_report_id' => $this->reportId,
            'decision' => $this->decision,
            'exception' => $exception->getMessage(),
        ]);
    }
}
