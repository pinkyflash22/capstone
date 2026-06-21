<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StationBarangayCoverage extends Model
{
    protected $table = 'station_barangay_coverage';
    protected $primaryKey = 'station_barangay_coverage_id';

    protected $fillable = [
        'station_id',
        'barangay_id',
        'coverage_type',
        'source',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
