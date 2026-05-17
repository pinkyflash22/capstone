<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangayOfficer extends Model
{
    use HasFactory;

    protected $primaryKey = 'barangay_officer_id';

    protected $fillable = [
        'barangay_id',
        'user_id',
        'position',
        'status',
    ];

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function captainHall()
    {
        return $this->hasOne(BarangayHall::class, 'captain_user_id', 'barangay_officer_id');
    }

    public function patrolledRiskAreas()
    {
        return $this->hasMany(RiskArea::class, 'patrolled_by', 'barangay_officer_id');
    }
}
