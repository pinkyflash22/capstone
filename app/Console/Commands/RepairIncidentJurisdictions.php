<?php

namespace App\Console\Commands;

use App\Models\Incident;
use App\Services\IncidentAutoAssignmentService;
use App\Services\IncidentJurisdictionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class RepairIncidentJurisdictions extends Command
{
    protected $signature = 'incidents:repair-jurisdiction
        {--apply : Persist matched barangay/station values and attempt dispatch}
        {--limit=500 : Maximum number of incidents to inspect}
        {--accuracy=200 : Assumed historical GPS accuracy in meters (controls the capped boundary-vicinity tolerance)}';

    protected $description = 'Preview or repair older incidents that are missing their barangay or station jurisdiction';

    public function handle(
        IncidentJurisdictionService $jurisdictionService,
        IncidentAutoAssignmentService $assignmentService
    ): int {
        if (! Schema::hasColumn('incidents', 'barangay_id') || ! Schema::hasColumn('incidents', 'station_id')) {
            $this->error('The incident jurisdiction columns are not available. No records were changed.');

            return self::FAILURE;
        }

        $limit = max(1, min(5000, (int) $this->option('limit')));
        $accuracy = max(0.0, min(1000.0, (float) $this->option('accuracy')));
        $apply = (bool) $this->option('apply');
        $rows = [];
        $summary = [
            'checked' => 0,
            'matched' => 0,
            'unmatched' => 0,
            'changed' => 0,
            'assigned' => 0,
        ];

        $incidents = Incident::query()
            ->with('marker')
            ->whereHas('marker', fn ($query) => $query->whereNotNull('lat')->whereNotNull('lng'))
            ->where(fn ($query) => $query->whereNull('barangay_id')->orWhereNull('station_id'))
            ->orderBy('incident_id')
            ->limit($limit)
            ->get();

        foreach ($incidents as $incident) {
            $summary['checked']++;
            $jurisdiction = $jurisdictionService->resolve(
                (float) $incident->marker->lat,
                (float) $incident->marker->lng,
                $accuracy
            );

            if (! $jurisdiction['barangay_id']) {
                $summary['unmatched']++;
                $rows[] = [$incident->incident_id, 'unmatched', '-', '-', '-', 'unchanged'];
                continue;
            }

            $summary['matched']++;
            $updates = [];
            if ((int) $incident->barangay_id !== (int) $jurisdiction['barangay_id']) {
                $updates['barangay_id'] = $jurisdiction['barangay_id'];
            }
            if ($jurisdiction['station_id'] && (int) $incident->station_id !== (int) $jurisdiction['station_id']) {
                $updates['station_id'] = $jurisdiction['station_id'];
            }
            if (Schema::hasColumn('incidents', 'barangay_resolution_status')
                && $incident->barangay_resolution_status !== 'matched') {
                $updates['barangay_resolution_status'] = 'matched';
            }
            if (Schema::hasColumn('incidents', 'barangay_resolution_source')
                && $incident->barangay_resolution_source !== $jurisdiction['resolution_source']) {
                $updates['barangay_resolution_source'] = $jurisdiction['resolution_source'];
            }
            if (Schema::hasColumn('incidents', 'barangay_resolved_at')
                && ($updates !== [] || ! $incident->barangay_resolved_at)) {
                $updates['barangay_resolved_at'] = now();
            }

            $wouldChange = $updates !== [];
            if ($wouldChange) {
                $summary['changed']++;
            }

            $dispatch = 'not requested';
            if ($apply && $wouldChange) {
                $incident->forceFill($updates)->save();

                if (! $incident->team_id && strtolower(trim((string) $incident->status)) === 'reported') {
                    $assignment = $assignmentService->assignSingleIncident($incident->fresh('marker'));
                    $dispatch = $assignment ? 'assigned' : 'waiting';
                    $summary['assigned'] += $assignment ? 1 : 0;
                }
            } elseif (! $apply && $wouldChange && ! $incident->team_id
                && strtolower(trim((string) $incident->status)) === 'reported') {
                $dispatch = 'would attempt';
            }

            $rows[] = [
                $incident->incident_id,
                $jurisdiction['resolution_source'],
                $jurisdiction['barangay_id'],
                $jurisdiction['station_id'] ?: '-',
                $jurisdiction['boundary_distance_meters'] === null
                    ? '-'
                    : number_format((float) $jurisdiction['boundary_distance_meters'], 1),
                $apply ? ($wouldChange ? $dispatch : 'unchanged') : ($wouldChange ? 'preview' : 'unchanged'),
            ];
        }

        if ($rows !== []) {
            $this->table(
                ['Incident', 'Boundary match', 'Barangay', 'Station', 'Edge distance (m)', 'Result'],
                $rows
            );
        }

        $mode = $apply ? 'APPLY' : 'PREVIEW ONLY';
        $this->info("{$mode}: checked {$summary['checked']}; matched {$summary['matched']}; unmatched {$summary['unmatched']}; changes {$summary['changed']}; assigned {$summary['assigned']}.");

        if (! $apply) {
            $this->line('No rows were changed. Review the output, then add --apply to persist these repairs.');
        }

        return self::SUCCESS;
    }
}
