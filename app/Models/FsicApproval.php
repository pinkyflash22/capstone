<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FsicApproval extends Model
{
    protected $table = 'fsic_approvals';
    protected $primaryKey = 'id';
    protected $fillable = [
        'fsic_request_id',
        'approved_by',
        'approval_date',
        'is_approved',
        'approval_remarks',
        'fsic_number',
        'date_issued',
        'valid_until',
    ];

    // 🔗 belongs to request
    public function request()
    {
        return $this->belongsTo(FsicRequest::class);
    }

    // 🔗 approver (station officer)
    public function approver()
    {
        return $this->belongsTo(StationOfficer::class, 'approved_by', 'station_officer_id');
    }
}