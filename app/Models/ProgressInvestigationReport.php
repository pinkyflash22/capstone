<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgressInvestigationReport extends Model
{
    use HasFactory;

    protected $table = 'progress_investigation_report';
    protected $primaryKey = 'progress_investigation_report_id';
    protected $fillable = [
        'spot_investigation_id',
        'fire_origin_id',
        'responsible_arson',
        'fire_cause',
        'station_officer_id',
        'report_completed_at',
        'memorandum_for',
        'mission_order_number',
        'mission_order_date',
        'facts_of_case',
        'disposition',
        'noted_by_station_officer_id',
        'approved_by_station_officer_id',
    ];

    protected $casts = [
        'report_completed_at' => 'datetime',
        'mission_order_date' => 'date',
    ];

    public function spotInvestigation()
    {
        return $this->belongsTo(SpotInvestigationReport::class, 'spot_investigation_id', 'spot_investigation_report_id');
    }

    public function fireOrigin()
    {
        return $this->belongsTo(FireOrigin::class, 'fire_origin_id', 'fire_origin_id');
    }

    public function investigator()
    {
        return $this->belongsTo(StationOfficer::class, 'station_officer_id', 'station_officer_id');
    }

    public function notedBy()
    {
        return $this->belongsTo(StationOfficer::class, 'noted_by_station_officer_id', 'station_officer_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(StationOfficer::class, 'approved_by_station_officer_id', 'station_officer_id');
    }

    public function finalInvestigation()
    {
        return $this->hasOne(FinalInvestigationReport::class, 'progress_investigation_id', 'progress_investigation_report_id');
    }
}
