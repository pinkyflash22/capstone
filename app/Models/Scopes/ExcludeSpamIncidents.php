<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExcludeSpamIncidents implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Self-relationship queries replace the model table with a SQL alias.
        $modelClass = $model::class;
        static::filter($builder, (new $modelClass)->getTable(), $model->getTable());
    }

    /** Also supports query-builder reads, which bypass Eloquent scopes. */
    public static function filter($query, string $table, ?string $qualifier = null)
    {
        $qualifier ??= $table;
        if (! Schema::hasColumn('markers', 'spammed_at')) {
            return $query;
        }

        $spamIds = DB::table('markers')->select('marker_id')->whereNotNull('spammed_at');
        $column = 'marker_id';

        if ($table !== 'incidents' && $table !== 'incident') {
            $spamIds = DB::table('incidents')->select('incident_id')->whereIn('marker_id', $spamIds);
            $column = 'incident_id';
        }

        if (in_array($table, ['progress_investigation_report', 'final_investigation_report'], true)) {
            $spamIds = DB::table('spot_investigation_report')->select('spot_investigation_report_id')
                ->whereIn('incident_id', $spamIds);
            $column = 'spot_investigation_id';
        }

        if ($table === 'final_investigation_report') {
            $spamIds = DB::table('progress_investigation_report')->select('progress_investigation_report_id')
                ->whereIn('spot_investigation_id', $spamIds);
            $column = 'progress_investigation_id';
        }

        // Preserve imported historical records and reports without an incident link.
        return $query->where(function ($visible) use ($qualifier, $column, $spamIds) {
            $visible->whereNull("{$qualifier}.{$column}")->orWhereNotIn("{$qualifier}.{$column}", $spamIds);
        });
    }
}
