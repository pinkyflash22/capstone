<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class IncidentResponseUnit extends Model
{
    protected $primaryKey = 'incident_response_unit_id';

    protected $fillable = [
        'incident_id', 'station_id', 'team_id', 'equipment_station_id', 'apparatus_id', 'run_number',
        'dispatch_source', 'status', 'requested_by', 'distance_meters', 'dispatched_at',
        'estimated_arrival_at', 'arrived_at', 'released_at', 'recalled_at', 'recalled_by', 'recall_reason',
    ];

    protected $casts = [
        'distance_meters' => 'float',
        'dispatched_at' => 'datetime',
        'estimated_arrival_at' => 'datetime',
        'arrived_at' => 'datetime',
        'released_at' => 'datetime',
        'recalled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(fn (self $unit) => Cache::forget("firefighter:registry:detail:{$unit->incident_id}"));
        static::deleted(fn (self $unit) => Cache::forget("firefighter:registry:detail:{$unit->incident_id}"));
    }

    public function incident() { return $this->belongsTo(Incident::class, 'incident_id', 'incident_id'); }
    public function station() { return $this->belongsTo(Station::class, 'station_id', 'station_id'); }
    public function team() { return $this->belongsTo(Team::class, 'team_id', 'team_id'); }
    public function equipmentAssignment() { return $this->belongsTo(EquipmentToStation::class, 'equipment_station_id', 'equipment_station_id'); }
    public function apparatus() { return $this->belongsTo(Apparatus::class, 'apparatus_id', 'apparatus_id'); }
}
