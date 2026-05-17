<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FsicInspection extends Model
{
     protected $table = 'fsic_inspections';
    protected $primaryKey = 'id';
    protected $fillable = [
        'fsic_request_id',
        'station_officer_id',
        'inspection_date',
        'inspection_remarks',
        'is_compliant',
    ];

    // 🔗 belongs to request
    public function request()
    {
        return $this->belongsTo(FsicRequest::class);
    }

    // 🔗 inspector (station officer)
    public function inspector()
    {
        return $this->belongsTo(StationOfficer::class, 'station_officer_id', 'station_officer_id');
    }

    // 🔗 checklist (boolean inspection data)
    public function checklist()
    {
        return $this->hasOne(InspectionChecklist::class);
    }
}