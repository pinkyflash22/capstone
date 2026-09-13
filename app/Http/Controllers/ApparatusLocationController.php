<?php

namespace App\Http\Controllers;

use App\Models\IncidentResponseUnit;
use App\Models\StationOfficer;
use App\Services\IncidentResourceDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApparatusLocationController extends Controller
{
    public function update(Request $request, IncidentResponseUnit $unit, IncidentResourceDispatchService $resources): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'speed_kph' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
        ]);

        $officer = StationOfficer::query()->where('user_id', Auth::id())->firstOrFail();
        $unit->loadMissing(['team', 'apparatus']);
        abort_unless($unit->team && (int) $unit->team->commander_driver === (int) $officer->station_officer_id, 403, 'Only the assigned Commander / Driver can publish this apparatus location.');
        abort_unless($unit->apparatus && (int) $unit->station_id === (int) $officer->station_id && (int) $unit->apparatus->station_id === (int) $officer->station_id, 403, 'This apparatus is outside your assigned station.');

        $updated = $resources->updateApparatusTelemetry($unit, $validated);

        return response()->json([
            'success' => true,
            'message' => $updated->status === 'on_scene'
                ? 'Arrival detected. Incident updated to On Scene.'
                : 'Apparatus location updated.',
            'unit' => $resources->serializeUnit($updated),
        ]);
    }
}
