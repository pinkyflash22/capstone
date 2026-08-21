<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceCall extends Model
{
    public const DIRECTION_INBOUND = 'inbound';
    public const DIRECTION_OUTBOUND = 'outbound';
    public const STATUS_RINGING = 'ringing';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_ANSWERED = 'answered';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_MISSED = 'missed';
    public const STATUS_ENDED = 'ended';

    public const TERMINAL_STATUSES = [
        self::STATUS_REJECTED,
        self::STATUS_MISSED,
        self::STATUS_ENDED,
    ];

    protected $fillable = [
        'channel_name',
        'direction',
        'caller_id',
        'initiated_by',
        'callback_of',
        'assigned_admin_id',
        'station_id',
        'incident_id',
        'created_incident_id',
        'caller_latitude',
        'caller_longitude',
        'caller_accuracy',
        'caller_location_label',
        'status',
        'queued_at',
        'answered_at',
        'held_at',
        'held_by',
        'ended_at',
        'flagged_at',
        'flagged_by',
        'flag_reason',
    ];

    protected $casts = [
        'caller_latitude' => 'decimal:7',
        'caller_longitude' => 'decimal:7',
        'caller_accuracy' => 'integer',
        'queued_at' => 'datetime',
        'answered_at' => 'datetime',
        'held_at' => 'datetime',
        'ended_at' => 'datetime',
        'flagged_at' => 'datetime',
    ];

    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caller_id', 'user_id');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id', 'user_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by', 'user_id');
    }

    public function callbackSource(): BelongsTo
    {
        return $this->belongsTo(self::class, 'callback_of');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'incident_id');
    }

    public function createdIncident(): BelongsTo
    {
        return $this->belongsTo(Incident::class, 'created_incident_id', 'incident_id');
    }

    public function flaggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'flagged_by', 'user_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', self::TERMINAL_STATUSES);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }
}
