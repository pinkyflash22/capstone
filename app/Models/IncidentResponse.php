<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class IncidentResponse extends Model
{
    use HasFactory;

    protected $primaryKey = 'incident_response_id';

    protected $fillable = [
        'incident_id',
        'responders',
    ];

    protected $casts = [
        'responders' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(fn (self $response) => Cache::forget("firefighter:registry:detail:{$response->incident_id}"));
        static::deleted(fn (self $response) => Cache::forget("firefighter:registry:detail:{$response->incident_id}"));
    }

    public function incident()
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'incident_id');
    }
}
