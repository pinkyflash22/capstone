<?php

namespace App\Support;

use Illuminate\Support\Collection;

final class HistoricalFireRecordSemantics
{
    /**
     * Weather-only archive placeholders deliberately store an incident count of
     * zero. A missing count is also not evidence that a fire occurred.
     */
    public static function incidentCountForRow(mixed $row): int
    {
        $value = is_array($row)
            ? ($row['incident_count'] ?? null)
            : ($row->incident_count ?? null);

        return is_numeric($value) ? max(0, (int) $value) : 0;
    }

    public static function incidentCount(iterable $rows): int
    {
        return collect($rows)->sum(fn ($row) => self::incidentCountForRow($row));
    }

    public static function incidentRows(iterable $rows): Collection
    {
        return collect($rows)
            ->filter(fn ($row) => self::incidentCountForRow($row) > 0)
            ->values();
    }
}
