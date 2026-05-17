<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionChecklist extends Model
{
    protected $table = 'inspection_checklist';

    protected $fillable = [
        'fsic_inspection_id',
        'fire_protection',
        'alarm_systems',
        'standard_material_used',
        'electrical',
        'housekeeping',
        'hazards',
        'planning',
        'remarks',
    ];

    // 🔗 belongs to inspection
    public function inspection()
    {
        return $this->belongsTo(FsicInspection::class);
    }
}
