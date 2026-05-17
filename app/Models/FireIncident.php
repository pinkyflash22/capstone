<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FireIncident extends Model
{
    use HasFactory;

    protected $table = 'fire_incidents';

    protected $fillable = [
        'incident_date',
        'location',
        'sector',
        'cause',
        'severity',
        'response_time_minutes',
        'damage_estimate',
        'status'
    ];

    protected $casts = [
        'incident_date' => 'datetime'
    ];
}