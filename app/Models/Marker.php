<?php

namespace App\Models;
use App\Models\Station;
use App\Models\Hydrant;
use App\Models\Incident;
use App\Models\MarkerCategory;
use App\Models\Status;
use App\Models\Multimedia;
use Illuminate\Database\Eloquent\Model;

class Marker extends Model
{
    protected $table = 'markers';
    protected $primaryKey = 'marker_id';
    protected $fillable = [
        'title',
        'description',
        'lat',
        'lng',
        'status',
        'category',
        'image',
        'spammed_by',
        'spammed_at',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'spammed_at' => 'datetime',
    ];

    public function scopeSpam($query)
    {
        return \Illuminate\Support\Facades\Schema::hasColumn('markers', 'spammed_at')
            ? $query->whereNotNull('markers.spammed_at')
            : $query->whereRaw('1 = 0');
    }

    public function stations()
    {
        return $this->hasMany(Station::class, 'marker_id', 'marker_id');
    }

    public function hydrants()
    {
        return $this->hasMany(Hydrant::class, 'marker_id', 'marker_id');
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class, 'marker_id', 'marker_id');
    }

    public function barangayHall()
    {
        return $this->hasOne(BarangayHall::class, 'marker_id', 'marker_id');
    }

    public function riskAreas()
    {
        return $this->hasMany(RiskArea::class, 'marker_id', 'marker_id');
    }


}
