<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MitigationAction extends Model
{
    use HasFactory;

    protected $primaryKey = 'mitigation_action_id';

    protected $fillable = [
        'barangay_id',
        'risk_area_id',
        'barangay_prediction_checklist_id',
        'ai_action_plan_checklist_id',
        'title',
        'category',
        'priority_level',
        'status',
        'target_date',
        'completed_at',
        'description',
        'remarks',
        'created_by',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'completed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }

    public function attachments()
    {
        return $this->hasMany(MitigationActionAttachment::class, 'mitigation_action_id', 'mitigation_action_id');
    }
}
