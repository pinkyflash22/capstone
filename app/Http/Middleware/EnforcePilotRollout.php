<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\StationOfficer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePilotRollout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('operations.rollout.mode') !== 'pilot' || ! $request->user()) {
            return $next($request);
        }

        $role = Role::roleNameForId((int) $request->user()->role_id);
        if (! in_array($role, ['firefighter', 'chief', 'stationadmin'], true)) {
            return $next($request);
        }

        $pilotStationIds = array_map('intval', config('operations.rollout.pilot_station_ids', []));
        $stationId = StationOfficer::query()
            ->where('user_id', $request->user()->user_id)
            ->value('station_id');

        abort_unless(
            $stationId && in_array((int) $stationId, $pilotStationIds, true),
            503,
            'This station is not in the current rollout wave.'
        );

        return $next($request);
    }
}
