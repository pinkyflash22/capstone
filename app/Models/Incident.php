<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Marker;
use App\Models\Team;
use App\Models\User;
use App\Models\Weather;
use App\Models\Route;
use App\Models\SpotInvestigationReport;
use App\Models\HistoricalFireIncident;
use App\Models\IncidentType;
use App\Models\IncidentStatus;
use App\Models\IncidentReport;
use App\Models\IncidentContext;
use App\Models\Barangay;

class Incident extends Model
{
    use HasFactory;

    protected $primaryKey = 'incident_id';
    protected $fillable = [
        'marker_id',
        'status',
        'team_id',
        'reported_by',
        'barangay_id',
        'barangay_resolution_status',
        'barangay_resolution_source',
        'barangay_resolved_at',
    ];

    protected $casts = [
        'barangay_resolved_at' => 'datetime',
    ];

    public function marker()
    {
        return $this->belongsTo(Marker::class, 'marker_id', 'marker_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

  


    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_id');
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }

  

    public function spotInvestigationReport()
    {
        return $this->hasOne(SpotInvestigationReport::class, 'incident_id', 'incident_id');
    }

    public function incidentReport()
    {
        return $this->hasOne(IncidentReport::class, 'incident_id', 'incident_id');
    }

    public function route()
    {
        return $this->hasMany(Route::class, 'incident_id', 'incident_id');
    }

    public function historicalRecords()
    {
        return $this->hasMany(HistoricalFireIncident::class, 'incident_id', 'incident_id');
    }

    public function context()
    {
        return $this->hasOne(IncidentContext::class, 'incident_id', 'incident_id');
    }
}
