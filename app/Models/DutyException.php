<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutyException extends Model
{
    protected $primaryKey = 'duty_exception_id';
    protected $fillable = ['station_id', 'station_officer_id', 'type', 'starts_at', 'ends_at', 'reason', 'created_by'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    public function officer() { return $this->belongsTo(StationOfficer::class, 'station_officer_id', 'station_officer_id'); }
}
