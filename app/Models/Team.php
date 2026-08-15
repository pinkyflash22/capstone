<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\StationOfficer;
use App\Models\Incident;

class Team extends Model
{
    use HasFactory;

    protected $primaryKey = 'team_id';
    protected $fillable = [
        'commander_driver',
        'pumper',
        'team_leader',
        'firefighter_officer_1',
        'firefighter_officer_2',
        'firefighter_officer_3',
        'rescue_radio',
        'station_id',
        'status'
    ];

    public function commander()
    {
        return $this->belongsTo(StationOfficer::class, 'commander_driver', 'station_officer_id');
    }

    public function pumpers()
    {
        return $this->belongsTo(StationOfficer::class, 'pumper', 'station_officer_id');
    }

    public function teamLeader()
    {
        return $this->belongsTo(StationOfficer::class, 'team_leader', 'station_officer_id');
    }

    public function officer1()
    {
        return $this->belongsTo(StationOfficer::class, 'firefighter_officer_1', 'station_officer_id');
    }

    public function officer2()
    {
        return $this->belongsTo(StationOfficer::class, 'firefighter_officer_2', 'station_officer_id');
    }

    public function officer3()
    {
        return $this->belongsTo(StationOfficer::class, 'firefighter_officer_3', 'station_officer_id');
    }

    public function rescueRadio()
    {
        return $this->belongsTo(StationOfficer::class, 'rescue_radio', 'station_officer_id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }

    public function getTeamNameAttribute()
    {
        return 'Team #' . $this->team_id;
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class, 'team_id', 'team_id');
    }
}
