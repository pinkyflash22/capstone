<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Analysis extends Model
{
    use HasFactory;

    protected $table = 'analysis';
    protected $primaryKey = 'analysis_id';
    
    protected $fillable = [
        'sector',
        'period',
        'risk_level',
        'confidence_data',
        'analysis_data',
        'recommendations',
        'predictions',
        'incident_count',
        'probability',
        'station_id',
    ];

    protected $casts = [
        'analysis_data' => 'array',
        'recommendations' => 'array',
        'predictions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function station(){
        return $this->belongsTo(Station::class,'station_id','station_id');
    }

    public function actionPlanChecklists()
    {
        return $this->hasMany(AiActionPlanChecklist::class, 'analysis_id', 'analysis_id');
    }

    public function recommendationSuggestions()
    {
        return $this->hasMany(AiRecommendationSuggestion::class, 'analysis_id', 'analysis_id');
    }
}
