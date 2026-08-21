<?php

namespace App\Services;

use App\Models\BoundaryLocation;
use App\Models\StationBarangayCoverage;
use App\Models\StationOfficer;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class BoundaryManagementScope
{
    public function assertManager(?User $user): User
    {
        $role = strtolower(trim((string) ($user?->role?->role ?? '')));

        if (! $user || ! in_array($role, ['chief', 'station admin'], true)) {
            throw new AuthorizationException('Only station administrators and chiefs may manage barangay boundaries.');
        }

        if ($this->stationIds($user)->isEmpty()) {
            throw new AuthorizationException('Your account is not assigned to a fire station.');
        }

        return $user;
    }

    public function stationIds(User $user): Collection
    {
        return StationOfficer::query()
            ->where('user_id', $user->getKey())
            ->pluck('station_id')
            ->filter()
            ->map(fn ($stationId) => (int) $stationId)
            ->unique()
            ->values();
    }

    public function coveredBarangayIds(User $user): Collection
    {
        $stationIds = $this->stationIds($user);

        if ($stationIds->isEmpty()) {
            return collect();
        }

        return StationBarangayCoverage::query()
            ->whereIn('station_id', $stationIds->all())
            ->where('is_active', true)
            ->pluck('barangay_id')
            ->map(fn ($barangayId) => (int) $barangayId)
            ->unique()
            ->values();
    }

    public function stationForBarangay(User $user, int $barangayId, ?int $requiredStationId = null): int
    {
        $stationIds = $this->stationIds($user);

        $coverage = StationBarangayCoverage::query()
            ->whereIn('station_id', $stationIds->all())
            ->where('barangay_id', $barangayId)
            ->where('is_active', true)
            ->when($requiredStationId, fn ($query) => $query->where('station_id', $requiredStationId))
            ->orderByRaw("CASE WHEN coverage_type = 'primary' THEN 0 ELSE 1 END")
            ->orderBy('station_id')
            ->first();

        if (! $coverage) {
            throw new AuthorizationException('This barangay is outside your station\'s assigned coverage.');
        }

        return (int) $coverage->station_id;
    }

    public function stationForBoundary(User $user, BoundaryLocation $boundary): int
    {
        if (! $boundary->barangay_id) {
            throw new AuthorizationException('This legacy boundary is not linked to a barangay and cannot be modified safely.');
        }

        return $this->stationForBarangay(
            $user,
            (int) $boundary->barangay_id,
            $boundary->station_id ? (int) $boundary->station_id : null,
        );
    }
}
