<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoundaryLocation extends Model
{
    use HasFactory;

    protected $table = 'boundary_location';
    protected $fillable = [
        'name',
        'geojson',
        'station_id',
        'barangay_id',
        'boundary_type',
        'source',
        'external_place_id',
        'properties',
    ];

    protected $casts = [
        'geojson' => 'array',
        'properties' => 'array',
    ];

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }
}
