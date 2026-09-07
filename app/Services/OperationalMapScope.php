<?php

namespace App\Services;

use App\Models\BarangayOfficer;
use App\Models\Role;
use App\Models\StationBarangayCoverage;
use App\Models\StationOfficer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OperationalMapScope
{
    public function role(?User $user): string
    {
        return strtolower(trim((string) (
            $user?->role?->role
            ?? Role::roleNameForId((int) ($user?->role_id ?? 0))
            ?? ''
        )));
    }

    public function hasProvinceWideAccess(?User $user): bool
    {
        return in_array($this->role($user), ['admin', 'provincial admin'], true);
    }

    public function isStationResponder(?User $user): bool
    {
        return in_array($this->role($user), ['chief', 'station admin', 'chief station admin', 'firefighter'], true);
    }

    public function stationIds(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        return StationOfficer::query()
            ->where('user_id', $user->getKey())
            ->pluck('station_id')
            ->filter()
            ->map(fn ($stationId) => (int) $stationId)
            ->unique()
            ->values();
    }

    public function primaryStationId(?User $user): ?int
    {
        return $this->stationIds($user)->first();
    }

    public function coveredBarangayIds(?User $user): Collection
    {
        $stationIds = $this->stationIds($user);
        if ($stationIds->isEmpty()) {
            return collect();
        }

        return StationBarangayCoverage::query()
            ->whereIn('station_id', $stationIds->all())
            ->where(function ($query) {
                $query->whereNull('is_active')->orWhere('is_active', true);
            })
            ->pluck('barangay_id')
            ->filter()
            ->map(fn ($barangayId) => (int) $barangayId)
            ->unique()
            ->values();
    }

    public function barangayIds(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        $assignedIds = BarangayOfficer::query()
            ->where('user_id', $user->getKey())
            ->pluck('barangay_id')
            ->push($user->address?->barangay_id);

        return $assignedIds->filter()
            ->map(fn ($barangayId) => (int) $barangayId)
            ->unique()
            ->values();
    }

    public function authorizeRequestedStation(?User $user, ?int $requestedStationId = null): ?int
    {
        if (! $user) {
            return $requestedStationId;
        }

        if ($this->hasProvinceWideAccess($user)) {
            return $requestedStationId;
        }

        if (! $this->isStationResponder($user)) {
            $barangayIds = $this->barangayIds($user);
            if ($barangayIds->isEmpty()) {
                return null;
            }

            $stationIds = StationBarangayCoverage::query()
                ->whereIn('barangay_id', $barangayIds->all())
                ->where(function ($query) {
                    $query->whereNull('is_active')->orWhere('is_active', true);
                })
                ->pluck('station_id')
                ->filter()
                ->map(fn ($stationId) => (int) $stationId)
                ->unique()
                ->values();
            abort_if($stationIds->isEmpty(), 403, 'Your barangay is not assigned to a station coverage area.');
            abort_if($requestedStationId && ! $stationIds->contains($requestedStationId), 403, 'That station is outside your barangay coverage.');

            return (int) $stationIds->first();
        }

        $stationIds = $this->stationIds($user);
        abort_if($stationIds->isEmpty(), 403, 'Your account is not assigned to a station.');
        abort_if($requestedStationId && ! $stationIds->contains($requestedStationId), 403, 'That station is outside your assignment.');

        return (int) $stationIds->first();
    }

    public function constrainIncidentQuery(Builder $query, ?User $user): Builder
    {
        if (! $user || $this->hasProvinceWideAccess($user)) {
            return $query;
        }

        if ($this->isStationResponder($user)) {
            $stationIds = $this->stationIds($user);

            return $stationIds->isEmpty()
                ? $query->whereRaw('1 = 0')
                : $query->where(function ($scope) use ($stationIds) {
                    $scope->whereIn('station_id', $stationIds->all())
                        ->orWhereHas('team', fn ($teams) => $teams->whereIn('station_id', $stationIds->all()));
                });
        }

        $barangayIds = $this->barangayIds($user);
        if ($barangayIds->isNotEmpty()) {
            return $query->whereIn('barangay_id', $barangayIds->all());
        }

        return $query->where('reported_by', $user->getKey());
    }
}
