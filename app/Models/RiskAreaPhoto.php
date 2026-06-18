<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskAreaPhoto extends Model
{
    use HasFactory;

    protected $primaryKey = 'risk_area_photo_id';

    protected $fillable = [
        'risk_area_id',
        'photo_path',
        'photo_name',
        'caption',
    ];

    public function riskArea()
    {
        return $this->belongsTo(RiskArea::class, 'risk_area_id', 'risk_area_id');
    }
}
