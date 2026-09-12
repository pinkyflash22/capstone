<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IncidentAssignmentNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $incidentId,
        private readonly int $stationId,
        private readonly int $teamId,
        private readonly bool $isResponder = false
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $role = strtolower(trim((string) $notifiable?->role?->role));
        $commandUrl = $role === 'provincial admin'
            ? "/admin/maps?focus_incident_id={$this->incidentId}"
            : "/station-admin/maps?focus_incident_id={$this->incidentId}";

        return [
            'category' => 'incident_assignment',
            'title' => $this->isResponder ? 'New fire response assignment' : 'Response team assigned',
            'message' => $this->isResponder
                ? "Your team has been assigned to incident #{$this->incidentId}. Open the incident and acknowledge the dispatch."
                : "Team #{$this->teamId} has been assigned to incident #{$this->incidentId} and is awaiting a response.",
            'url' => $this->isResponder
                ? "/firefighter/incidents?focus_incident_id={$this->incidentId}"
                : $commandUrl,
            'icon' => 'bi bi-fire',
            'meta' => [
                'incident_id' => $this->incidentId,
                'station_id' => $this->stationId,
                'team_id' => $this->teamId,
                'recipient' => $this->isResponder ? 'responder' : 'station_command',
            ],
            'created_at' => now()->toIso8601String(),
        ];
    }
}
