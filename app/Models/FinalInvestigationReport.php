<?php

namespace App\Models;

use App\Models\Concerns\HasInvestigationProofImages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\StationOfficer;

class FinalInvestigationReport extends Model
{
    use HasFactory, HasInvestigationProofImages;

    protected $table ='final_investigation_report';
    protected $primaryKey = 'final_investigation_report_id';
    protected $fillable = [
        'station_officer_id',
        'report_completed_at',
        'memorandum_for',
        'exact_address',
        'office_address',
        'alarm_at',
        'establishment_burned',
        'fire_victims',
        'origin_of_fire',
        'cause_of_fire',
        'substantiating_documents',
        'facts_of_case',
        'discussion',
        'findings',
        'recommendation',
        'noted_by_station_officer_id',
        'approved_by_station_officer_id',
        'progress_investigation_id',
        'total_damage',
        'review_status',
        'review_comments',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected $casts = [
        'report_completed_at' => 'datetime',
        'alarm_at' => 'datetime',
        'substantiating_documents' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function progressInvestigation()
    {
        return $this->belongsTo(ProgressInvestigationReport::class, 'progress_investigation_id', 'progress_investigation_report_id');
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

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id', 'user_id');
    }

    protected function investigationReportType(): string
    {
        return 'final';
    }
}
