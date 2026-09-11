<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CommunityActivityRequest extends Model
{
    protected $table = 'community_activity_requests';
    protected $primaryKey = 'community_activity_request_id';

    protected $fillable = [
        'requester_user_id',
        'requester_role',
        'submitted_from',
        'station_id',
        'barangay_id',
        'request_type',
        'title',
        'description',
        'preferred_date',
        'preferred_start_time',
        'preferred_end_time',
        'contact_name',
        'contact_number',
        'status',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
        'converted_event_id',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public static function isAvailable(): bool
    {
        return Schema::hasTable('community_activity_requests');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_user_id', 'user_id');
    }

    public function canCitizenEdit(): bool
    {
        return $this->submitted_from === 'citizen'
            && $this->status === 'pending'
            && $this->reviewed_at === null
            && $this->reviewed_by === null
            && $this->converted_event_id === null;
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }

    public function convertedEvent()
    {
        return $this->belongsTo(EventActivity::class, 'converted_event_id', 'event_id');
    }
}
