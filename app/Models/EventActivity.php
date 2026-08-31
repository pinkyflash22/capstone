<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Station;
use App\Models\User;

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
        'user_id',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function afterActivityReport()
    {
        return $this->hasOne(AfterActivityReport::class, 'event_activity_id', 'event_id');
    }
}
