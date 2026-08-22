<?php

namespace App\Models;

use App\Support\MediaPath;
use Illuminate\Database\Eloquent\Model;

class ChecklistStepVisitPhoto extends Model
{
    protected $primaryKey = 'checklist_step_visit_photo_id';

    protected $fillable = [
        'checklist_step_visit_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'sort_order',
    ];

    protected $appends = ['url'];

    public function visit()
    {
        return $this->belongsTo(ChecklistStepVisit::class, 'checklist_step_visit_id', 'checklist_step_visit_id');
    }

    public function getUrlAttribute(): ?string
    {
        return MediaPath::url($this->file_path);
    }
}
