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
        'humidity',
        'rainfall',
        'wind_speed',
        'wind_direction',
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
            'humidity' => 'decimal:2',
            'rainfall' => 'decimal:2',
            'wind_speed' => 'decimal:2',
            'wind_direction' => 'string',
            'is_dry_season' => 'boolean',
            'captured_at' => 'datetime',
        ];
    }

    public function incident()
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'incident_id');
    }
}
