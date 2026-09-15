<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class InvestigationReportReviewedNotification extends Notification
{
    public function __construct(
        private readonly int $reportId,
        private readonly string $reportKind,
        private readonly string $reportTitle,
        private readonly string $decision,
        private readonly ?string $comments,
        private readonly string $reviewerName,
    ) {
    }

    public static function fromReport(object $report, string $kind, string $decision, ?string $comments, string $reviewerName): self
    {
        $title = match ($kind) {
            'progress' => $report->report_stage_label ?? 'Progress Investigation Report',
            'final' => 'Final Investigation Report',
            default => 'Spot Investigation Report',
        };

        return new self((int) $report->getKey(), $kind, $title, $decision, $comments, $reviewerName);
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $approved = $this->decision === 'approved';
        $tab = match ($this->reportKind) {
            'progress' => 'progress',
            'final' => 'final',
            default => 'spot',
        };

        return [
            'category' => 'investigation_report',
            'title' => $approved ? 'Investigation report approved' : 'Investigation report rejected',
            'message' => $approved
                ? "{$this->reportTitle} #{$this->reportId} was approved by {$this->reviewerName}."
                : "{$this->reportTitle} #{$this->reportId} was rejected for revision.".($this->comments ? " {$this->comments}" : ''),
            'url' => route('firefighter.my-station', ['tab' => $tab]),
            'icon' => $approved ? 'fa-solid fa-circle-check' : 'fa-solid fa-pen-to-square',
            'meta' => [
                'report_id' => $this->reportId,
                'report_kind' => $this->reportKind,
                'decision' => $this->decision,
            ],
            'created_at' => now()->toIso8601String(),
        ];
    }
}
