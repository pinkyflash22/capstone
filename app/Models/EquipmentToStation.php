<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Station;
use App\Models\Equipment;

class EquipmentToStation extends Model
{
    use HasFactory;
    protected $table = 'equipments_to_stations';
    protected $primaryKey = 'equipment_station_id';
    protected $fillable = [
        'station_id',
        'equipment_id',
        'available_quantity',
        'damaged_quantity',
        'maintenance_quantity',
    ];

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id', 'equipment_id');
    }

    public function responseUnits()
    {
        return $this->hasMany(IncidentResponseUnit::class, 'equipment_station_id', 'equipment_station_id');
    }

    public function apparatusLoadouts()
    {
        return $this->hasMany(ApparatusEquipment::class, 'equipment_station_id', 'equipment_station_id');
    }
}
