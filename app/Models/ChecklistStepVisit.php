<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistStepVisit extends Model
{
    protected $primaryKey = 'checklist_step_visit_id';

    protected $fillable = [
        'ai_action_plan_checklist_id',
        'ai_action_plan_checklist_item_id',
        'station_id',
        'barangay_id',
        'firefighter_user_id',
        'verified_status',
        'visit_notes',
        'visited_at',
        'signoff_status',
        'barangay_signed_off_by',
        'signed_off_at',
        'signoff_notes',
    ];

    protected function casts(): array
    {
        return ['visited_at' => 'datetime', 'signed_off_at' => 'datetime'];
    }

    public function checklist()
    {
        return $this->belongsTo(AiActionPlanChecklist::class, 'ai_action_plan_checklist_id', 'ai_action_plan_checklist_id');
    }

    public function item()
    {
        return $this->belongsTo(AiActionPlanChecklistItem::class, 'ai_action_plan_checklist_item_id', 'ai_action_plan_checklist_item_id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }

    public function firefighter()
    {
        return $this->belongsTo(User::class, 'firefighter_user_id', 'user_id');
    }

    public function signatory()
    {
        return $this->belongsTo(User::class, 'barangay_signed_off_by', 'user_id');
    }

    public function photos()
    {
        return $this->hasMany(ChecklistStepVisitPhoto::class, 'checklist_step_visit_id', 'checklist_step_visit_id')->orderBy('sort_order');
    }
}
