<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoundaryLocation extends Model
{
    use HasFactory;

    protected $table = 'boundary_location';
    protected $fillable = ['name', 'geojson', 'station_id'];

    protected $casts = [
        'geojson' => 'array',
    ];

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }
}
