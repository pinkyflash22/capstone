<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MitigationActionAttachment extends Model
{
    use HasFactory;

    protected $primaryKey = 'mitigation_action_attachment_id';

    protected $fillable = [
        'mitigation_action_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'attachment_type',
    ];

    public function mitigationAction()
    {
        return $this->belongsTo(MitigationAction::class, 'mitigation_action_id', 'mitigation_action_id');
    }
}
