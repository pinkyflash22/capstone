<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiActionPlanChecklistItem extends Model
{
    use HasFactory;

    protected $table = 'ai_action_plan_checklist_items';
    protected $primaryKey = 'ai_action_plan_checklist_item_id';

    protected $fillable = [
        'ai_action_plan_checklist_id',
        'item_order',
        'item_text',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function checklist()
    {
        return $this->belongsTo(AiActionPlanChecklist::class, 'ai_action_plan_checklist_id', 'ai_action_plan_checklist_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }
}
