<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Incident;
use App\Models\RespondedTeam;

class IncidentReport extends Model
{
      use HasFactory;
     protected $table = 'incident_report'; 
    protected $fillable = [
       'incident_id',
       'victims',
       'severity',
       'dateEnded',
       
    ];
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
