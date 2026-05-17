<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangayFireRecord extends Model
{
    use HasFactory;

    protected $primaryKey = 'barangay_fire_record_id';

    protected $fillable = [
        'barangay_hall_id',
        'incident_id',
        'reported_by',
        'incident_date',
        'incident_count',
        'fire_classification',
        'fire_cause',
        'temperature',
        'humidity',
        'wind_speed',
        'wind_direction',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'temperature' => 'float',
            'humidity' => 'float',
            'wind_speed' => 'float',
        ];
    }

    public function barangayHall()
    {
        return $this->belongsTo(BarangayHall::class, 'barangay_hall_id', 'barangay_hall_id');
    }

    public function incident()
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'incident_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_id');
    }
}
