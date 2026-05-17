<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskArea extends Model
{
    use HasFactory;

    protected $primaryKey = 'risk_area_id';

    protected $fillable = [
        'marker_id',
        'barangay_hall_id',
        'station_id',
        'risk_factor',
        'mode',
        'hazard_type',
        'risk_score',
        'estimated_radius',
        'near_hydrant',
        'near_water_source',
        'status',
        'patrolled_by',
        'reported_by',
    ];

    protected function casts(): array
    {
        return [
            'risk_score' => 'decimal:2',
            'estimated_radius' => 'decimal:2',
            'near_hydrant' => 'boolean',
            'near_water_source' => 'boolean',
        ];
    }

    public function marker()
    {
        return $this->belongsTo(Marker::class, 'marker_id', 'marker_id');
    }

    public function barangayHall()
    {
        return $this->belongsTo(BarangayHall::class, 'barangay_hall_id', 'barangay_hall_id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }

    public function patrolOfficer()
    {
        return $this->belongsTo(BarangayOfficer::class, 'patrolled_by', 'barangay_officer_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_id');
    }
}
