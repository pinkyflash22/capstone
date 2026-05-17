<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Station;
use App\Models\Marker;
use App\Models\HydrantType;

class Hydrant extends Model
{
    protected $primaryKey = 'hydrant_id';
    protected $fillable = [
        'hydrant_code',
        'type',
        'marker_id',
        'station_id',
    ];
 

    public function marker()
    {
        return $this->belongsTo(Marker::class, 'marker_id', 'marker_id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }

}
