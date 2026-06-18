<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BarangayPortalNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $category,
        protected string $title,
        protected string $message,
        protected ?string $url = null,
        protected string $icon = 'bi bi-bell-fill',
        protected array $meta = []
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => $this->category,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->icon,
            'meta' => $this->meta,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
