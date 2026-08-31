<?php

namespace App\Models;

use App\Models\Concerns\HasInvestigationProofImages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgressInvestigationReport extends Model
{
    use HasFactory, HasInvestigationProofImages;

    public const STAGE_SINGLE = 'single';
    public const STAGE_FIRST = 'first';
    public const STAGE_SECOND = 'second';

    public const STAGES = [
        self::STAGE_SINGLE,
        self::STAGE_FIRST,
        self::STAGE_SECOND,
    ];

    public const FINAL_ELIGIBLE_STAGES = [
        self::STAGE_SINGLE,
        self::STAGE_SECOND,
    ];

    protected $table = 'progress_investigation_report';
    protected $primaryKey = 'progress_investigation_report_id';
    protected $fillable = [
        'spot_investigation_id',
        'report_stage',
        'parent_progress_investigation_id',
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

    protected $appends = [
        'report_stage_label',
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

    public function parentProgressInvestigation()
    {
        return $this->belongsTo(self::class, 'parent_progress_investigation_id', 'progress_investigation_report_id');
    }

    public function secondProgressInvestigation()
    {
        return $this->hasOne(self::class, 'parent_progress_investigation_id', 'progress_investigation_report_id');
    }

    public function scopeEligibleForFinal($query)
    {
        return $query->whereIn('report_stage', self::FINAL_ELIGIBLE_STAGES);
    }

    public function getReportStageLabelAttribute(): string
    {
        return match ($this->report_stage) {
            self::STAGE_FIRST => '1st Progress Report',
            self::STAGE_SECOND => '2nd Progress Report',
            default => 'Progress Report',
        };
    }

    public function isEligibleForFinal(): bool
    {
        return in_array($this->report_stage, self::FINAL_ELIGIBLE_STAGES, true);
    }

    protected function investigationReportType(): string
    {
        return 'progress';
    }
}
