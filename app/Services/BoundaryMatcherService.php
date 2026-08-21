<?php

namespace App\Services;

use App\Models\BoundaryLocation;
use App\Models\BarangayHall;
use Illuminate\Support\Collection;

class BoundaryMatcherService
{
    public function locateBarangayHall(float $lat, float $lng): ?BarangayHall
    {
        $boundary = $this->locateBarangayBoundary($lat, $lng);

        if (! $boundary?->barangay_id) {
            return null;
        }

        return BarangayHall::query()
            ->with('barangay')
            ->where('barangay_id', $boundary->barangay_id)
            ->first();
    }

    public function locateBarangayBoundary(float $lat, float $lng): ?BoundaryLocation
    {
        /** @var Collection<int, BoundaryLocation> $boundaries */
        $boundaries = BoundaryLocation::query()
            ->with(['barangay', 'station'])
            ->where('boundary_type', 'barangay')
            ->whereNotNull('barangay_id')
            ->get();

        $matches = $boundaries
            ->filter(fn (BoundaryLocation $boundary) => $this->containsPoint($boundary->geojson, $lat, $lng))
            ->values();

        if ($matches->isEmpty()) {
            return null;
        }

        if ($matches->count() === 1) {
            return $matches->first();
        }

        return $matches
            ->sortBy(fn (BoundaryLocation $boundary) => $this->distanceToBoundaryReferencePoint($boundary, $lat, $lng))
            ->first();
    }

    public function containsPoint(array|string|null $geojson, float $lat, float $lng): bool
    {
        if (is_string($geojson)) {
            $geojson = json_decode($geojson, true);
        }

        if (! is_array($geojson)) {
            return false;
        }

        $type = $geojson['type'] ?? null;

        if ($type === 'FeatureCollection') {
            foreach ($geojson['features'] ?? [] as $feature) {
                if ($this->containsPoint($feature, $lat, $lng)) {
                    return true;
                }
            }

            return false;
        }

        if ($type === 'Feature') {
            return $this->containsPoint($geojson['geometry'] ?? null, $lat, $lng);
        }

        if ($type === 'Polygon') {
            return $this->polygonContains($geojson['coordinates'] ?? [], $lat, $lng);
        }

        if ($type === 'MultiPolygon') {
            foreach ($geojson['coordinates'] ?? [] as $polygon) {
                if ($this->polygonContains($polygon, $lat, $lng)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function polygonContains(array $rings, float $lat, float $lng): bool
    {
        if (empty($rings)) {
            return false;
        }

        $insideOuter = $this->ringContains($rings[0], $lat, $lng);
        if (! $insideOuter) {
            return false;
        }

        foreach (array_slice($rings, 1) as $hole) {
            if ($this->ringContains($hole, $lat, $lng)) {
                return false;
            }
        }

        return true;
    }

    protected function ringContains(array $ring, float $lat, float $lng): bool
    {
        $inside = false;
        $pointCount = count($ring);

        if ($pointCount < 3) {
            return false;
        }

        for ($i = 0, $j = $pointCount - 1; $i < $pointCount; $j = $i++) {
            $current = $ring[$i] ?? null;
            $previous = $ring[$j] ?? null;

            if (! is_array($current) || ! is_array($previous) || count($current) < 2 || count($previous) < 2) {
                continue;
            }

            [$currentLng, $currentLat] = $current;
            [$previousLng, $previousLat] = $previous;

            $intersects = (($currentLat > $lat) !== ($previousLat > $lat))
                && ($lng < (($previousLng - $currentLng) * ($lat - $currentLat) / (($previousLat - $currentLat) ?: 1e-12)) + $currentLng);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    protected function distanceToBoundaryReferencePoint(BoundaryLocation $boundary, float $lat, float $lng): float
    {
        $referencePoint = $this->extractBoundaryReferencePoint($boundary->geojson);

        if (! $referencePoint) {
            return INF;
        }

        return $this->distanceMeters($lat, $lng, $referencePoint['lat'], $referencePoint['lng']);
    }

    protected function extractBoundaryReferencePoint(array|string|null $geojson): ?array
    {
        if (is_string($geojson)) {
            $geojson = json_decode($geojson, true);
        }

        if (! is_array($geojson)) {
            return null;
        }

        $points = $this->flattenBoundaryCoordinates($geojson);

        if (empty($points)) {
            return null;
        }

        $latSum = 0.0;
        $lngSum = 0.0;

        foreach ($points as $point) {
            $latSum += $point['lat'];
            $lngSum += $point['lng'];
        }

        return [
            'lat' => $latSum / count($points),
            'lng' => $lngSum / count($points),
        ];
    }

    protected function flattenBoundaryCoordinates(array $geojson): array
    {
        $type = $geojson['type'] ?? null;

        if ($type === 'FeatureCollection') {
            return collect($geojson['features'] ?? [])
                ->flatMap(fn ($feature) => $this->flattenBoundaryCoordinates(is_array($feature) ? $feature : []))
                ->values()
                ->all();
        }

        if ($type === 'Feature') {
            return $this->flattenBoundaryCoordinates(is_array($geojson['geometry'] ?? null) ? $geojson['geometry'] : []);
        }

        if ($type === 'Polygon') {
            return $this->flattenPolygonCoordinates($geojson['coordinates'] ?? []);
        }

        if ($type === 'MultiPolygon') {
            return collect($geojson['coordinates'] ?? [])
                ->flatMap(fn ($polygon) => $this->flattenPolygonCoordinates(is_array($polygon) ? $polygon : []))
                ->values()
                ->all();
        }

        return [];
    }

    protected function flattenPolygonCoordinates(array $rings): array
    {
        return collect($rings)
            ->flatMap(function ($ring) {
                if (! is_array($ring)) {
                    return [];
                }

                return collect($ring)
                    ->filter(fn ($point) => is_array($point) && count($point) >= 2)
                    ->map(fn ($point) => [
                        'lng' => (float) $point[0],
                        'lat' => (float) $point[1],
                    ])
                    ->values()
                    ->all();
            })
            ->values()
            ->all();
    }

    protected function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
