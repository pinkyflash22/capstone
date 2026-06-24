<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Station;

class EventActivity extends Model
{
    protected $table = 'event_activities';
    protected $primaryKey = 'event_id';

    protected $fillable = [
        'event',
        'event_date',
        'start_time',
        'end_time',
        'schedule',
        'activity_type',
        'status',
        'barangay',
        'notes',
        'station_id',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }
}
