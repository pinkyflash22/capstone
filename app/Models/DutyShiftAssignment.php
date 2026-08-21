<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutyShiftAssignment extends Model
{
    protected $primaryKey = 'duty_shift_assignment_id';
    protected $fillable = ['duty_shift_id', 'station_officer_id', 'team_role'];
    public function officer() { return $this->belongsTo(StationOfficer::class, 'station_officer_id', 'station_officer_id'); }
    public function shift() { return $this->belongsTo(DutyShift::class, 'duty_shift_id', 'duty_shift_id'); }
}
