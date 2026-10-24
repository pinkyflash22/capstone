<?php

namespace App\Models\Concerns;

use App\Models\Scopes\ExcludeSpamIncidents;

trait ExcludesSpamIncidents
{
    public static function bootExcludesSpamIncidents(): void
    {
        static::addGlobalScope(new ExcludeSpamIncidents);
    }
}
