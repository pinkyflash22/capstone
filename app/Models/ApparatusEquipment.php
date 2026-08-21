<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApparatusEquipment extends Model
{
    protected $table = 'apparatus_equipment';
    protected $primaryKey = 'apparatus_equipment_id';
    protected $fillable = ['apparatus_id', 'equipment_station_id', 'quantity', 'notes'];
    protected $casts = ['quantity' => 'integer'];

    public function apparatus() { return $this->belongsTo(Apparatus::class, 'apparatus_id', 'apparatus_id'); }
    public function stationAssignment() { return $this->belongsTo(EquipmentToStation::class, 'equipment_station_id', 'equipment_station_id'); }
}
