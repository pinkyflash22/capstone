<?php

namespace App\Http\Controllers\staff;

use Illuminate\Support\Facades\Auth;

class FirefighterV3Controller extends FirefighterV2Controller
{
    public const HYBRID_SECTIONS = [
        'dashboard',
        'assigned',
        'map',
        'schedule',
    ];

    public function shell(?string $section = 'dashboard')
    {
        $section = in_array($section, self::HYBRID_SECTIONS, true) ? $section : 'dashboard';
        $user = Auth::user();

        return view('firefighters.v3-shell', [
            'initialSection' => $section,
            'stationOfficer' => $this->getStationOfficerForUserId($user->user_id),
            'userTeam' => $this->resolveUserTeam($user->user_id),
        ]);
    }
}
