<?php

namespace App\Models;

use App\Support\DeviceInfo;
use Illuminate\Database\Eloquent\Model;

class UserLog extends Model
{
    protected $table = 'user_logs';

    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'user_agent',
    ];

    protected $appends = [
        'device_info',
    ];

    public function getDeviceInfoAttribute(): string
    {
        return DeviceInfo::fromUserAgent($this->user_agent);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
