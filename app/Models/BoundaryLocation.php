<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BoundaryLocation extends Model
{
    use HasFactory;

    public const MAP_CACHE_VERSION_KEY = 'map:shapes:version';

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

    protected static function booted(): void
    {
        static::saved(fn () => static::invalidateMapCache());
        static::deleted(fn () => static::invalidateMapCache());
    }

    public static function mapCacheVersion(): string
    {
        return (string) Cache::get(static::MAP_CACHE_VERSION_KEY, 'initial');
    }

    public static function invalidateMapCache(): string
    {
        $version = (string) Str::uuid();
        Cache::forever(static::MAP_CACHE_VERSION_KEY, $version);

        return $version;
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }
}
