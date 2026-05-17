<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangayHall extends Model
{
    use HasFactory;

    protected $primaryKey = 'barangay_hall_id';

    protected $fillable = [
        'barangay_id',
        'marker_id',
        'hall_name',
        'address_id',
        'contact_number',
        'email',
        'captain_user_id',
    ];

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }

    public function marker()
    {
        return $this->belongsTo(Marker::class, 'marker_id', 'marker_id');
    }

    public function address()
    {
        return $this->belongsTo(FullAddress::class, 'address_id', 'address_id');
    }

    public function captain()
    {
        return $this->belongsTo(BarangayOfficer::class, 'captain_user_id', 'barangay_officer_id');
    }

    public function fireRecords()
    {
        return $this->hasMany(BarangayFireRecord::class, 'barangay_hall_id', 'barangay_hall_id');
    }

    public function riskAreas()
    {
        return $this->hasMany(RiskArea::class, 'barangay_hall_id', 'barangay_hall_id');
    }
}
