<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiActionPlanChecklistActivity extends Model
{
    protected $table = 'ai_action_plan_checklist_activities';
    protected $primaryKey = 'ai_action_plan_checklist_activity_id';
    public $timestamps = false;

    protected $fillable = [
        'ai_action_plan_checklist_id',
        'ai_action_plan_checklist_item_id',
        'user_id',
        'event_type',
        'old_value',
        'new_value',
        'notes',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function checklist()
    {
        return $this->belongsTo(AiActionPlanChecklist::class, 'ai_action_plan_checklist_id', 'ai_action_plan_checklist_id');
    }
}
