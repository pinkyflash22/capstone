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
        'entry_type',
        'hazard_category',
        'risk_type',
        'street_or_sitio',
        'risk_factor',
        'mode',
        'condition_code',
        'condition_label',
        'hazard_type',
        'population_density_level',
        'building_density_level',
        'road_accessibility',
        'distance_to_hydrant',
        'distance_to_water_source',
        'nearby_establishments',
        'estimated_affected_households',
        'risk_score',
        'estimated_radius',
        'assessment_level',
        'assessment_description',
        'remarks',
        'near_hydrant',
        'near_water_source',
        'blocked_hydrant',
        'informal_settlement',
        'cooking_fire_prone_activity',
        'combustible_materials_present',
        'poor_waste_disposal',
        'previous_fire_prone_location',
        'status',
        'review_status',
        'review_type',
        'review_notes',
        'reviewed_at',
        'reviewed_by',
        'patrolled_by',
        'reported_by',
    ];

    protected function casts(): array
    {
        return [
            'risk_score' => 'decimal:2',
            'estimated_radius' => 'decimal:2',
            'distance_to_hydrant' => 'decimal:2',
            'distance_to_water_source' => 'decimal:2',
            'near_hydrant' => 'boolean',
            'near_water_source' => 'boolean',
            'blocked_hydrant' => 'boolean',
            'informal_settlement' => 'boolean',
            'cooking_fire_prone_activity' => 'boolean',
            'combustible_materials_present' => 'boolean',
            'poor_waste_disposal' => 'boolean',
            'previous_fire_prone_location' => 'boolean',
            'reviewed_at' => 'datetime',
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

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }

    public function photos()
    {
        return $this->hasMany(RiskAreaPhoto::class, 'risk_area_id', 'risk_area_id');
    }
}
