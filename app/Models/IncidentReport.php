<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Incident;
use App\Models\RespondedTeam;
use Illuminate\Support\Facades\Schema;

class IncidentReport extends Model
{
    use \App\Models\Concerns\ExcludesSpamIncidents;

      use HasFactory;
     protected $table = 'incident_report'; 
    protected $fillable = [
       'incident_id',
       'marker_id',
       'station_id',
       'status',
       'victims',
       'severity',
       'dateEnded',
       
    ];

    protected static function booted(): void
    {
        static::saved(function (self $report) {
            if (! Schema::hasColumn('incidents', 'dispatch_priority')) {
                return;
            }

            $priority = match (strtolower(trim((string) $report->severity))) {
                'code red', 'critical' => 'critical',
                'high' => 'high',
                'low' => 'low',
                default => 'medium',
            };

            $report->incident()->update(['dispatch_priority' => $priority]);
        });
    }
    public function incident(){
        return $this->belongsTo(Incident::class, 'incident_id', 'incident_id');

    }
    //  public function marker()
    // {
    //     return $this->belongsTo(Marker::class, 'marker_id', 'marker_id');
    // }
   
    public function respondedTeam()
    {
        return $this->hasMany(RespondedTeam::class, 'incident_id', 'incident_id');
    }
}
