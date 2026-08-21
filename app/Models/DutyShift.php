<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutyShift extends Model
{
    protected $primaryKey = 'duty_shift_id';
    protected $fillable = ['station_id', 'team_id', 'duty_roster_template_id', 'occurrence_date', 'is_override', 'label', 'starts_at', 'ends_at', 'status', 'created_by', 'notes'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'occurrence_date' => 'date', 'is_override' => 'boolean'];

    public function assignments() { return $this->hasMany(DutyShiftAssignment::class, 'duty_shift_id', 'duty_shift_id'); }
    public function team() { return $this->belongsTo(Team::class, 'team_id', 'team_id'); }
    public function station() { return $this->belongsTo(Station::class, 'station_id', 'station_id'); }
    public function template() { return $this->belongsTo(DutyRosterTemplate::class, 'duty_roster_template_id', 'duty_roster_template_id'); }
}
