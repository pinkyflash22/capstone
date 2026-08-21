<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Apparatus extends Model
{
    use SoftDeletes;

    protected $table = 'apparatus';
    protected $primaryKey = 'apparatus_id';

    protected $fillable = [
        'station_id', 'equipment_id', 'unit_number', 'plate_number', 'apparatus_type',
        'water_capacity_liters', 'status', 'maintenance_status', 'last_maintenance_at',
        'next_maintenance_due_at', 'gps_status', 'last_lat', 'last_lng',
        'gps_accuracy_meters', 'gps_speed_kph', 'gps_heading', 'gps_last_seen_at', 'notes',
    ];

    protected $casts = [
        'water_capacity_liters' => 'integer',
        'last_lat' => 'float',
        'last_lng' => 'float',
        'gps_accuracy_meters' => 'float',
        'gps_speed_kph' => 'float',
        'gps_heading' => 'float',
        'last_maintenance_at' => 'date',
        'next_maintenance_due_at' => 'date',
        'gps_last_seen_at' => 'datetime',
    ];

    public function station() { return $this->belongsTo(Station::class, 'station_id', 'station_id'); }
    public function equipment() { return $this->belongsTo(Equipment::class, 'equipment_id', 'equipment_id'); }
    public function responseUnits() { return $this->hasMany(IncidentResponseUnit::class, 'apparatus_id', 'apparatus_id'); }
    public function loadout() { return $this->hasMany(ApparatusEquipment::class, 'apparatus_id', 'apparatus_id'); }

    public function scopeDispatchable($query)
    {
        return $query->where('status', 'available')->where('maintenance_status', 'operational');
    }
}
