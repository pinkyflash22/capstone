<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use App\Models\FullAddress;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Team;
use App\Models\Incident;
use App\Models\Analysis;
use App\Models\Post;
use App\Models\Comment;
use App\Models\PostLike;
use App\Models\SpotInvestigationReport;
use App\Models\ProgressInvestigationReport;

use App\Models\StationOfficer;
use App\Models\Barangay;
use App\Models\BarangayHall;
use App\Models\BarangayOfficer;
use App\Models\BarangayFireRecord;
use App\Models\BarangayPredictionChecklist;



class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
protected $table = 'users';  // it probably already is 'users'
protected $primaryKey = 'user_id';
 public $incrementing = true;
 protected $keyType = 'int';    
/**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_code',
        'first_name',
        'middle_name',
        'last_name',
        'role_id',
        'address_id',
        'contact_number',
        'email',
        'email_verified_at',
        'is_verified',
        'password',
        'image',
        'google_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_verified' => 'boolean',
            'password' => 'hashed',
        ];
    }

   public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function address()
    {
        return $this->belongsTo(FullAddress::class, 'address_id', 'address_id');
    }

    public function barangayOfficer()
    {
        return $this->hasOne(BarangayOfficer::class, 'user_id', 'user_id');
    }

    public function captainedBarangayHall()
    {
        return $this->hasOneThrough(
            BarangayHall::class,
            BarangayOfficer::class,
            'user_id',
            'captain_user_id',
            'user_id',
            'barangay_officer_id'
        );
    }

    public function barangayFireRecords()
    {
        return $this->hasMany(BarangayFireRecord::class, 'reported_by', 'user_id');
    }

    public function assignedPredictionChecklists()
    {
        return $this->hasMany(BarangayPredictionChecklist::class, 'assigned_to', 'user_id');
    }

    public function reviewedPredictionChecklists()
    {
        return $this->hasMany(BarangayPredictionChecklist::class, 'reviewed_by', 'user_id');
    }

    public function reportedRiskAreas()
    {
        return $this->hasMany(RiskArea::class, 'reported_by', 'user_id');
    }

    public function shifts()
    {
        return $this->belongsToMany(Shift::class, 'firefighter_shift', 'firefighter_id', 'shift_id')
                    ->withPivot('date')
                    ->withTimestamps();
    }

    // Teams relationships (as commander_driver)
    public function teamsAsCommander()
    {
        return $this->hasManyThrough(Team::class, StationOfficer::class, 'user_id', 'commander_driver', 'user_id', 'station_officer_id');
    }

    // Teams relationships (as pumper)
    public function teamsAsPumper()
    {
        return $this->hasManyThrough(Team::class, StationOfficer::class, 'user_id', 'pumper', 'user_id', 'station_officer_id');
    }

    // Teams relationships (as team_leader)
    public function teamsAsLeader()
    {
        return $this->hasManyThrough(Team::class, StationOfficer::class, 'user_id', 'team_leader', 'user_id', 'station_officer_id');
    }

    // Teams relationships (as firefighter_officer_1)
    public function teamsAsOfficer1()
    {
        return $this->hasManyThrough(Team::class, StationOfficer::class, 'user_id', 'firefighter_officer_1', 'user_id', 'station_officer_id');
    }

    // Teams relationships (as firefighter_officer_2)
    public function teamsAsOfficer2()
    {
        return $this->hasManyThrough(Team::class, StationOfficer::class, 'user_id', 'firefighter_officer_2', 'user_id', 'station_officer_id');
    }

    // Teams relationships (as firefighter_officer_3)
    public function teamsAsOfficer3()
    {
        return $this->hasManyThrough(Team::class, StationOfficer::class, 'user_id', 'firefighter_officer_3', 'user_id', 'station_officer_id');
    }

    // Teams relationships (as rescue_radio)
    public function teamsAsRescueRadio()
    {
        return $this->hasManyThrough(Team::class, StationOfficer::class, 'user_id', 'rescue_radio', 'user_id', 'station_officer_id');
    }

    public function incidentsReported()
    {
        return $this->hasMany(Incident::class, 'reported_by', 'user_id');
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id', 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'user_id', 'user_id');
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class, 'user_id', 'user_id');
    }

    // Accessor for full name
       public function getFullNameAttribute()
    {
        $middle = $this->middle_name ? ' ' . $this->middle_name . ' ' : ' ';
        return trim($this->first_name . $middle . $this->last_name);
    }
    public function stations()
{
    return $this->belongsToMany(Station::class, 'station_officers', 'user_id', 'station_id')
                ->withPivot('rank')
                ->withTimestamps();
}

public function fsicRequests()
{
    return $this->hasMany(FsicRequest::class);
}
public function otpRequests()
{
    return $this->hasMany(OtpRequest::class);
}

public function scopeAnonymous(Builder $query): Builder
{
    return $query->whereHas('role', function ($roleQuery) {
        $roleQuery->where('role', 'anonymous');
    });
}

public static function resolveAnonymousUserId(): ?int
{
    return static::query()
        ->anonymous()
        ->orderBy('user_id')
        ->value('user_id');
}
}
