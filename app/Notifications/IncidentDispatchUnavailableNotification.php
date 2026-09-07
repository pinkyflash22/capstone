<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IncidentDispatchUnavailableNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $incidentId,
        private readonly int $stationId,
        private readonly ?int $barangayId,
        private readonly ?string $barangayName = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $place = $this->barangayName ? " in {$this->barangayName}" : '';

        return [
            'category' => 'dispatch_unavailable',
            'title' => 'Incident waiting for response resources',
            'message' => "Incident #{$this->incidentId}{$place} is inside your coverage, but no deployable team or required apparatus is currently available.",
            'url' => "/station-admin/maps?focus_incident_id={$this->incidentId}",
            'icon' => 'bi bi-exclamation-triangle-fill',
            'meta' => [
                'incident_id' => $this->incidentId,
                'station_id' => $this->stationId,
                'barangay_id' => $this->barangayId,
                'reason' => 'no_deployable_resources',
            ],
            'created_at' => now()->toIso8601String(),
        ];
    }
}
