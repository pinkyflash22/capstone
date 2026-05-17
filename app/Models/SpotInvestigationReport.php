<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpotInvestigationReport extends Model
{
    use HasFactory;
    protected $table = 'spot_investigation_report';
    protected $primaryKey = 'spot_investigation_report_id';
    protected $fillable = [
        'incident_id',
        'dead',
        'injured',
        'owner',
        'fire_started',
        'fire_out',
        'estimated_damage',
        'occupancy',
        'estimated_area',
        'weather_context',
        'station_officer_id',

    ];

    protected $casts = [
        'fire_started' => 'datetime',
        'fire_out' => 'datetime',
        'weather_context' => 'array',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'incident_id');
    }

    // public function owner()
    // {
    //     return $this->belongsTo(User::class, 'owner_user_id', 'user_id');
    // }

    public function investigator()
    {
        return $this->belongsTo(StationOfficer::class, 'station_officer_id', 'station_officer_id');
    }

    public function progressInvestigation()
    {
        return $this->hasOne(ProgressInvestigationReport::class, 'spot_investigation_id', 'spot_investigation_report_id');
    }
}
