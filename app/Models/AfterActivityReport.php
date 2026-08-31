<?php

namespace App\Models;

use App\Models\Concerns\HasInvestigationProofImages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AfterActivityReport extends Model
{
    use HasFactory, HasInvestigationProofImages;

    protected $primaryKey = 'after_activity_report_id';

    protected $fillable = [
        'event_activity_id',
        'station_id',
        'station_officer_id',
        'approved_by_station_officer_id',
        'activity_title',
        'activity_type',
        'activity_date',
        'activity_location',
        'participants',
        'deployed_resources',
        'memorandum_for',
        'report_completed_at',
        'references',
        'background',
        'significance',
        'activity_details',
        'issues_challenges',
        'discussion_assessment',
        'recommendations',
        'review_status',
        'review_comments',
        'reviewed_by_user_id',
        'reviewed_at',
        'prepared_signed_at',
        'prepared_signature_hash',
        'approval_signer_name',
        'approved_signed_at',
        'approval_signature_hash',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'report_completed_at' => 'datetime',
        'participants' => 'array',
        'deployed_resources' => 'array',
        'reviewed_at' => 'datetime',
        'prepared_signed_at' => 'datetime',
        'approved_signed_at' => 'datetime',
    ];

    public function eventActivity()
    {
        return $this->belongsTo(EventActivity::class, 'event_activity_id', 'event_id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id', 'station_id');
    }

    public function investigator()
    {
        return $this->belongsTo(StationOfficer::class, 'station_officer_id', 'station_officer_id');
    }

    public function preparedBy()
    {
        return $this->investigator();
    }

    public function approvedBy()
    {
        return $this->belongsTo(StationOfficer::class, 'approved_by_station_officer_id', 'station_officer_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id', 'user_id');
    }

    public function reviewHistory()
    {
        return $this->hasMany(AfterActivityReportReview::class, 'after_activity_report_id', 'after_activity_report_id')->latest('created_at');
    }

    public function isApproved(): bool
    {
        return $this->review_status === 'approved' && filled($this->approval_signature_hash);
    }

    public function approvalSignatureIsValid(): bool
    {
        if (! $this->isApproved() || ! $this->reviewed_by_user_id || ! $this->approved_signed_at) {
            return false;
        }

        return hash_equals((string) $this->approval_signature_hash, $this->makeApprovalSignatureHash());
    }

    public function makeApprovalSignatureHash(): string
    {
        $content = collect($this->only([
            'after_activity_report_id', 'event_activity_id', 'station_id', 'station_officer_id',
            'activity_title', 'activity_type', 'activity_date', 'activity_location', 'participants',
            'deployed_resources', 'memorandum_for', 'report_completed_at', 'references', 'background',
            'significance', 'activity_details', 'issues_challenges', 'discussion_assessment', 'recommendations',
        ]))->map(fn ($value) => $value instanceof \DateTimeInterface ? $value->format(DATE_ATOM) : $value)->all();

        return hash_hmac('sha256', json_encode([
            'content' => $content,
            'prepared_signature' => $this->prepared_signature_hash,
            'reviewer_user_id' => (int) $this->reviewed_by_user_id,
            'signer_name' => $this->approval_signer_name,
            'signed_at' => $this->approved_signed_at?->format(DATE_ATOM),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), (string) config('app.key'));
    }

    protected function investigationReportType(): string
    {
        return 'aar';
    }
}
