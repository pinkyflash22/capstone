<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyWeatherContext extends Model
{
    protected $primaryKey = 'daily_weather_context_id';

    protected $fillable = [
        'station_id',
        'barangay_id',
        'observation_date',
        'latitude',
        'longitude',
        'temperature_avg_c',
        'temperature_min_c',
        'temperature_max_c',
        'humidity_avg_percent',
        'wind_avg_kph',
        'wind_max_kph',
        'wind_gust_max_kph',
        'precipitation_total_mm',
        'rain_total_mm',
        'dominant_weather_code',
        'dominant_condition',
        'hourly_observation_count',
        'source',
        'raw_payload',
        'fetched_at',
    ];

    protected $casts = [
        'observation_date' => 'date',
        'raw_payload' => 'array',
        'fetched_at' => 'datetime',
    ];
}
