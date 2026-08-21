<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestigationReportImage extends Model
{
    use HasFactory;

    protected $primaryKey = 'investigation_report_image_id';

    protected $fillable = [
        'report_type',
        'report_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'sort_order',
    ];
}
