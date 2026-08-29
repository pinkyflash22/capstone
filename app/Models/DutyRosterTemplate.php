<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutyRosterTemplate extends Model
{
    protected $primaryKey = 'duty_roster_template_id';

    protected $fillable = [
        'station_id', 'team_id', 'label', 'weekday', 'recurrence_type', 'weekdays', 'rotation_interval_days', 'rotation_group', 'starts_time', 'ends_time',
        'effective_from', 'effective_until', 'status', 'created_by', 'notes',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'weekdays' => 'array',
        'rotation_interval_days' => 'integer',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    public function assignments()
    {
        return $this->hasMany(DutyRosterTemplateAssignment::class, 'duty_roster_template_id', 'duty_roster_template_id');
    }

    public function shifts()
    {
        return $this->hasMany(DutyShift::class, 'duty_roster_template_id', 'duty_roster_template_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }
}
