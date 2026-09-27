<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MitigationAction extends Model
{
    use HasFactory;

    protected $primaryKey = 'mitigation_action_id';

    protected $fillable = [
        'barangay_id',
        'scope_type',
        'scope_city_id',
        'owner_station_id',
        'risk_area_id',
        'barangay_prediction_checklist_id',
        'ai_action_plan_checklist_id',
        'responsible_team_id',
        'responsible_user_id',
        'title',
        'category',
        'priority_level',
        'status',
        'source_type',
        'target_date',
        'target_month',
        'completed_at',
        'completion_approval_status',
        'completion_requested_by',
        'completion_requested_at',
        'completion_decided_by',
        'completion_decided_at',
        'completion_decision_notes',
        'description',
        'baseline_condition',
        'expected_result',
        'remarks',
        'implementation_notes',
        'blocked_reason',
        'completion_notes',
        'outcome_result',
        'effectiveness',
        'verification_date',
        'verified_by',
        'follow_up_required',
        'follow_up_date',
        'follow_up_notes',
        'created_by',
        'reviewed_by',
        'approved_by',
        'approved_at',
        'reviewed_at',
        'analysis_revision',
        'is_superseded',
        'requires_revision_review',
        'is_manually_edited',
        'superseded_at',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'target_month' => 'date',
            'completed_at' => 'datetime',
            'completion_requested_at' => 'datetime',
            'completion_decided_at' => 'datetime',
            'approved_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'follow_up_required' => 'boolean',
            'verification_date' => 'date',
            'follow_up_date' => 'date',
            'analysis_revision' => 'integer',
            'is_superseded' => 'boolean',
            'requires_revision_review' => 'boolean',
            'is_manually_edited' => 'boolean',
            'superseded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('current_revision', function (Builder $builder) {
            $builder->where($builder->getModel()->qualifyColumn('is_superseded'), false);
        });
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }

    public function riskArea()
    {
        return $this->belongsTo(RiskArea::class, 'risk_area_id', 'risk_area_id');
    }

    public function checklist()
    {
        return $this->belongsTo(BarangayPredictionChecklist::class, 'barangay_prediction_checklist_id', 'barangay_prediction_checklist_id');
    }

    public function aiActionPlanChecklist()
    {
        return $this->belongsTo(AiActionPlanChecklist::class, 'ai_action_plan_checklist_id', 'ai_action_plan_checklist_id');
    }

    public function responsibleTeam()
    {
        return $this->belongsTo(Team::class, 'responsible_team_id', 'team_id');
    }

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id', 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by', 'user_id');
    }

    public function attachments()
    {
        return $this->hasMany(MitigationActionAttachment::class, 'mitigation_action_id', 'mitigation_action_id');
    }

    public function activities()
    {
        return $this->hasMany(MitigationActionActivity::class, 'mitigation_action_id', 'mitigation_action_id')->latest('created_at');
    }
}
