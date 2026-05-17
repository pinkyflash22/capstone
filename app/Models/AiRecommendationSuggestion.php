<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiRecommendationSuggestion extends Model
{
    use HasFactory;

    protected $table = 'ai_recommendation_suggestions';
    protected $primaryKey = 'ai_recommendation_suggestion_id';

    protected $fillable = [
        'analysis_id',
        'ai_action_plan_checklist_id',
        'station_id',
        'barangay_id',
        'recommendation_title',
        'recommendation_description',
        'priority_level',
        'request_context',
        'suggestions',
        'steps',
        'resources_needed',
        'estimated_timeline',
        'ai_response',
        'is_ai_generated',
    ];

    protected function casts(): array
    {
        return [
            'request_context' => 'array',
            'suggestions' => 'array',
            'steps' => 'array',
            'resources_needed' => 'array',
            'is_ai_generated' => 'boolean',
        ];
    }

    public function analysis()
    {
        return $this->belongsTo(Analysis::class, 'analysis_id', 'analysis_id');
    }

    public function actionPlanChecklist()
    {
        return $this->belongsTo(AiActionPlanChecklist::class, 'ai_action_plan_checklist_id', 'ai_action_plan_checklist_id');
    }
}
