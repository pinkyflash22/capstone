<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AfterActivityReportReview extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'after_activity_report_review_id';
    protected $fillable = [
        'after_activity_report_id', 'reviewer_user_id', 'decision', 'comments',
        'signer_name', 'signed_at', 'signature_hash',
    ];
    protected $casts = ['signed_at' => 'datetime', 'created_at' => 'datetime'];

    public function report()
    {
        return $this->belongsTo(AfterActivityReport::class, 'after_activity_report_id', 'after_activity_report_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_user_id', 'user_id');
    }
}
