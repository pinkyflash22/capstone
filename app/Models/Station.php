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
use App\Models\Barangay;
use App\Models\StationBarangayCoverage;
use App\Models\StationMedia;

class Station extends Model
{
    protected $primaryKey = 'station_id';

    /** The existing stations table was created without created_at/updated_at columns. */
    public $timestamps = false;

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

    public function apparatus()
    {
        return $this->hasMany(Apparatus::class, 'station_id', 'station_id');
    }

    public function boundaryLocations()
    {
        return $this->hasMany(BoundaryLocation::class, 'station_id', 'station_id');
    }

    public function riskAreas()
    {
        return $this->hasMany(RiskArea::class, 'station_id', 'station_id');
    }

    public function barangayCoverage()
    {
        return $this->hasMany(StationBarangayCoverage::class, 'station_id', 'station_id');
    }

    public function barangays()
    {
        return $this->belongsToMany(
            Barangay::class,
            'station_barangay_coverage',
            'station_id',
            'barangay_id',
            'station_id',
            'barangay_id'
        )->withPivot(['station_barangay_coverage_id', 'coverage_type', 'source', 'is_active'])
            ->withTimestamps();
    }

    public function users()
{
    return $this->belongsToMany(User::class, 'station_officers', 'station_id', 'user_id')
                ->withPivot('rank')
                ->withTimestamps();
}

    public function stationOfficers()
    {
        return $this->hasMany(StationOfficer::class, 'station_id', 'station_id');
    }

    public function media()
    {
        return $this->hasMany(StationMedia::class, 'station_id', 'station_id')
            ->orderBy('sort_order')
            ->orderByDesc('station_media_id');
    }

    public function profileMedia()
    {
        return $this->hasOne(StationMedia::class, 'station_id', 'station_id')
            ->where('kind', 'profile')
            ->latest('station_media_id');
    }

    public function galleryMedia()
    {
        return $this->hasMany(StationMedia::class, 'station_id', 'station_id')
            ->where('kind', 'gallery')
            ->orderBy('sort_order')
            ->orderByDesc('station_media_id');
    }
}
