<?php
// app/Models/FsicRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FsicRequest extends Model
{
    use HasFactory;

    protected $table = 'fsic_requests';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'request_code',
        'date_requested',
        'establishment_type',
        'business_name',
        'owner_name',
        'business_type',
        'building_address',
        'barangay',
        'city',
        'province',
        'dti_sec_registration_no',
        'mayors_permit_no',
        'tin_number',
        'status'
    ];

    protected $casts = [
        'date_requested' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function inspection()
    {
        return $this->hasOne(FsicInspection::class, 'fsic_request_id');
    }

    public function approval()
    {
        return $this->hasOne(FsicApproval::class, 'fsic_request_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForInspection($query)
    {
        return $query->where('status', 'for_inspection');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Accessors
    public function getFullAddressAttribute()
    {
        return "{$this->building_address}, {$this->barangay}, {$this->city}, {$this->province}";
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'bg-yellow-100 text-yellow-700',
            'for_inspection' => 'bg-blue-100 text-blue-700',
            'inspected' => 'bg-purple-100 text-purple-700',
            'approved' => 'bg-green-100 text-green-700',
            'rejected' => 'bg-red-100 text-red-700'
        ];
        
        return $badges[$this->status] ?? 'bg-gray-100 text-gray-700';
    }
}