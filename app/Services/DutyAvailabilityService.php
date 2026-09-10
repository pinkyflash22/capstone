<?php

namespace App\Services;

use App\Models\DutyShift;
use Illuminate\Support\Carbon;

class DutyAvailabilityService
{
    /** Recovery follows actual shift length, capped at one day for legacy shifts. */
    public static function recoveryMinutes(Carbon $start, Carbon $end): int
    {
        return (int) min(1440, max(0, $start->diffInMinutes($end)));
    }

    public static function recoveryEnd(DutyShift $shift): Carbon
    {
        return $shift->ends_at->copy()->addMinutes(self::recoveryMinutes($shift->starts_at, $shift->ends_at));
    }

    public function conflict(int $officerId, Carbon $start, Carbon $end, ?int $exceptShiftId = null): ?DutyShift
    {
        $recoveryEnd = $end->copy()->addMinutes(self::recoveryMinutes($start, $end));
        return DutyShift::query()
            ->where('status', 'scheduled')
            ->when($exceptShiftId, fn ($query) => $query->where('duty_shift_id', '!=', $exceptShiftId))
            ->where('starts_at', '<', $recoveryEnd)
            ->where('ends_at', '>', $start->copy()->subDay())
            ->whereHas('assignments', fn ($query) => $query->where('station_officer_id', $officerId)->where('team_role', '!=', 'station_duty'))
            ->orderBy('starts_at')->get()
            ->first(fn (DutyShift $shift) => $shift->starts_at->lt($recoveryEnd) && self::recoveryEnd($shift)->gt($start));
    }
}
