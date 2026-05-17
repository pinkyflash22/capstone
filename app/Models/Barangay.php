<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\City;
use App\Models\FullAddress;
use App\Models\BarangayHall;
use App\Models\HistoricalFireIncident;
use App\Models\BarangayPredictionChecklist;

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

    public function historicalFireIncidents()
    {
        return $this->hasMany(HistoricalFireIncident::class, 'barangay_id', 'barangay_id');
    }

    public function predictionChecklists()
    {
        return $this->hasMany(BarangayPredictionChecklist::class, 'barangay_id', 'barangay_id');
    }
}
