<?php

namespace App\Http\Controllers;

use App\Models\Apparatus;
use App\Models\ApparatusEquipment;
use App\Models\EquipmentToStation;
use App\Models\StationOfficer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ApparatusController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $stationId = $this->managedStationId($request);
        $validated = $this->validated($request, $stationId);
        $apparatus = DB::transaction(function () use ($validated, $stationId) {
            $loadout = $validated['loadout'] ?? [];
            unset($validated['loadout']);
            $apparatus = Apparatus::create($this->normalize($validated) + ['station_id' => $stationId]);
            $this->syncLoadout($apparatus, $loadout);
            return $apparatus;
        });

        return response()->json(['success' => true, 'message' => "Apparatus {$apparatus->unit_number} registered.", 'data' => $apparatus->load($this->relations())], 201);
    }

    public function update(Request $request, Apparatus $apparatus): JsonResponse
    {
        $this->authorizeStation($apparatus);
        $stationId = $this->managedStationId($request, (int) $apparatus->station_id);
        $validated = $this->validated($request, $stationId, $apparatus);
        DB::transaction(function () use ($apparatus, $validated, $stationId) {
            $loadout = $validated['loadout'] ?? [];
            unset($validated['loadout']);
            $apparatus->update($this->normalize($validated, $apparatus) + ['station_id' => $stationId]);
            $this->syncLoadout($apparatus, $loadout);
        });

        return response()->json(['success' => true, 'message' => "Apparatus {$apparatus->unit_number} updated.", 'data' => $apparatus->fresh()->load($this->relations())]);
    }

    public function destroy(Apparatus $apparatus): JsonResponse
    {
        $this->authorizeStation($apparatus);
        abort_if($apparatus->responseUnits()->whereIn('status', ['reserved', 'en_route', 'on_scene'])->exists(), 409, 'An active response is using this apparatus. Recall or release it first.');
        $unitNumber = $apparatus->unit_number;
        $apparatus->delete();

        return response()->json(['success' => true, 'message' => "Apparatus {$unitNumber} removed."]);
    }

    private function validated(Request $request, int $stationId, ?Apparatus $apparatus = null): array
    {
        return $request->validate([
            'equipment_id' => ['required', 'integer', Rule::exists('equipments', 'equipment_id')->where(fn ($query) => $query->where('category', 'vehicles'))],
            'unit_number' => ['required', 'string', 'max:50', Rule::unique('apparatus')->where(fn ($query) => $query->where('station_id', $stationId))->ignore($apparatus?->apparatus_id, 'apparatus_id')],
            'plate_number' => ['nullable', 'string', 'max:30', Rule::unique('apparatus')->ignore($apparatus?->apparatus_id, 'apparatus_id')],
            'apparatus_type' => ['required', Rule::in(['fire_engine', 'pumper', 'tanker', 'ladder', 'rescue', 'ambulance', 'utility'])],
            'water_capacity_liters' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'status' => ['required', Rule::in(['available', 'dispatched', 'out_of_service'])],
            'maintenance_status' => ['required', Rule::in(['operational', 'due', 'in_maintenance'])],
            'last_maintenance_at' => ['nullable', 'date'],
            'next_maintenance_due_at' => ['nullable', 'date', 'after_or_equal:last_maintenance_at'],
            'gps_status' => ['required', Rule::in(['online', 'stale', 'offline', 'not_installed'])],
            'last_lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:last_lng'],
            'last_lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:last_lat'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'loadout' => ['nullable', 'array'],
            'loadout.*.equipment_station_id' => ['required', 'integer', 'distinct', 'exists:equipments_to_stations,equipment_station_id'],
            'loadout.*.quantity' => ['required', 'integer', 'min:0', 'max:10000'],
            'loadout.*.notes' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function syncLoadout(Apparatus $apparatus, array $rows): void
    {
        $requested = collect($rows)->filter(fn ($row) => (int) ($row['quantity'] ?? 0) > 0)
            ->keyBy(fn ($row) => (int) $row['equipment_station_id']);

        foreach ($requested as $assignmentId => $row) {
            $assignment = EquipmentToStation::query()->with('equipment')->lockForUpdate()->findOrFail($assignmentId);
            if ((int) $assignment->station_id !== (int) $apparatus->station_id) {
                throw ValidationException::withMessages(['loadout' => 'Every onboard item must belong to the apparatus station.']);
            }
            if (strtolower((string) $assignment->equipment?->category) === 'vehicles') {
                throw ValidationException::withMessages(['loadout' => 'Vehicles cannot be placed inside an apparatus loadout.']);
            }
            $allocatedElsewhere = (int) ApparatusEquipment::query()
                ->where('equipment_station_id', $assignmentId)
                ->where('apparatus_id', '!=', $apparatus->apparatus_id)
                ->sum('quantity');
            $quantity = (int) $row['quantity'];
            if ($allocatedElsewhere + $quantity > (int) $assignment->available_quantity) {
                throw ValidationException::withMessages([
                    'loadout' => "{$assignment->equipment?->equipment_name} exceeds the station's available quantity.",
                ]);
            }
            $apparatus->loadout()->updateOrCreate(
                ['equipment_station_id' => $assignmentId],
                ['quantity' => $quantity, 'notes' => $row['notes'] ?? null]
            );
        }

        $apparatus->loadout()->when($requested->isNotEmpty(), fn ($query) => $query->whereNotIn('equipment_station_id', $requested->keys()))->delete();
    }

    private function relations(): array
    {
        return ['station', 'equipment', 'loadout.stationAssignment.equipment'];
    }

    private function normalize(array $validated, ?Apparatus $existing = null): array
    {
        $validated['unit_number'] = strtoupper(trim($validated['unit_number']));
        $validated['plate_number'] = filled($validated['plate_number'] ?? null) ? strtoupper(trim($validated['plate_number'])) : null;
        if (isset($validated['last_lat'], $validated['last_lng'])) {
            $validated['gps_last_seen_at'] = now();
        } elseif ($existing?->gps_last_seen_at) {
            $validated['gps_last_seen_at'] = $existing->gps_last_seen_at;
        }
        return $validated;
    }

    private function managedStationId(Request $request, ?int $fallback = null): int
    {
        if ($this->isProvincialAdmin()) {
            return (int) $request->validate(['station_id' => ['required', 'integer', 'exists:stations,station_id']])['station_id'];
        }
        return (int) (StationOfficer::where('user_id', Auth::id())->value('station_id') ?: $fallback ?: abort(403, 'No managed station is assigned to this account.'));
    }

    private function authorizeStation(Apparatus $apparatus): void
    {
        if ($this->isProvincialAdmin()) return;
        $stationId = (int) StationOfficer::where('user_id', Auth::id())->value('station_id');
        abort_unless($stationId && $stationId === (int) $apparatus->station_id, 403, 'You can only modify apparatus assigned to your station.');
    }

    private function isProvincialAdmin(): bool
    {
        return strtolower(trim((string) Auth::user()?->role?->role)) === 'provincial admin';
    }
}
