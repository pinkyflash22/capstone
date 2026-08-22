<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentDispatchAudit extends Model
{
    protected $primaryKey = 'incident_dispatch_audit_id';

    protected $fillable = [
        'incident_id', 'actor_user_id', 'action', 'previous_team_id', 'assigned_team_id',
        'source_station_id', 'target_station_id', 'response_unit_id', 'reason', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];
}
