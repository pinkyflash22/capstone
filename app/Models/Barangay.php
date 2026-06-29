<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\City;
use App\Models\FullAddress;
use App\Models\BarangayHall;
use App\Models\HistoricalFireIncident;
use App\Models\BarangayPredictionChecklist;
use App\Models\BoundaryLocation;
use App\Models\Station;
use App\Models\StationBarangayCoverage;

class Barangay extends Model
{
    use HasFactory;

    protected $primaryKey = 'barangay_id';
    protected $fillable = ['barangay_name', 'city_id'];

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'city_id');
    }

    public function fullAddresses()
    {
        return $this->hasMany(FullAddress::class, 'barangay_id', 'barangay_id');
    }

    public function hall()
    {
        return $this->hasOne(BarangayHall::class, 'barangay_id', 'barangay_id');
    }

    public function barangayHall()
    {
        return $this->hall();
    }

    public function historicalFireIncidents()
    {
        return $this->hasMany(HistoricalFireIncident::class, 'barangay_id', 'barangay_id');
    }

    public function predictionChecklists()
    {
        return $this->hasMany(BarangayPredictionChecklist::class, 'barangay_id', 'barangay_id');
    }

    public function boundaryLocations()
    {
        return $this->hasMany(BoundaryLocation::class, 'barangay_id', 'barangay_id');
    }

    public function stationCoverage()
    {
        return $this->hasMany(StationBarangayCoverage::class, 'barangay_id', 'barangay_id');
    }

    public function stations()
    {
        return $this->belongsToMany(
            Station::class,
            'station_barangay_coverage',
            'barangay_id',
            'station_id',
            'barangay_id',
            'station_id'
        )->withPivot(['station_barangay_coverage_id', 'coverage_type', 'source', 'is_active'])
            ->withTimestamps();
    }
}
