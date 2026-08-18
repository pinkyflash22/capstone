<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function incident()
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'incident_id');
    }
}
