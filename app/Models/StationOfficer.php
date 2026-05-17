<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StationOfficer extends Model
{
    use HasFactory;

    protected $table = 'station_officers';
    protected $primaryKey = 'station_officer_id';
    protected $fillable=[
        'station_id',
        'user_id',
        'rank',
        'status'
    ];

     public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class,'station_id','station_id');
    }

        // 🔗 inspections handled
    public function inspections()
    {
        return $this->hasMany(FsicInspection::class, 'station_officer_id', 'station_officer_id');
    }

    // 🔗 approvals made
    public function approvals()
    {
        return $this->hasMany(FsicApproval::class, 'approved_by', 'station_officer_id');
    }

    public function ledTeams()
    {
        return $this->hasMany(Team::class, 'team_leader', 'station_officer_id');
    }
}
