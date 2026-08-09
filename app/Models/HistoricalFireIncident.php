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
        'temperature_2m',
        'humidity',
        'relative_humidity_2m',
        'precipitation',
        'rain',
        'wind_speed',
        'wind_speed_10m',
        'wind_gusts',
        'wind_gusts_10m',
        'wind_direction',
        'wind_direction_10m',
        'weather_code',
        'vapour_pressure_deficit',
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
            'temperature_2m' => 'float',
            'humidity' => 'float',
            'relative_humidity_2m' => 'float',
            'precipitation' => 'float',
            'rain' => 'float',
            'wind_speed' => 'float',
            'wind_speed_10m' => 'float',
            'wind_gusts' => 'float',
            'wind_gusts_10m' => 'float',
            'wind_direction_10m' => 'float',
            'weather_code' => 'integer',
            'vapour_pressure_deficit' => 'float',
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
