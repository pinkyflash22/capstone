<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangayPredictionChecklist extends Model
{
    use HasFactory;

    protected $primaryKey = 'barangay_prediction_checklist_id';

    protected $fillable = [
        'barangay_id',
        'analysis_id',
        'historical_fire_incident_id',
        'title',
        'checklist_type',
        'ai_output',
        'planning_notes',
        'priority_level',
        'status',
        'target_date',
        'complied_at',
        'assigned_to',
        'reviewed_by',
        'compliance_remarks',
        'is_ai_generated',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'complied_at' => 'datetime',
            'is_ai_generated' => 'boolean',
        ];
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }

    public function analysis()
    {
        return $this->belongsTo(Analysis::class, 'analysis_id', 'analysis_id');
    }

    public function historicalFireIncident()
    {
        return $this->belongsTo(HistoricalFireIncident::class, 'historical_fire_incident_id', 'historical_fire_incident_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to', 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }
}
