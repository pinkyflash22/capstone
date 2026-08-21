<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoiceResponderPresence extends Model
{
    protected $table = 'voice_responder_presence';

    protected $fillable = ['user_id', 'station_id', 'active_call_count', 'last_seen_at'];

    protected $casts = ['active_call_count' => 'integer', 'last_seen_at' => 'datetime'];
}
