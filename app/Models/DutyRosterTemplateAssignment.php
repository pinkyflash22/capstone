<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutyRosterTemplateAssignment extends Model
{
    protected $primaryKey = 'duty_roster_template_assignment_id';

    protected $fillable = ['duty_roster_template_id', 'station_officer_id', 'team_role'];

    public function template()
    {
        return $this->belongsTo(DutyRosterTemplate::class, 'duty_roster_template_id', 'duty_roster_template_id');
    }

    public function officer()
    {
        return $this->belongsTo(StationOfficer::class, 'station_officer_id', 'station_officer_id');
    }
}
