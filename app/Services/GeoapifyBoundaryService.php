<?php

namespace App\Services;

use App\Exceptions\BoundaryUnavailableException;
use App\Models\Barangay;
use App\Models\BoundaryLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class GeoapifyBoundaryService
{
    public function syncBarangayBoundary(
        int $barangayId,
        ?int $coverageStationId = null,
        ?int $importedBy = null,
        string $geometry = 'geometry_1000',
        bool $force = false,
    ): BoundaryLocation {
        $existing = BoundaryLocation::query()
            ->where('barangay_id', $barangayId)
            ->where('boundary_type', 'barangay')
            ->first();

        if ($existing && ! $force && $existing->source === 'geoapify' && ! empty($existing->geojson)) {
            return $existing;
        }

        $barangay = Barangay::query()
            ->with(['hall.marker', 'city.province'])
            ->findOrFail($barangayId);
        $marker = $barangay->hall?->marker;
        if (! $marker || $marker->lat === null || $marker->lng === null) {
            throw new BoundaryUnavailableException('The barangay does not have a hall marker with coordinates.');
        }

        $payload = $this->importBarangayBoundary(
            $barangay,
            (float) $marker->lat,
            (float) $marker->lng,
            $geometry,
        );
        $feature = (array) ($payload['feature'] ?? []);
        $properties = (array) ($feature['properties'] ?? []);

        return BoundaryLocation::query()->updateOrCreate(
            ['barangay_id' => $barangayId, 'boundary_type' => 'barangay'],
            [
                'name' => $barangay->barangay_name,
                'station_id' => $coverageStationId ?? $existing?->station_id,
                'geojson' => $payload['feature_collection'],
                'source' => 'geoapify',
                'external_place_id' => $properties['place_id'] ?? $properties['id'] ?? null,
                'properties' => [
                    'geoapify_feature' => $properties,
                    'query' => $this->boundaryQueryComponents($barangay),
                    'coverage_station_id' => $coverageStationId,
                    'imported_at' => now()->toISOString(),
                    'imported_by' => $importedBy,
                ],
            ]
        );
    }

    public function importBarangayBoundary(Barangay $barangay, float $lat, float $lng, string $geometry = 'geometry_1000'): array
    {
        $barangay->loadMissing('city.province');

        $apiKey = trim((string) config('services.geoapify.key'));

        if ($apiKey === '') {
            throw new RuntimeException('Geoapify is not configured.');
        }

        $geometryFallbacks = collect([$geometry, 'geometry_5000', 'geometry_10000'])
            ->filter()
            ->unique()
            ->values();

        $attemptSummaries = [];

        foreach ($geometryFallbacks as $geometryMode) {
            $payload = $this->fetchBoundaryPayload($lat, $lng, $geometryMode, $apiKey);
            $features = collect($payload['features'] ?? []);

            if ($features->isEmpty()) {
                $attemptSummaries[] = [
                    'geometry' => $geometryMode,
                    'feature_count' => 0,
                    'features' => [],
                ];

                continue;
            }

            $selectedFeature = $this->selectBestBarangayFeature($barangay, $features->all());

            $attemptSummaries[] = [
                'geometry' => $geometryMode,
                'feature_count' => $features->count(),
                'features' => $this->summarizeFeatures($features->all()),
                'selected_name' => data_get($selectedFeature, 'properties.name'),
                'selected_type' => data_get($selectedFeature, 'geometry.type'),
            ];

            if ($selectedFeature) {
                return [
                    'feature' => $selectedFeature,
                    'feature_collection' => [
                        'type' => 'FeatureCollection',
                        'features' => [$selectedFeature],
                    ],
                ];
            }

            $parentPlaceId = $this->extractMunicipalityPlaceId($features->all(), $cityNeedle = $this->normalizeName((string) ($barangay->city?->city_name ?? '')));

            if ($parentPlaceId) {
                foreach ([null, 1, 2, 3] as $sublevel) {
                    $childPayload = $this->fetchContainsPayload($parentPlaceId, $geometryMode, $apiKey, $sublevel);
                    $childFeatures = collect($childPayload['features'] ?? []);

                    if ($childFeatures->isEmpty()) {
                        $attemptSummaries[] = [
                            'geometry' => $geometryMode,
                            'mode' => 'consists-of',
                            'sublevel' => $sublevel,
                            'feature_count' => 0,
                            'features' => [],
                        ];

                        continue;
                    }

                    $selectedChildFeature = $this->selectBestBarangayFeature($barangay, $childFeatures->all());

                    $attemptSummaries[] = [
                        'geometry' => $geometryMode,
                        'mode' => 'consists-of',
                        'sublevel' => $sublevel,
                        'feature_count' => $childFeatures->count(),
                        'features' => $this->summarizeFeatures($childFeatures->all()),
                        'selected_name' => data_get($selectedChildFeature, 'properties.name'),
                        'selected_type' => data_get($selectedChildFeature, 'geometry.type'),
                    ];

                    if ($selectedChildFeature) {
                        return [
                            'feature' => $selectedChildFeature,
                            'feature_collection' => [
                                'type' => 'FeatureCollection',
                                'features' => [$selectedChildFeature],
                            ],
                        ];
                    }
                }
            }
        }

        $textQuery = $this->buildBarangaySearchText($barangay);
        $textPlaceId = $this->searchPlaceIdByText($textQuery, $apiKey, $attemptSummaries, $lat, $lng);

        if ($textPlaceId) {
            foreach ($geometryFallbacks as $geometryMode) {
                $textPayload = $this->fetchBoundaryPayloadByPlaceId($textPlaceId, $geometryMode, $apiKey);
                $textFeatures = collect($textPayload['features'] ?? []);

                if ($textFeatures->isNotEmpty()) {
                    $selectedTextFeature = $this->selectBestBarangayFeature($barangay, $textFeatures->all());

                    $attemptSummaries[] = [
                        'geometry' => $geometryMode,
                        'mode' => 'text-part-of',
                        'feature_count' => $textFeatures->count(),
                        'features' => $this->summarizeFeatures($textFeatures->all()),
                        'selected_name' => data_get($selectedTextFeature, 'properties.name'),
                        'selected_type' => data_get($selectedTextFeature, 'geometry.type'),
                    ];

                    if ($selectedTextFeature) {
                        return [
                            'feature' => $selectedTextFeature,
                            'feature_collection' => [
                                'type' => 'FeatureCollection',
                                'features' => [$selectedTextFeature],
                            ],
                        ];
                    }
                }

                foreach ([null, 1, 2, 3] as $sublevel) {
                    $childPayload = $this->fetchContainsPayload($textPlaceId, $geometryMode, $apiKey, $sublevel);
                    $childFeatures = collect($childPayload['features'] ?? []);

                    if ($childFeatures->isEmpty()) {
                        $attemptSummaries[] = [
                            'geometry' => $geometryMode,
                            'mode' => 'text-consists-of',
                            'sublevel' => $sublevel,
                            'feature_count' => 0,
                            'features' => [],
                        ];

                        continue;
                    }

                    $selectedChildFeature = $this->selectBestBarangayFeature($barangay, $childFeatures->all());

                    $attemptSummaries[] = [
                        'geometry' => $geometryMode,
                        'mode' => 'text-consists-of',
                        'sublevel' => $sublevel,
                        'feature_count' => $childFeatures->count(),
                        'features' => $this->summarizeFeatures($childFeatures->all()),
                        'selected_name' => data_get($selectedChildFeature, 'properties.name'),
                        'selected_type' => data_get($selectedChildFeature, 'geometry.type'),
                    ];

                    if ($selectedChildFeature) {
                        return [
                            'feature' => $selectedChildFeature,
                            'feature_collection' => [
                                'type' => 'FeatureCollection',
                                'features' => [$selectedChildFeature],
                            ],
                        ];
                    }
                }
            }
        }

        Log::warning('Geoapify boundary sync did not find a usable barangay polygon.', [
            'barangay_id' => $barangay->barangay_id,
            'barangay_name' => $barangay->barangay_name,
            'city_name' => $barangay->city?->city_name,
            'lat' => $lat,
            'lng' => $lng,
            'attempts' => $attemptSummaries,
        ]);

        throw new BoundaryUnavailableException(
            'Geoapify did not return a barangay-level boundary for ' . $barangay->barangay_name . '. ' .
            'Only higher-level boundaries were available, so please draw or edit this barangay manually.'
        );
    }

    protected function fetchBoundaryPayload(float $lat, float $lng, string $geometry, string $apiKey): array
    {
        return $this->geoapifyGet(
            'https://api.geoapify.com/v1/boundaries/part-of',
            [
                'lat' => $lat,
                'lon' => $lng,
                'boundaries' => 'administrative',
                'geometry' => $geometry,
                'apiKey' => $apiKey,
            ],
            'Geoapify boundary request'
        );
    }

    protected function fetchBoundaryPayloadByPlaceId(string $placeId, string $geometry, string $apiKey): array
    {
        return $this->geoapifyGet(
            'https://api.geoapify.com/v1/boundaries/part-of',
            [
                'id' => $placeId,
                'boundaries' => 'administrative',
                'geometry' => $geometry,
                'apiKey' => $apiKey,
            ],
            'Geoapify boundary place-id request'
        );
    }

    protected function fetchContainsPayload(string $placeId, string $geometry, string $apiKey, ?int $sublevel = null): array
    {
        $query = [
            'id' => $placeId,
            'boundary' => 'administrative',
            'geometry' => $geometry,
            'apiKey' => $apiKey,
        ];

        if ($sublevel !== null) {
            $query['sublevel'] = $sublevel;
        }

        return $this->geoapifyGet(
            'https://api.geoapify.com/v1/boundaries/consists-of',
            $query,
            'Geoapify consists-of request'
        );
    }

    protected function selectBestBarangayFeature(Barangay $barangay, array $features): ?array
    {
        $needle = $this->normalizeName($barangay->barangay_name);
        $cityNeedle = $this->normalizeName((string) ($barangay->city?->city_name ?? ''));

        $scored = collect($features)
            ->filter(function ($feature) {
                return in_array($feature['geometry']['type'] ?? null, ['Polygon', 'MultiPolygon'], true);
            })
            ->map(function ($feature) use ($needle, $cityNeedle) {
                $properties = (array) ($feature['properties'] ?? []);
                $candidates = array_filter([
                    $properties['name'] ?? null,
                    $properties['formatted'] ?? null,
                    $properties['address_line1'] ?? null,
                    $properties['address_line2'] ?? null,
                    $properties['suburb'] ?? null,
                    $properties['district'] ?? null,
                    $properties['city_district'] ?? null,
                    $properties['county'] ?? null,
                ]);

                $score = 0;
                $barangayMatched = false;
                $normalizedName = $this->normalizeName((string) ($properties['name'] ?? ''));

                foreach ($candidates as $candidate) {
                    $normalized = $this->normalizeName((string) $candidate);

                    if ($normalized === '') {
                        continue;
                    }

                    if ($normalized === $needle) {
                        $score = max($score, 100);
                        $barangayMatched = true;
                    } elseif (Str::contains($normalized, $needle) || Str::contains($needle, $normalized)) {
                        $score = max($score, 75);
                        $barangayMatched = true;
                    } elseif ($this->sharesBarangayToken($needle, $normalized)) {
                        $score = max($score, 50);
                        $barangayMatched = true;
                    }
                }

                $looksLikeMunicipality = $this->isMunicipalityLevelMatch($properties, $cityNeedle);

                if ($looksLikeMunicipality && ! $barangayMatched) {
                    $score = -100;
                }

                if ($cityNeedle !== '') {
                    $cityCandidates = array_filter([
                        $properties['city'] ?? null,
                        $properties['county'] ?? null,
                        $properties['state_district'] ?? null,
                        $properties['formatted'] ?? null,
                    ]);

                    foreach ($cityCandidates as $cityCandidate) {
                        $normalizedCity = $this->normalizeName((string) $cityCandidate);

                        if ($normalizedCity !== '' && (Str::contains($normalizedCity, $cityNeedle) || Str::contains($cityNeedle, $normalizedCity))) {
                            $score += 10;
                            break;
                        }
                    }
                }

                return [
                    'feature' => $feature,
                    'score' => $score,
                    'name' => $normalizedName,
                    'area' => $this->estimateGeometryArea($feature['geometry'] ?? []),
                    'barangay_matched' => $barangayMatched,
                    'looks_like_municipality' => $looksLikeMunicipality,
                ];
            })
            ->sort(function (array $a, array $b) {
                if (($a['score'] ?? 0) !== ($b['score'] ?? 0)) {
                    return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
                }

                return ($a['area'] ?? INF) <=> ($b['area'] ?? INF);
            })
            ->values();

        $best = $scored->first();

        if (! $best) {
            return null;
        }

        if (! ($best['barangay_matched'] ?? false)) {
            return null;
        }

        if (($best['score'] ?? 0) < 50) {
            $smallestLocalPolygon = $scored
                ->filter(fn (array $item) => ($item['area'] ?? 0) > 0 && ($item['barangay_matched'] ?? false))
                ->sortBy('area')
                ->first();

            return $smallestLocalPolygon['feature'] ?? $best['feature'] ?? null;
        }

        return $best['feature'];
    }

    protected function summarizeFeatures(array $features): array
    {
        return collect($features)
            ->take(8)
            ->map(function (array $feature) {
                $properties = (array) ($feature['properties'] ?? []);

                return [
                    'name' => $properties['name'] ?? null,
                    'formatted' => $properties['formatted'] ?? null,
                    'place_id' => $properties['place_id'] ?? ($properties['id'] ?? null),
                    'result_type' => $properties['result_type'] ?? null,
                    'rank' => $properties['rank'] ?? null,
                    'place' => $properties['place'] ?? null,
                    'category' => $properties['category'] ?? null,
                    'city' => $properties['city'] ?? null,
                    'county' => $properties['county'] ?? null,
                    'geometry_type' => $feature['geometry']['type'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    protected function searchPlaceIdByText(
        string $textQuery,
        string $apiKey,
        array &$attemptSummaries,
        ?float $lat = null,
        ?float $lng = null,
    ): ?string
    {
        $countryCode = strtolower(trim((string) config('services.geoapify.country_code', 'ph')));
        $query = [
            'text' => $textQuery,
            'format' => 'json',
            'limit' => 5,
            'filter' => 'countrycode:' . ($countryCode ?: 'ph'),
            'apiKey' => $apiKey,
        ];
        if ($lat !== null && $lng !== null) {
            $query['bias'] = "proximity:{$lng},{$lat}";
        }

        $payload = $this->geoapifyGet(
            'https://api.geoapify.com/v1/geocode/search',
            $query,
            'Geoapify text search request'
        );

        $results = collect((array) ($payload['results'] ?? []));

        $attemptSummaries[] = [
            'mode' => 'text-search',
            'query' => $textQuery,
            'result_count' => $results->count(),
            'results' => $results
                ->take(5)
                ->map(fn (array $result) => [
                    'name' => $result['name'] ?? null,
                    'formatted' => $result['formatted'] ?? null,
                    'result_type' => $result['result_type'] ?? null,
                    'place_id' => $result['place_id'] ?? null,
                ])
                ->values()
                ->all(),
        ];

        $needle = $this->normalizeName($textQuery);

        $bestResult = $results->first(function (array $result) use ($needle) {
            $candidates = array_filter([
                $result['name'] ?? null,
                $result['formatted'] ?? null,
            ]);

            foreach ($candidates as $candidate) {
                $normalized = $this->normalizeName((string) $candidate);

                if ($normalized === '' || $needle === '') {
                    continue;
                }

                if (Str::contains($normalized, $needle) || Str::contains($needle, $normalized) || $this->sharesBarangayToken($needle, $normalized)) {
                    return true;
                }
            }

            return false;
        });

        $placeId = $bestResult['place_id'] ?? ($results->first()['place_id'] ?? null);

        return is_string($placeId) && $placeId !== '' ? $placeId : null;
    }

    protected function geoapifyGet(string $url, array $query, string $operation): array
    {
        try {
            $response = Http::timeout(20)->retry(1, 250)->get($url, $query);
        } catch (\Throwable) {
            throw new RuntimeException("{$operation} could not connect to Geoapify.");
        }

        if (! $response->successful()) {
            throw new RuntimeException("{$operation} failed with status {$response->status()}.");
        }

        return (array) $response->json();
    }

    protected function buildBarangaySearchText(Barangay $barangay): string
    {
        return collect($this->boundaryQueryComponents($barangay))
            ->filter(fn (?string $value) => filled($value))
            ->implode(', ');
    }

    protected function boundaryQueryComponents(Barangay $barangay): array
    {
        return [
            'barangay' => $barangay->barangay_name,
            'city' => $barangay->city?->city_name,
            'province' => $barangay->city?->province?->province_name,
            'region' => (string) config('services.geoapify.region', 'Ilocos Region'),
            'country' => (string) config('services.geoapify.country', 'Philippines'),
        ];
    }

    protected function extractMunicipalityPlaceId(array $features, string $cityNeedle): ?string
    {
        if ($cityNeedle === '') {
            return null;
        }

        foreach ($features as $feature) {
            $properties = (array) ($feature['properties'] ?? []);
            $candidates = array_filter([
                $properties['name'] ?? null,
                $properties['formatted'] ?? null,
                $properties['city'] ?? null,
            ]);

            foreach ($candidates as $candidate) {
                if ($this->normalizeName((string) $candidate) === $cityNeedle) {
                    $placeId = $properties['place_id'] ?? $properties['id'] ?? null;

                    if (is_string($placeId) && $placeId !== '') {
                        return $placeId;
                    }
                }
            }
        }

        return null;
    }

    protected function isMunicipalityLevelMatch(array $properties, string $cityNeedle): bool
    {
        if ($cityNeedle === '') {
            return false;
        }

        $candidates = array_filter([
            $properties['name'] ?? null,
            $properties['formatted'] ?? null,
            $properties['city'] ?? null,
            $properties['county'] ?? null,
            $properties['state_district'] ?? null,
        ]);

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeName((string) $candidate);

            if ($normalized === '') {
                continue;
            }

            if ($normalized === $cityNeedle) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeName(string $value): string
    {
        $normalized = Str::lower($value);
        $normalized = preg_replace('/\bbarangay\b/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^a-z0-9]+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    protected function sharesBarangayToken(string $needle, string $candidate): bool
    {
        $needleTokens = array_values(array_filter(explode(' ', $needle)));
        $candidateTokens = array_values(array_filter(explode(' ', $candidate)));

        if (empty($needleTokens) || empty($candidateTokens)) {
            return false;
        }

        return count(array_intersect($needleTokens, $candidateTokens)) >= max(1, min(2, count($needleTokens)));
    }

    protected function estimateGeometryArea(array $geometry): float
    {
        $type = $geometry['type'] ?? null;
        $coordinates = $geometry['coordinates'] ?? [];

        return match ($type) {
            'Polygon' => $this->estimatePolygonArea($coordinates),
            'MultiPolygon' => collect($coordinates)->sum(fn ($polygon) => $this->estimatePolygonArea($polygon)),
            default => INF,
        };
    }

    protected function estimatePolygonArea(array $rings): float
    {
        if (empty($rings) || ! is_array($rings[0] ?? null)) {
            return INF;
        }

        $outerRing = $rings[0];
        $area = 0.0;
        $count = count($outerRing);

        if ($count < 3) {
            return INF;
        }

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $current = $outerRing[$i] ?? null;
            $previous = $outerRing[$j] ?? null;

            if (! is_array($current) || ! is_array($previous) || count($current) < 2 || count($previous) < 2) {
                continue;
            }

            $area += ((float) $previous[0] * (float) $current[1]) - ((float) $current[0] * (float) $previous[1]);
        }

        return abs($area / 2);
    }
}
