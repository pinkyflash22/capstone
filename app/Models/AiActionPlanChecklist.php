<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AiActionPlanChecklist extends Model
{
    use HasFactory;

    protected $table = 'ai_action_plan_checklists';
    protected $primaryKey = 'ai_action_plan_checklist_id';

    protected $fillable = [
        'analysis_id',
        'station_id',
        'barangay_id',
        'risk_area_id',
        'source_type',
        'source_title',
        'source_description',
        'plan_title',
        'plan_description',
        'checklist_items',
        'success_indicator',
        'priority_level',
        'status',
        'source_signature',
        'target_date',
        'assigned_to',
        'confirmed_by',
        'confirmed_at',
        'confirmation_notes',
        'ai_payload',
        'is_ai_generated',
        'analysis_revision',
        'is_superseded',
        'requires_revision_review',
        'superseded_at',
    ];

    protected function casts(): array
    {
        return [
            'checklist_items' => 'array',
            'ai_payload' => 'array',
            'target_date' => 'date',
            'confirmed_at' => 'datetime',
            'is_ai_generated' => 'boolean',
            'analysis_revision' => 'integer',
            'is_superseded' => 'boolean',
            'requires_revision_review' => 'boolean',
            'superseded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('current_revision', function (Builder $builder) {
            $builder->where($builder->getModel()->qualifyColumn('is_superseded'), false);
        });
    }

    public function analysis()
    {
        return $this->belongsTo(Analysis::class, 'analysis_id', 'analysis_id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }

    public function riskArea()
    {
        return $this->belongsTo(RiskArea::class, 'risk_area_id', 'risk_area_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to', 'user_id');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by', 'user_id');
    }

    public function suggestions()
    {
        return $this->hasMany(AiRecommendationSuggestion::class, 'ai_action_plan_checklist_id', 'ai_action_plan_checklist_id');
    }

    public function mitigationActions()
    {
        return $this->hasMany(MitigationAction::class, 'ai_action_plan_checklist_id', 'ai_action_plan_checklist_id');
    }

    public function items()
    {
        return $this->hasMany(AiActionPlanChecklistItem::class, 'ai_action_plan_checklist_id', 'ai_action_plan_checklist_id')
            ->orderBy('item_order');
    }

    public function getChecklistProgressAttribute(): array
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        $total = $items->count();
        $followed = $items->where('status', 'followed')->count();
        $notFollowed = $items->where('status', 'not_followed')->count();

        return [
            'total' => $total,
            'followed' => $followed,
            'not_followed' => $notFollowed,
            'pending' => max(0, $total - $followed - $notFollowed),
            'percent' => $total > 0 ? (int) round(($followed / $total) * 100) : 0,
        ];
    }
}
