<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\EquipmentToStation;

class Equipment extends Model
{
    use HasFactory;
    protected $table = 'equipments';
    protected $primaryKey = 'equipment_id';
    protected $fillable = ['equipment_name', 'category', 'type'];

    public function stationEquipments()
    {
        return $this->hasMany(EquipmentToStation::class, 'equipment_id', 'equipment_id');
    }
}
