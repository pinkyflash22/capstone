<?php

namespace App\Console\Commands;

use App\Models\Marker;
use Illuminate\Console\Command;

class AuditOrphanFireMarkers extends Command
{
    protected $signature = 'markers:audit-orphan-fire
        {--limit=100 : Maximum number of orphan rows to display}';

    protected $description = 'Report fire markers that are not linked to an incident without changing the database';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $orphans = Marker::query()
            ->whereRaw('LOWER(TRIM(category)) = ?', ['fire'])
            ->whereDoesntHave('incidents')
            ->withCount([
                'stations',
                'hydrants',
                'barangayHall',
                'riskAreas',
            ])
            ->orderBy('marker_id')
            ->get([
                'marker_id',
                'title',
                'status',
                'created_at',
                'updated_at',
            ]);

        if ($orphans->isEmpty()) {
            $this->info('No orphan fire markers were found.');

            return self::SUCCESS;
        }

        $safeCleanupCandidates = $orphans->filter(fn (Marker $marker) =>
            (int) $marker->stations_count === 0
            && (int) $marker->hydrants_count === 0
            && (int) $marker->barangay_hall_count === 0
            && (int) $marker->risk_areas_count === 0
        );

        $this->warn("Found {$orphans->count()} fire marker(s) without an incident.");
        $this->line("{$safeCleanupCandidates->count()} are unreferenced by other known map records and are cleanup candidates.");
        $this->line('This command is read-only; no rows were changed.');

        $this->table(
            ['Marker ID', 'Title', 'Status', 'Other links', 'Updated'],
            $orphans->take($limit)->map(function (Marker $marker) {
                $otherLinks = collect([
                    'stations' => (int) $marker->stations_count,
                    'hydrants' => (int) $marker->hydrants_count,
                    'barangay hall' => (int) $marker->barangay_hall_count,
                    'risk areas' => (int) $marker->risk_areas_count,
                ])->filter()->map(fn ($count, $label) => "{$label}: {$count}")->implode(', ');

                return [
                    $marker->marker_id,
                    $marker->title ?: '(untitled)',
                    $marker->status ?: '(none)',
                    $otherLinks ?: 'none',
                    optional($marker->updated_at)->toDateTimeString() ?: '(unknown)',
                ];
            })->all()
        );

        if ($orphans->count() > $limit) {
            $this->line('Increase --limit to display the remaining '.($orphans->count() - $limit).' row(s).');
        }

        return self::SUCCESS;
    }
}
