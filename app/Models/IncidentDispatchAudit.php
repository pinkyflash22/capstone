<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Team;

class IncidentDispatchAudit extends Model
{
    protected $primaryKey = 'incident_dispatch_audit_id';

    protected $fillable = [
        'incident_id', 'actor_user_id', 'action', 'previous_team_id', 'assigned_team_id',
        'source_station_id', 'target_station_id', 'response_unit_id', 'reason', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'user_id');
    }

    public function previousTeam()
    {
        return $this->belongsTo(Team::class, 'previous_team_id', 'team_id');
    }

    public function assignedTeam()
    {
        return $this->belongsTo(Team::class, 'assigned_team_id', 'team_id');
    }
}
