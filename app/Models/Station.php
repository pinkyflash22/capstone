<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\FullAddress;
use App\Models\Marker;
use App\Models\User;
use App\Models\Analysis;
use App\Models\Hydrant;
use App\Models\Team;
use App\Models\StationOfficer;
use App\Models\EquipmentToStation;
use App\Models\BoundaryLocation;

class Station extends Model
{
    protected $primaryKey = 'station_id';
    protected $fillable = [
        'station_name',
        'station_code',
        'contact_number',
        'email',
        'address_id',
        'marker_id',
    ];

    public function address()
    {
        return $this->belongsTo(FullAddress::class, 'address_id', 'address_id');
    }

    public function marker()
    {
        return $this->belongsTo(Marker::class, 'marker_id', 'marker_id');
    }

    public function hydrants()
    {
        return $this->hasMany(Hydrant::class, 'station_id', 'station_id');
    }

    public function analyses()
    {
        return $this->hasMany(Analysis::class, 'station_id', 'station_id');
    }

    public function teams()
    {
        return $this->hasManyThrough(
            Team::class,
            StationOfficer::class,
            'station_id',
            'team_leader',
            'station_id',
            'station_officer_id'
        );
    }

    public function equipmentStations()
    {
        return $this->hasMany(EquipmentToStation::class, 'station_id', 'station_id');
    }

    public function boundaryLocations()
    {
        return $this->hasMany(BoundaryLocation::class, 'station_id', 'station_id');
    }

    public function riskAreas()
    {
        return $this->hasMany(RiskArea::class, 'station_id', 'station_id');
    }
    public function users()
{
    return $this->belongsToMany(User::class, 'station_officers', 'station_id', 'user_id')
                ->withPivot('rank')
                ->withTimestamps();
}
}
