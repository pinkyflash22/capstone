<?php

namespace App\Services;

use App\Models\BoundaryLocation;
use App\Models\City;
use App\Models\Barangay;
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

        if (! $user || ! in_array($role, ['chief', 'stationadmin'], true)) {
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

        $stationCity = $this->stationCityAssignments($user)->first();

        if ($stationCity?->city_id) {
            return Barangay::query()
                ->where('city_id', (int) $stationCity->city_id)
                ->pluck('barangay_id')
                ->map(fn ($barangayId) => (int) $barangayId)
                ->unique()
                ->values();
        }

        return StationBarangayCoverage::query()
            ->whereIn('station_id', $stationIds->all())
            ->where(function ($query) {
                $query->whereNull('is_active')->orWhere('is_active', true);
            })
            ->pluck('barangay_id')
            ->map(fn ($barangayId) => (int) $barangayId)
            ->unique()
            ->values();
    }

    public function assignedCity(User $user): ?City
    {
        $stationOfficer = StationOfficer::query()
            ->with('station.address.barangay.city.province')
            ->where('user_id', $user->getKey())
            ->orderBy('station_officer_id')
            ->first();
        $stationCity = $stationOfficer?->station?->address?->barangay?->city;

        if ($stationCity) {
            return $stationCity;
        }

        $coveredCityIds = Barangay::query()
            ->whereIn('barangay_id', $this->coveredBarangayIds($user)->all())
            ->pluck('city_id')
            ->filter()
            ->map(fn ($cityId) => (int) $cityId)
            ->unique()
            ->values();

        return $coveredCityIds->count() === 1
            ? City::query()->with('province')->find($coveredCityIds->first())
            : null;
    }

    public function assertBarangayInAssignedCity(User $user, int $barangayId): int
    {
        $city = $this->assignedCity($user);
        if (! $city) {
            throw new AuthorizationException('The assigned station has no municipality configured. Update the station address first.');
        }

        $barangayCityId = Barangay::query()->whereKey($barangayId)->value('city_id');
        if ((int) $barangayCityId !== (int) $city->city_id) {
            throw new AuthorizationException('The selected barangay is outside the assigned station municipality.');
        }

        return (int) $city->city_id;
    }

    public function stationForBarangay(User $user, int $barangayId, ?int $requiredStationId = null): int
    {
        $stationIds = $this->stationIds($user);

        $coverage = StationBarangayCoverage::query()
            ->whereIn('station_id', $stationIds->all())
            ->where('barangay_id', $barangayId)
            ->where(function ($query) {
                $query->whereNull('is_active')->orWhere('is_active', true);
            })
            ->when($requiredStationId, fn ($query) => $query->where('station_id', $requiredStationId))
            ->orderByRaw("CASE WHEN coverage_type = 'primary' THEN 0 ELSE 1 END")
            ->orderBy('station_id')
            ->first();

        if (! $coverage) {
            $barangayCityId = Barangay::query()->whereKey($barangayId)->value('city_id');
            $stationAssignment = $this->stationCityAssignments($user)
                ->when(
                    $requiredStationId,
                    fn (Collection $assignments) => $assignments->where('station_id', $requiredStationId)
                )
                ->first(fn ($assignment) => (int) $assignment->city_id === (int) $barangayCityId);

            if (! $stationAssignment) {
                throw new AuthorizationException('This barangay is outside your station\'s assigned municipality.');
            }

            return (int) $stationAssignment->station_id;
        }

        return (int) $coverage->station_id;
    }

    protected function stationCityAssignments(User $user): Collection
    {
        return StationOfficer::query()
            ->join('stations', 'station_officers.station_id', '=', 'stations.station_id')
            ->join('full_addresses', 'stations.address_id', '=', 'full_addresses.address_id')
            ->join('barangays', 'full_addresses.barangay_id', '=', 'barangays.barangay_id')
            ->where('station_officers.user_id', $user->getKey())
            ->whereNotNull('barangays.city_id')
            ->orderBy('station_officers.station_officer_id')
            ->get([
                'station_officers.station_id',
                'barangays.city_id',
            ])
            ->unique('station_id')
            ->values();
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
