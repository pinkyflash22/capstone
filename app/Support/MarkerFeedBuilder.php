<?php

namespace App\Support;

use App\Models\Marker;
use App\Models\StationBarangayCoverage;
use App\Models\StationOfficer;
use App\Models\User;
use Illuminate\Support\Collection;

class MarkerFeedBuilder
{
    public static function buildForViewer(?User $viewer = null): Collection
    {
        $visibility = static::visibilityContext($viewer);
        $markers = Marker::with([
            'stations.address.barangay.city',
            'stations.stationOfficers.user.role',
            'barangayHall.address.barangay.city',
            'barangayHall.barangay.city',
            'hydrants.station',
            'incidents',
            'riskAreas.barangayHall.barangay.city',
            'riskAreas.reporter',
            'riskAreas.photos',
        ])->get();

        return $markers
            ->map(fn (Marker $marker) => static::serializeMarker($marker, $viewer, $visibility))
            ->filter()
            ->values();
    }

    protected static function serializeMarker(Marker $marker, ?User $viewer = null, array $visibility = []): ?array
    {
        $riskArea = static::resolveCurrentRiskArea($marker);

        if (! static::markerIsVisible($marker, $riskArea, $viewer, $visibility)) {
            return null;
        }

        $station = $marker->stations->first();
        $hydrant = $marker->hydrants->first();
        $incident = $marker->incidents
            ->sortByDesc(fn ($item) => implode('|', [
                optional($item->updated_at)->timestamp ?? 0,
                optional($item->created_at)->timestamp ?? 0,
                (int) ($item->incident_id ?? 0),
            ]))
            ->first();

        return [
            'marker_id' => $marker->marker_id,
            'title' => $marker->title,
            'description' => $marker->description,
            'lat' => $marker->lat,
            'lng' => $marker->lng,
            'category' => $marker->category,
            'status' => $marker->status,
            'image' => $marker->image,
            'station' => $station ? [
                'station_name' => static::publicValue($station->station_name),
                'contact_number' => static::publicValue($station->contact_number),
            ] : null,
            'station_public' => $station ? [
                'name' => static::publicValue($station->station_name),
                'address' => static::formatFullAddress($station->address),
                'contact_number' => static::publicValue($station->contact_number),
                'operating_hours' => static::publicValue($station->operating_hours ?? $station->office_hours ?? null),
                'chief_name' => static::resolveStationChiefName($station),
            ] : null,
            'hydrant' => $hydrant ? [
                'hydrant_code' => static::publicValue($hydrant->hydrant_code),
                'type' => static::publicValue($hydrant->type),
            ] : null,
            'hydrant_public' => $hydrant ? [
                'code' => static::publicValue($hydrant->hydrant_code),
                'type' => static::publicValue($hydrant->type),
                'station_name' => static::publicValue($hydrant->station?->station_name),
            ] : null,
            'incident_public' => $incident ? [
                'id' => $incident->incident_id,
                'status' => static::publicValue($incident->status),
                'created_at' => $incident->created_at,
                'updated_at' => $incident->updated_at,
            ] : null,
            'barangay_public' => $marker->barangayHall ? [
                'name' => static::publicValue($marker->barangayHall->hall_name)
                    ?: static::publicValue($marker->barangayHall->barangay?->barangay_name),
                'address' => static::formatFullAddress($marker->barangayHall->address),
                'contact_number' => static::publicValue($marker->barangayHall->contact_number),
                'office_hours' => static::publicValue($marker->barangayHall->office_hours ?? $marker->barangayHall->operating_hours ?? null),
                'barangay_name' => static::publicValue($marker->barangayHall->barangay?->barangay_name),
            ] : null,
            'risk_area_public' => $riskArea ? [
                'risk_area_id' => $riskArea->risk_area_id,
                'entry_type' => static::publicValue($riskArea->entry_type),
                'review_status' => static::publicValue($riskArea->review_status ?? 'pending'),
                'review_type' => static::publicValue($riskArea->review_type ?? 'create'),
                'status' => static::publicValue($riskArea->status),
                'condition_label' => static::publicValue($riskArea->condition_label),
                'assessment_level' => static::publicValue($riskArea->assessment_level),
                'barangay_name' => static::publicValue($riskArea->barangayHall?->barangay?->barangay_name),
                'reported_by_me' => $viewer?->user_id && (int) $viewer->user_id === (int) ($riskArea->reported_by ?? 0),
                'primary_photo_url' => static::riskAreaPrimaryPhotoUrl($riskArea),
                'photos' => $riskArea->photos
                    ->map(fn ($photo) => [
                        'risk_area_photo_id' => $photo->risk_area_photo_id,
                        'photo_name' => static::publicValue($photo->photo_name),
                        'photo_url' => MediaPath::url($photo->photo_path),
                    ])
                    ->filter(fn ($photo) => ! empty($photo['photo_url']))
                    ->values()
                    ->all(),
            ] : null,
            'created_at' => $marker->created_at,
            'updated_at' => collect([$marker->updated_at, $riskArea?->updated_at, $incident?->updated_at])
                ->filter()
                ->sortByDesc(fn ($date) => $date->timestamp)
                ->first(),
        ];
    }

    protected static function resolveCurrentRiskArea(Marker $marker)
    {
        return $marker->riskAreas
            ->sortByDesc(function ($riskArea) {
                return implode('|', [
                    optional($riskArea->updated_at)->timestamp ?? 0,
                    optional($riskArea->created_at)->timestamp ?? 0,
                    (int) ($riskArea->risk_area_id ?? 0),
                ]);
            })
            ->first();
    }

    protected static function markerIsVisible(Marker $marker, $riskArea, ?User $viewer = null, array $visibility = []): bool
    {
        $category = strtolower(trim((string) ($marker->category ?? '')));

        if (! in_array($category, ['risk', 'hazard'], true)) {
            return strtolower(trim((string) ($marker->status ?? 'active'))) !== 'inactive';
        }

        if (! $riskArea) {
            return false;
        }

        $reviewStatus = strtolower(trim((string) ($riskArea->review_status ?? 'pending')));
        $reviewType = strtolower(trim((string) ($riskArea->review_type ?? 'create')));

        if ($reviewStatus === 'rejected') {
            return false;
        }

        if ($reviewType === 'delete' && $reviewStatus === 'approved') {
            return false;
        }

        if ($reviewStatus === 'approved') {
            return true;
        }

        $viewerId = (int) ($visibility['viewer_id'] ?? $viewer?->user_id ?? 0);
        if ($viewerId > 0 && $viewerId === (int) ($riskArea->reported_by ?? 0)) {
            return true;
        }

        $role = (string) ($visibility['role'] ?? '');
        if (in_array($role, ['admin', 'provincial admin'], true)) {
            return true;
        }

        $riskStationId = (int) ($riskArea->station_id ?? 0);
        $riskBarangayId = (int) ($riskArea->barangayHall?->barangay_id ?? 0);

        if (in_array($role, ['chief', 'station admin', 'firefighter'], true)) {
            return ($riskStationId > 0 && in_array($riskStationId, $visibility['station_ids'] ?? [], true))
                || ($riskBarangayId > 0 && in_array($riskBarangayId, $visibility['covered_barangay_ids'] ?? [], true));
        }

        if (in_array($role, ['barangay', 'barangay captain', 'barangay officer'], true)) {
            return $riskBarangayId > 0
                && in_array($riskBarangayId, $visibility['barangay_ids'] ?? [], true);
        }

        return false;
    }

    protected static function visibilityContext(?User $viewer): array
    {
        if (! $viewer) {
            return [
                'viewer_id' => 0,
                'role' => '',
                'station_ids' => [],
                'covered_barangay_ids' => [],
                'barangay_ids' => [],
            ];
        }

        $viewer->loadMissing([
            'role',
            'address.barangay',
            'barangayOfficer.barangay',
            'captainedBarangayHall.barangay',
        ]);

        $stationIds = StationOfficer::query()
            ->where('user_id', $viewer->user_id)
            ->pluck('station_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $coveredBarangayIds = $stationIds->isEmpty()
            ? collect()
            : StationBarangayCoverage::query()
                ->whereIn('station_id', $stationIds->all())
                ->where('is_active', true)
                ->pluck('barangay_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

        $barangayIds = collect([
            $viewer->address?->barangay_id,
            $viewer->barangayOfficer?->barangay_id,
            $viewer->captainedBarangayHall?->barangay_id,
        ])
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return [
            'viewer_id' => (int) $viewer->user_id,
            'role' => strtolower(trim((string) ($viewer->role?->role ?? ''))),
            'station_ids' => $stationIds->all(),
            'covered_barangay_ids' => $coveredBarangayIds->all(),
            'barangay_ids' => $barangayIds->all(),
        ];
    }

    protected static function riskAreaPrimaryPhotoUrl($riskArea): ?string
    {
        $photo = $riskArea?->photos?->first();

        return $photo ? MediaPath::url($photo->photo_path) : null;
    }

    protected static function publicValue(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }

    protected static function formatFullAddress($address): ?string
    {
        if (! $address) {
            return null;
        }

        $parts = array_filter([
            static::publicValue($address->street ?? null),
            static::publicValue($address->barangay?->barangay_name ?? null),
            static::publicValue($address->barangay?->city?->city_name ?? null),
        ]);

        return $parts ? implode(', ', $parts) : null;
    }

    protected static function publicDisplayName($user): ?string
    {
        if (! $user) {
            return null;
        }

        $name = trim(implode(' ', array_filter([
            static::publicValue($user->first_name ?? null),
            static::publicValue($user->last_name ?? null),
        ])));

        return $name !== '' ? $name : static::publicValue($user->name ?? null);
    }

    protected static function resolveStationChiefName($station): ?string
    {
        if (! $station?->stationOfficers) {
            return null;
        }

        $chiefOfficer = $station->stationOfficers
            ->filter(function ($officer) {
                $role = strtolower((string) ($officer->user?->role?->role ?? ''));
                $rank = strtolower((string) ($officer->rank ?? ''));
                $status = strtolower((string) ($officer->status ?? ''));

                $isChief = $role === 'chief' || str_contains($rank, 'chief');
                $isActive = $status === '' || in_array($status, ['active', 'available', 'on duty', 'on-duty'], true);

                return $isChief && $isActive;
            })
            ->sortByDesc(function ($officer) {
                return strtolower((string) ($officer->user?->role?->role ?? '')) === 'chief' ? 1 : 0;
            })
            ->first();

        return static::publicDisplayName($chiefOfficer?->user);
    }
}
