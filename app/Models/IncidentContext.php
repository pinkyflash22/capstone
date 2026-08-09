<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentContext extends Model
{
    use HasFactory;

    protected $primaryKey = 'incident_context_id';

    protected $fillable = [
        'incident_id',
        'incident_month',
        'season',
        'day_of_week',
        'time_of_day_bucket',
        'is_holiday_or_event_period',
        'event_period_label',
        'temperature',
        'temperature_2m',
        'humidity',
        'relative_humidity_2m',
        'precipitation',
        'rain',
        'rainfall',
        'wind_speed',
        'wind_speed_10m',
        'wind_gusts',
        'wind_gusts_10m',
        'wind_direction',
        'wind_direction_10m',
        'weather_code',
        'weather_condition',
        'is_dry_season',
        'context_source',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'incident_month' => 'integer',
            'is_holiday_or_event_period' => 'boolean',
            'temperature' => 'decimal:2',
            'temperature_2m' => 'decimal:2',
            'humidity' => 'decimal:2',
            'relative_humidity_2m' => 'decimal:2',
            'precipitation' => 'decimal:2',
            'rain' => 'decimal:2',
            'rainfall' => 'decimal:2',
            'wind_speed' => 'decimal:2',
            'wind_speed_10m' => 'decimal:2',
            'wind_gusts' => 'decimal:2',
            'wind_gusts_10m' => 'decimal:2',
            'wind_direction' => 'string',
            'wind_direction_10m' => 'decimal:2',
            'weather_code' => 'integer',
            'is_dry_season' => 'boolean',
            'captured_at' => 'datetime',
        ];
    }

    public function incident()
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'incident_id');
    }
}
