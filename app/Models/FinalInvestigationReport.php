<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinalInvestigationReport extends Model
{
    use HasFactory;

    protected $table ='final_investigation_report';
    protected $primaryKey = 'final_investigation_report_id';
    protected $fillable = [
        'station_officer_id',
        'exact_address',
        'progress_investigation_id',
        'total_damage',
    ];

    // public function address()
    // {
    //     return $this->belongsTo(FullAddress::class, 'address_id', 'address_id');
    // }

    public function progressInvestigation()
    {
        return $this->belongsTo(ProgressInvestigationReport::class, 'progress_investigation_id', 'progress_investigation_report_id');
    }

    public function investigator()
    {
        return $this->belongsTo(StationOfficer::class, 'station_officer_id', 'station_officer_id');
    }
}
