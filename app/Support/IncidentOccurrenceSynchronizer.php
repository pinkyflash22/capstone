<?php

namespace App\Support;

use App\Models\Incident;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IncidentOccurrenceSynchronizer
{
    public static function verify(
        Incident $incident,
        Carbon|string|null $occurredAt,
        string $source,
        ?int $verifiedByUserId = null
    ): bool {
        if (! $occurredAt) {
            return false;
        }

        $verifiedOccurrence = $occurredAt instanceof Carbon
            ? $occurredAt->copy()
            : Carbon::parse($occurredAt);
        $previousOccurrence = $incident->occurred_at?->copy();
        $normalizedSource = mb_substr(trim($source) ?: 'investigation', 0, 50);
        $changed = ! $previousOccurrence || ! $previousOccurrence->equalTo($verifiedOccurrence);
        $verificationChanged = ! $incident->occurred_at_verified_at
            || (int) $incident->occurred_at_verified_by !== (int) $verifiedByUserId
            || $incident->occurred_at_source !== $normalizedSource;

        if (! $changed && ! $verificationChanged) {
            return false;
        }

        $incident->forceFill([
            'occurred_at' => $verifiedOccurrence,
            'occurred_at_source' => $normalizedSource,
            'occurred_at_verified_at' => now(),
            'occurred_at_verified_by' => $verifiedByUserId,
        ])->save();

        if (Schema::hasTable('incident_occurrence_revisions')) {
            DB::table('incident_occurrence_revisions')->insert([
                'incident_id' => $incident->incident_id,
                'previous_occurred_at' => $previousOccurrence,
                'occurred_at' => $verifiedOccurrence,
                'source' => $normalizedSource,
                'verified_by_user_id' => $verifiedByUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return true;
    }
}
