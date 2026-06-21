<?php

namespace App\Services;

use App\Models\BoundaryLocation;
use Illuminate\Support\Collection;

class BoundaryMatcherService
{
    public function locateBarangayBoundary(float $lat, float $lng): ?BoundaryLocation
    {
        /** @var Collection<int, BoundaryLocation> $boundaries */
        $boundaries = BoundaryLocation::query()
            ->with(['barangay', 'station'])
            ->where('boundary_type', 'barangay')
            ->whereNotNull('barangay_id')
            ->get();

        foreach ($boundaries as $boundary) {
            if ($this->containsPoint($boundary->geojson, $lat, $lng)) {
                return $boundary;
            }
        }

        return null;
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
}
