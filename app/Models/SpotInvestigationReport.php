<?php

namespace App\Models;

use App\Models\Concerns\HasInvestigationProofImages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpotInvestigationReport extends Model
{
    use \App\Models\Concerns\ExcludesSpamIncidents;

    use HasFactory, HasInvestigationProofImages;
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
        'report_completed_at',
        'memorandum_for',
        'place_of_occurrence',
        'involved_structure',
        'establishment_name',
        'occupant',
        'highest_alarm_level',
        'affected_establishments_count',
        'description',
        'fatality_details',
        'details_of_investigation',
        'disposition',
        'noted_by_station_officer_id',
        'approved_by_station_officer_id',
        'review_status',
        'review_comments',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected $casts = [
        'fire_started' => 'datetime',
        'fire_out' => 'datetime',
        'report_completed_at' => 'datetime',
        'weather_context' => 'array',
        'reviewed_at' => 'datetime',
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
        return $this->hasOne(ProgressInvestigationReport::class, 'spot_investigation_id', 'spot_investigation_report_id')
            ->latestOfMany('progress_investigation_report_id');
    }

    public function progressInvestigations()
    {
        return $this->hasMany(ProgressInvestigationReport::class, 'spot_investigation_id', 'spot_investigation_report_id');
    }

    public function notedBy()
    {
        return $this->belongsTo(StationOfficer::class, 'noted_by_station_officer_id', 'station_officer_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(StationOfficer::class, 'approved_by_station_officer_id', 'station_officer_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id', 'user_id');
    }

    protected function investigationReportType(): string
    {
        return 'spot';
    }
}
