<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricalFireIncident extends Model
{
    use HasFactory;

    protected $primaryKey = 'historical_fire_incident_id';

    protected $fillable = [
        'barangay_id',
        'incident_id',
        'incident_date',
        'incident_time',
        'source_name',
        'source_type',
        'source_reference',
        'fire_classification',
        'fire_cause',
        'incident_location',
        'incident_count',
        'injured_count',
        'death_count',
        'estimated_damage',
        'temperature',
        'humidity',
        'wind_speed',
        'wind_direction',
        'weather_condition',
        'is_verified',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'estimated_damage' => 'decimal:2',
            'temperature' => 'float',
            'humidity' => 'float',
            'wind_speed' => 'float',
            'is_verified' => 'boolean',
        ];
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }

    public function incident()
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'incident_id');
    }

    public function predictionChecklists()
    {
        return $this->hasMany(BarangayPredictionChecklist::class, 'historical_fire_incident_id', 'historical_fire_incident_id');
    }
}
