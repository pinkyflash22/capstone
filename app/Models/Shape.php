<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shape extends Model
{
     protected $table = 'boundary_location'; 
    protected $fillable = [
        'name',
        'geojson',
        'station_id'
    ];
}
