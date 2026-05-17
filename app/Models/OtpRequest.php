<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class OtpRequest extends Model
{
    use HasFactory;

    protected $table = 'otp_requests'; // Specify table name if not plural
    
    protected $fillable = [
        'user_id',
        'phone',
        'email',
        'request_id',
        'otp_code',
        'is_verified',
        'verified_at',
        'expires_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user associated with the OTP request
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Check if OTP is expired
     */
    public function isExpired(): bool
    {
        return now()->gt($this->expires_at);
    }

    /**
     * Mark OTP as verified
     */
    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }
}
