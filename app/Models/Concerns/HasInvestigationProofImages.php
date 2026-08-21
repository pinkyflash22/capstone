<?php

namespace App\Models\Concerns;

use App\Models\InvestigationReportImage;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasInvestigationProofImages
{
    abstract protected function investigationReportType(): string;

    public function proofImages(): HasMany
    {
        return $this->hasMany(InvestigationReportImage::class, 'report_id', $this->getKeyName())
            ->where('report_type', $this->investigationReportType())
            ->orderBy('sort_order')
            ->orderBy('investigation_report_image_id');
    }
}
