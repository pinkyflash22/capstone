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
use App\Models\Station;
use App\Services\IncidentResponderSnapshotService;
use Illuminate\Support\Facades\Log;

class Incident extends Model
{
    use HasFactory;

    protected $primaryKey = 'incident_id';
    protected $fillable = [
        'marker_id',
        'status',
        'occurred_at',
        'occurred_at_source',
        'occurred_at_verified_at',
        'occurred_at_verified_by',
        'incident_completed_at',
        'station_id',
        'team_id',
        'reported_by',
        'barangay_id',
        'barangay_resolution_status',
        'barangay_resolution_source',
        'barangay_resolved_at',
        'dispatch_priority',
        'dispatch_assigned_at',
        'duplicate_of_incident_id',
    ];

    protected $casts = [
        'barangay_resolved_at' => 'datetime',
        'occurred_at' => 'datetime',
        'occurred_at_verified_at' => 'datetime',
        'incident_completed_at' => 'datetime',
        'dispatch_assigned_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $incident) {
            if (! $incident->occurred_at) {
                $incident->occurred_at = $incident->created_at ?: now();
                $incident->occurred_at_source = $incident->occurred_at_source ?: 'report_time_fallback';
            }

            if (in_array(strtolower((string) $incident->status), ['closed', 'completed'], true)
                && ! $incident->incident_completed_at) {
                $incident->incident_completed_at = now();
            }
        });

        static::saved(function (self $incident) {
            $shouldCapture = filled($incident->team_id)
                && ($incident->wasRecentlyCreated
                    || $incident->wasChanged('team_id'));

            if (! $shouldCapture) {
                return;
            }

            try {
                app(IncidentResponderSnapshotService::class)->captureForIncident($incident);
            } catch (\Throwable $exception) {
                Log::error('Unable to capture the incident responder snapshot.', [
                    'incident_id' => $incident->incident_id,
                    'team_id' => $incident->team_id,
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }

    public function marker()
    {
        return $this->belongsTo(Marker::class, 'marker_id', 'marker_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
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

    public function responderSnapshot()
    {
        return $this->hasOne(IncidentResponse::class, 'incident_id', 'incident_id');
    }

    public function responseUnits()
    {
        return $this->hasMany(IncidentResponseUnit::class, 'incident_id', 'incident_id');
    }

    public function canonicalIncident()
    {
        return $this->belongsTo(self::class, 'duplicate_of_incident_id', 'incident_id');
    }

    public function duplicateReports()
    {
        return $this->hasMany(self::class, 'duplicate_of_incident_id', 'incident_id');
    }
}
