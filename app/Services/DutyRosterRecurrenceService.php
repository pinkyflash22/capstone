<?php

namespace App\Services;

use App\Models\DutyRosterTemplate;
use App\Models\DutyShift;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

class DutyRosterRecurrenceService
{
    public function generate(?int $stationId = null, ?Carbon $from = null, ?Carbon $to = null): int
    {
        $from = ($from ?? now()->startOfDay())->copy()->startOfDay();
        $to = ($to ?? now()->addDays(90)->endOfDay())->copy()->endOfDay();
        $templates = DutyRosterTemplate::query()
            ->with('assignments')
            ->where('status', 'active')
            ->when($stationId, fn ($query) => $query->where('station_id', $stationId))
            ->whereDate('effective_from', '<=', $to->toDateString())
            ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $from->toDateString()))
            ->get();

        $created = 0;
        foreach ($templates as $template) {
            foreach ($this->occurrences($template, $from, $to) as [$date, $startsAt, $endsAt]) {
                if (DutyShift::query()->where('duty_roster_template_id', $template->duty_roster_template_id)->whereDate('occurrence_date', $date)->exists()) {
                    continue;
                }
                try {
                    $shift = DutyShift::create([
                        'station_id' => $template->station_id,
                        'team_id' => $template->team_id,
                        'duty_roster_template_id' => $template->duty_roster_template_id,
                        'occurrence_date' => $date,
                        'is_override' => false,
                        'label' => $template->label,
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                        'status' => 'scheduled',
                        'created_by' => $template->created_by,
                        'notes' => $template->notes,
                    ]);
                    $shift->assignments()->createMany($template->assignments->map(fn ($assignment) => [
                        'station_officer_id' => $assignment->station_officer_id,
                        'team_role' => $assignment->team_role,
                    ])->all());
                    $template->team()->update(['roster_managed' => true]);
                    $created++;
                } catch (QueryException $exception) {
                    if (! DutyShift::query()->where('duty_roster_template_id', $template->duty_roster_template_id)->whereDate('occurrence_date', $date)->exists()) {
                        throw $exception;
                    }
                }
            }
        }

        return $created;
    }

    public function occurrences(DutyRosterTemplate $template, Carbon $from, Carbon $to): array
    {
        $startDate = Carbon::parse($template->effective_from)->max($from->copy()->startOfDay());
        $endDate = ($template->effective_until ? Carbon::parse($template->effective_until)->endOfDay() : $to->copy())->min($to);
        $cursor = $startDate->copy()->startOfDay();
        $occurrences = [];
        $recurrenceType = $template->recurrence_type ?: 'weekly';
        $selectedWeekdays = collect($template->weekdays ?? [])
            ->map(fn ($weekday) => (int) $weekday)
            ->filter(fn ($weekday) => $weekday >= 1 && $weekday <= 7)
            ->unique()
            ->values();
        $rotationStart = Carbon::parse($template->effective_from)->startOfDay();
        $rotationInterval = max(1, (int) ($template->rotation_interval_days ?? 1));
        while ($cursor->lte($endDate)) {
            $matchesRecurrence = match ($recurrenceType) {
                'daily' => true,
                'weekdays' => $selectedWeekdays->contains($cursor->dayOfWeekIso),
                'rotation' => ((int) $rotationStart->diffInDays($cursor)) % $rotationInterval === 0,
                default => $cursor->dayOfWeekIso === (int) $template->weekday,
            };

            if ($matchesRecurrence) {
                $startsAt = $cursor->copy()->setTimeFromTimeString((string) $template->starts_time);
                $endsAt = $cursor->copy()->setTimeFromTimeString((string) $template->ends_time);
                if ($endsAt->lte($startsAt)) $endsAt->addDay();
                $occurrences[] = [$cursor->toDateString(), $startsAt, $endsAt];
            }
            $cursor->addDay();
        }
        return $occurrences;
    }
}
