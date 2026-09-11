<?php

namespace App\Http\Controllers\Chief;

use App\Http\Controllers\Controller;
use App\Models\DailyWeatherContext;
use App\Models\StationOfficer;
use App\Services\DailyWeatherContextService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class WeatherContextController extends Controller
{
    public function index(Request $request, DailyWeatherContextService $weatherService): View
    {
        $stationOfficer = StationOfficer::query()
            ->with(['station.marker', 'user'])
            ->where('user_id', Auth::user()?->user_id)
            ->firstOrFail();
        $stationId = (int) $stationOfficer->station_id;
        $coveredBarangays = $this->coveredBarangays($stationId);
        $coveredBarangayIds = $coveredBarangays->pluck('barangay_id')->map(fn ($id) => (int) $id)->all();

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'barangay_id' => ['nullable', 'integer'],
            'quality' => ['nullable', 'in:all,complete,incomplete'],
            'source' => ['nullable', 'string', 'max:60'],
            'per_page' => ['nullable', 'integer', 'in:10,15,25'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $month = Carbon::createFromFormat('Y-m', $validated['month'] ?? now('Asia/Manila')->format('Y-m'), 'Asia/Manila')->startOfMonth();
        $barangayId = isset($validated['barangay_id']) && in_array((int) $validated['barangay_id'], $coveredBarangayIds, true)
            ? (int) $validated['barangay_id']
            : null;
        $quality = $validated['quality'] ?? 'all';
        $source = trim((string) ($validated['source'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 15);
        $selectedDate = $validated['date'] ?? null;
        $monthStart = $month->toDateString();
        $monthEnd = $month->copy()->endOfMonth()->toDateString();

        $baseQuery = DailyWeatherContext::query()
            ->join('barangays', 'daily_weather_contexts.barangay_id', '=', 'barangays.barangay_id')
            ->whereIn('daily_weather_contexts.barangay_id', $coveredBarangayIds ?: [0])
            ->whereBetween('daily_weather_contexts.observation_date', [$monthStart, $monthEnd])
            ->when($barangayId, fn ($query) => $query->where('daily_weather_contexts.barangay_id', $barangayId))
            ->when($source !== '', fn ($query) => $query->where('daily_weather_contexts.source', $source));

        $qualityFilter = function ($query) use ($quality) {
            if ($quality === 'complete') {
                $query->where('daily_weather_contexts.hourly_observation_count', '>=', 20)
                    ->whereNotNull('daily_weather_contexts.temperature_avg_c')
                    ->whereNotNull('daily_weather_contexts.humidity_avg_percent')
                    ->whereNotNull('daily_weather_contexts.wind_avg_kph');
            } elseif ($quality === 'incomplete') {
                $query->where(function ($issueQuery) {
                    $issueQuery->where('daily_weather_contexts.hourly_observation_count', '<', 20)
                        ->orWhereNull('daily_weather_contexts.temperature_avg_c')
                        ->orWhereNull('daily_weather_contexts.humidity_avg_percent')
                        ->orWhereNull('daily_weather_contexts.wind_avg_kph');
                });
            }
        };

        $records = (clone $baseQuery)
            ->tap($qualityFilter)
            ->when($selectedDate, fn ($query) => $query->whereDate('daily_weather_contexts.observation_date', $selectedDate))
            ->select('daily_weather_contexts.*', 'barangays.barangay_name')
            ->orderByDesc('daily_weather_contexts.observation_date')
            ->orderBy('barangays.barangay_name')
            ->paginate($perPage)
            ->withQueryString();

        $selectedDateDetails = $selectedDate
            ? (clone $baseQuery)
                ->whereDate('daily_weather_contexts.observation_date', $selectedDate)
                ->select('daily_weather_contexts.*', 'barangays.barangay_name')
                ->orderBy('barangays.barangay_name')
                ->get()
            : collect();

        $monthRows = DailyWeatherContext::query()
            ->join('barangays', 'daily_weather_contexts.barangay_id', '=', 'barangays.barangay_id')
            ->whereIn('daily_weather_contexts.barangay_id', $coveredBarangayIds ?: [0])
            ->whereBetween('daily_weather_contexts.observation_date', [$monthStart, $monthEnd])
            ->get(['daily_weather_contexts.*', 'barangays.barangay_name']);

        $selectedCoverageCount = $barangayId ? 1 : $coveredBarangays->count();
        $currentMonth = Carbon::now('Asia/Manila')->startOfMonth();
        $lastExpectedDate = $month->greaterThan($currentMonth)
            ? $month->copy()->subDay()
            : ($month->isSameMonth($currentMonth)
                ? Carbon::now('Asia/Manila')->subDay()->startOfDay()
                : $month->copy()->endOfMonth());
        $expectedCalendarDays = $lastExpectedDate->lessThan($month)
            ? 0
            : min($month->daysInMonth, $month->diffInDays($lastExpectedDate) + 1);
        $expectedRows = $selectedCoverageCount * $expectedCalendarDays;
        $scopeRows = $barangayId
            ? $monthRows->where('barangay_id', $barangayId)->values()
            : $monthRows;
        if ($source !== '') {
            $scopeRows = $scopeRows->where('source', $source)->values();
        }
        $uniqueObservedRows = $scopeRows->unique(fn ($row) => $row->barangay_id.'|'.Carbon::parse($row->observation_date)->toDateString())->count();
        $completeRows = $scopeRows->filter(fn ($row) => $this->isCompleteRow($row))->count();

        $summary = $weatherService->summarizeStoredRows($scopeRows);
        $previousDayEnd = Carbon::now('Asia/Manila')->subDay()->startOfDay();
        $previousDayStart = $previousDayEnd->copy()->subDays(6);
        $previousWeatherRows = DailyWeatherContext::query()
            ->join('barangays', 'daily_weather_contexts.barangay_id', '=', 'barangays.barangay_id')
            ->whereIn('daily_weather_contexts.barangay_id', $coveredBarangayIds ?: [0])
            ->whereBetween('daily_weather_contexts.observation_date', [
                $previousDayStart->toDateString(),
                $previousDayEnd->toDateString(),
            ])
            ->when($barangayId, fn ($query) => $query->where('daily_weather_contexts.barangay_id', $barangayId))
            ->when($source !== '', fn ($query) => $query->where('daily_weather_contexts.source', $source))
            ->get(['daily_weather_contexts.*', 'barangays.barangay_name'])
            ->groupBy(fn ($row) => Carbon::parse($row->observation_date)->toDateString());

        $weatherCards = collect(range(6, 0))->map(function (int $offset) use ($previousDayEnd, $previousWeatherRows, $weatherService) {
                $date = $previousDayEnd->copy()->subDays($offset)->toDateString();
                $rows = $previousWeatherRows->get($date, collect());
                if ($rows->isEmpty()) {
                    return [
                        'date' => $date,
                        'day' => Carbon::parse($date)->format('D'),
                        'date_label' => Carbon::parse($date)->format('M j'),
                        'condition' => 'Not collected',
                        'visual' => 'cloud',
                        'avg_temp_c' => null,
                        'min_temp_c' => null,
                        'max_temp_c' => null,
                        'humidity' => null,
                        'wind_kph' => null,
                        'precipitation_mm' => null,
                        'locations' => 0,
                        'has_data' => false,
                        'risk_flags' => [],
                        'attention_level' => 'none',
                    ];
                }

                $daySummary = $weatherService->summarizeStoredRows($rows) ?? [];
                $condition = trim((string) ($daySummary['dominant_condition'] ?? 'Unknown')) ?: 'Unknown';
                $averageTemperatures = $rows->pluck('temperature_avg_c')->filter(fn ($value) => $value !== null);
                $minimumTemperatures = $rows->pluck('temperature_min_c')->filter(fn ($value) => $value !== null);
                $maximumTemperatures = $rows->pluck('temperature_max_c')->filter(fn ($value) => $value !== null);
                $humidities = $rows->pluck('humidity_avg_percent')->filter(fn ($value) => $value !== null);
                $windSpeeds = $rows->pluck('wind_avg_kph')->filter(fn ($value) => $value !== null);
                $precipitation = $rows->pluck('precipitation_total_mm')->filter(fn ($value) => $value !== null);

                $maximumTemperature = $maximumTemperatures->isEmpty() ? null : round((float) $maximumTemperatures->max(), 1);
                $averageWind = $windSpeeds->isEmpty() ? null : round((float) $windSpeeds->avg(), 1);
                $averagePrecipitation = $precipitation->isEmpty() ? null : round((float) $precipitation->avg(), 1);
                $riskFlags = [];
                if ($maximumTemperature !== null && $maximumTemperature >= 32) {
                    $riskFlags[] = 'Hot';
                }
                if ($averagePrecipitation !== null && $averagePrecipitation <= 0) {
                    $riskFlags[] = 'Dry';
                }
                if ($averageWind !== null && $averageWind >= 20) {
                    $riskFlags[] = 'Windy';
                }

                return [
                    'date' => $date,
                    'day' => Carbon::parse($date)->format('D'),
                    'date_label' => Carbon::parse($date)->format('M j'),
                    'condition' => $condition,
                    'visual' => $this->weatherVisualType($condition),
                    'avg_temp_c' => $averageTemperatures->isEmpty() ? null : round((float) $averageTemperatures->avg(), 1),
                    'min_temp_c' => $minimumTemperatures->isEmpty() ? null : round((float) $minimumTemperatures->min(), 1),
                    'max_temp_c' => $maximumTemperature,
                    'humidity' => $humidities->isEmpty() ? null : round((float) $humidities->avg(), 1),
                    'wind_kph' => $averageWind,
                    'precipitation_mm' => $averagePrecipitation,
                    'locations' => $rows->pluck('barangay_id')->unique()->count(),
                    'has_data' => true,
                    'risk_flags' => $riskFlags,
                    'attention_level' => count($riskFlags) >= 2 ? 'attention' : (count($riskFlags) === 1 ? 'watch' : 'normal'),
                ];
            })
            ->values();

        $selectedBarangay = $barangayId
            ? $coveredBarangays->firstWhere('barangay_id', $barangayId)
            : null;
        $barangayForecastLocation = $this->resolveBarangayForecastLocation(
            $barangayId,
            $coveredBarangayIds
        );
        $coordinateRow = $barangayId
            ? $scopeRows->where('barangay_id', $barangayId)->sortByDesc('observation_date')->first()
            : $scopeRows->sortByDesc('observation_date')->first();
        $stationLatitude = $stationOfficer->station?->marker?->lat;
        $stationLongitude = $stationOfficer->station?->marker?->lng;

        if ($barangayId && $barangayForecastLocation) {
            $forecastLatitude = $barangayForecastLocation->lat;
            $forecastLongitude = $barangayForecastLocation->lng;
            $forecastCoordinateSource = 'Selected barangay hall marker';
            $forecastLocationLabel = $selectedBarangay?->barangay_name ?? $barangayForecastLocation->barangay_name;
        } elseif (is_numeric($stationLatitude) && is_numeric($stationLongitude)) {
            $forecastLatitude = $stationLatitude;
            $forecastLongitude = $stationLongitude;
            $forecastCoordinateSource = 'Assigned station marker';
            $forecastLocationLabel = $stationOfficer->station?->station_name ?? 'Assigned station';
        } elseif ($barangayForecastLocation) {
            $forecastLatitude = $barangayForecastLocation->lat;
            $forecastLongitude = $barangayForecastLocation->lng;
            $forecastCoordinateSource = 'Covered barangay hall marker';
            $forecastLocationLabel = $barangayForecastLocation->barangay_name ?? 'Covered barangay';
        } else {
            $forecastLatitude = $coordinateRow?->latitude;
            $forecastLongitude = $coordinateRow?->longitude;
            $forecastCoordinateSource = $coordinateRow ? 'Latest saved weather observation' : null;
            $forecastLocationLabel = $coordinateRow?->barangay_name ?? 'Station location';
        }
        $liveForecast = collect();
        $liveForecastCurrent = [];
        $liveForecastFetchedAt = null;
        $liveForecastError = null;
        $liveForecastIsStale = false;
        $liveForecastWarning = null;
        $liveForecastProvider = 'Open-Meteo';
        $liveForecastProviderUrl = 'https://open-meteo.com/';

        if (is_numeric($forecastLatitude) && is_numeric($forecastLongitude)) {
            try {
                $forecastPayload = $weatherService->fetchForecast((float) $forecastLatitude, (float) $forecastLongitude, 7);
                $liveForecast = collect($forecastPayload['cards'] ?? [])->map(function (array $card) {
                    $card['visual'] = $this->weatherVisualType($card['condition'] ?? 'Unknown');

                    return $card;
                });
                $liveForecastCurrent = $forecastPayload['current'] ?? [];
                $liveForecastFetchedAt = $forecastPayload['fetched_at'] ?? null;
                $liveForecastIsStale = (bool) ($forecastPayload['is_stale'] ?? false);
                $liveForecastWarning = $forecastPayload['warning'] ?? null;
                $liveForecastProvider = $forecastPayload['provider'] ?? 'Open-Meteo';
                $liveForecastProviderUrl = $forecastPayload['provider_url'] ?? 'https://open-meteo.com/';
            } catch (\Throwable $exception) {
                $liveForecastError = 'Weather providers are temporarily unavailable and no usable cached forecast was found. Please try again later.';
                Log::warning('Weather Context live forecast failed.', [
                    'station_id' => $stationId,
                    'error' => $exception->getMessage(),
                ]);
            }
        } else {
            $liveForecastError = 'Add coordinates to the assigned station or one of its covered barangay halls to enable the live forecast.';
        }

        $yesterdayObservation = $weatherCards->last();
        $todayForecast = $liveForecast->first();
        $weatherComparison = null;
        if (($yesterdayObservation['has_data'] ?? false) && is_array($todayForecast)) {
            $comparisonMetrics = [
                ['label' => 'Maximum temperature', 'icon' => 'fa-temperature-high', 'observed' => $yesterdayObservation['max_temp_c'], 'forecast' => $todayForecast['max_temp_c'] ?? null, 'unit' => '°C'],
                ['label' => 'Humidity', 'icon' => 'fa-droplet', 'observed' => $yesterdayObservation['humidity'], 'forecast' => $todayForecast['humidity_percent'] ?? null, 'unit' => '%'],
                ['label' => 'Wind speed', 'icon' => 'fa-wind', 'observed' => $yesterdayObservation['wind_kph'], 'forecast' => $todayForecast['wind_kph'] ?? null, 'unit' => ' km/h'],
                ['label' => 'Precipitation', 'icon' => 'fa-cloud-rain', 'observed' => $yesterdayObservation['precipitation_mm'], 'forecast' => $todayForecast['precipitation_mm'] ?? null, 'unit' => ' mm'],
            ];
            $weatherComparison = [
                'yesterday_date' => $yesterdayObservation['date_label'],
                'today_date' => $todayForecast['date_label'] ?? Carbon::now('Asia/Manila')->format('M j'),
                'metrics' => collect($comparisonMetrics)->map(function (array $metric) {
                    $metric['delta'] = $metric['observed'] !== null && $metric['forecast'] !== null
                        ? round((float) $metric['forecast'] - (float) $metric['observed'], 1)
                        : null;

                    return $metric;
                })->all(),
            ];
        }
        $stats = [
            'covered_barangays' => $selectedCoverageCount,
            'observed_days' => $scopeRows->pluck('observation_date')->map(fn ($date) => Carbon::parse($date)->toDateString())->unique()->count(),
            'observed_rows' => $uniqueObservedRows,
            'expected_rows' => $expectedRows,
            'missing_rows' => max(0, $expectedRows - $uniqueObservedRows),
            'coverage_percent' => $expectedRows > 0 ? round(min(100, ($uniqueObservedRows / $expectedRows) * 100), 1) : 0,
            'complete_rows' => $completeRows,
            'quality_percent' => $scopeRows->isNotEmpty() ? round(($completeRows / $scopeRows->count()) * 100, 1) : 0,
        ];

        $barangaySummaries = $monthRows
            ->groupBy('barangay_id')
            ->map(function (Collection $rows) use ($weatherService, $expectedCalendarDays) {
                $summary = $weatherService->summarizeStoredRows($rows) ?? [];
                $observedDays = $rows->pluck('observation_date')->map(fn ($date) => Carbon::parse($date)->toDateString())->unique()->count();
                $complete = $rows->filter(fn ($row) => $this->isCompleteRow($row))->count();

                return array_merge($summary, [
                    'barangay_id' => (int) $rows->first()->barangay_id,
                    'barangay_name' => $rows->first()->barangay_name,
                    'observed_days' => $observedDays,
                    'missing_days' => max(0, $expectedCalendarDays - $observedDays),
                    'quality_percent' => $rows->count() > 0 ? round(($complete / $rows->count()) * 100, 1) : 0,
                ]);
            })
            ->sortByDesc('observed_days')
            ->values();

        $sources = DailyWeatherContext::query()
            ->whereIn('barangay_id', $coveredBarangayIds ?: [0])
            ->select('source')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');
        $sourceSummaries = $monthRows->groupBy('source')->map(function (Collection $rows, string $sourceName) {
            return [
                'source' => $sourceName,
                'rows' => $rows->count(),
                'days' => $rows->pluck('observation_date')->map(fn ($date) => Carbon::parse($date)->toDateString())->unique()->count(),
                'latest' => $rows->max('observation_date'),
                'official' => str_contains(strtolower($sourceName), 'pagasa'),
            ];
        })->values();

        $trainingReadyMonths = DailyWeatherContext::query()
            ->whereIn('barangay_id', $coveredBarangayIds ?: [0])
            ->get(['barangay_id', 'observation_date'])
            ->groupBy(fn ($row) => Carbon::parse($row->observation_date)->format('Y-m'))
            ->filter(fn (Collection $rows) => $rows->pluck('observation_date')->map(fn ($date) => Carbon::parse($date)->toDateString())->unique()->count() >= 20)
            ->count();

        return view('chief.weather', [
            'stationOfficer' => $stationOfficer,
            'station' => $stationOfficer->station,
            'coveredBarangays' => $coveredBarangays,
            'records' => $records,
            'selectedDateDetails' => $selectedDateDetails,
            'month' => $month,
            'selectedBarangayId' => $barangayId,
            'quality' => $quality,
            'selectedSource' => $source,
            'perPage' => $perPage,
            'selectedDate' => $selectedDate,
            'sources' => $sources,
            'sourceSummaries' => $sourceSummaries,
            'summary' => $summary,
            'weatherCards' => $weatherCards,
            'weatherComparison' => $weatherComparison,
            'liveForecast' => $liveForecast,
            'liveForecastCurrent' => $liveForecastCurrent,
            'liveForecastFetchedAt' => $liveForecastFetchedAt,
            'liveForecastError' => $liveForecastError,
            'liveForecastIsStale' => $liveForecastIsStale,
            'liveForecastWarning' => $liveForecastWarning,
            'liveForecastProvider' => $liveForecastProvider,
            'liveForecastProviderUrl' => $liveForecastProviderUrl,
            'forecastLatitude' => is_numeric($forecastLatitude) ? (float) $forecastLatitude : null,
            'forecastLongitude' => is_numeric($forecastLongitude) ? (float) $forecastLongitude : null,
            'forecastCoordinateSource' => $forecastCoordinateSource,
            'forecastLocationLabel' => $forecastLocationLabel,
            'stats' => $stats,
            'barangaySummaries' => $barangaySummaries,
            'trainingReadyMonths' => $trainingReadyMonths,
            'hasOfficialSource' => $sourceSummaries->contains(fn ($item) => $item['official']),
        ]);
    }

    public function backfill(Request $request, DailyWeatherContextService $service): RedirectResponse
    {
        $stationId = (int) StationOfficer::query()
            ->where('user_id', Auth::user()?->user_id)
            ->value('station_id');
        abort_unless($stationId > 0, 403, 'No station assignment was found.');

        $today = Carbon::now('Asia/Manila')->startOfDay();
        $validated = $request->validate([
            'days' => ['nullable', 'required_without:date', 'integer', 'min:1', 'max:31'],
            'date' => [
                'nullable',
                'required_without:days',
                'date_format:Y-m-d',
                'after_or_equal:'.$today->copy()->subDays(31)->toDateString(),
                'before_or_equal:'.$today->copy()->subDay()->toDateString(),
            ],
        ]);

        $summary = isset($validated['date'])
            ? $service->collectDate(Carbon::createFromFormat('Y-m-d', $validated['date'], 'Asia/Manila'), $stationId, true)
            : $service->collectMissingRecentDays((int) $validated['days'], $stationId);
        $scopeLabel = isset($validated['date'])
            ? Carbon::parse($validated['date'])->format('M j, Y')
            : "the last {$validated['days']} days";

        return back()->with(
            $summary['failed'] > 0 ? 'warning' : 'success',
            "Weather backfill for {$scopeLabel} saved {$summary['saved']} missing row(s); {$summary['failed']} failed."
        );
    }

    private function coveredBarangays(int $stationId): Collection
    {
        return DB::table('station_barangay_coverage')
            ->join('barangays', 'station_barangay_coverage.barangay_id', '=', 'barangays.barangay_id')
            ->where('station_barangay_coverage.station_id', $stationId)
            ->where('station_barangay_coverage.is_active', true)
            ->select('barangays.barangay_id', 'barangays.barangay_name')
            ->orderBy('barangays.barangay_name')
            ->get()
            ->unique('barangay_id')
            ->values();
    }

    private function resolveBarangayForecastLocation(?int $barangayId, array $coveredBarangayIds): ?object
    {
        if ($barangayId === null && $coveredBarangayIds === []) {
            return null;
        }

        return DB::table('barangay_halls')
            ->join('markers', 'barangay_halls.marker_id', '=', 'markers.marker_id')
            ->join('barangays', 'barangay_halls.barangay_id', '=', 'barangays.barangay_id')
            ->whereIn('barangay_halls.barangay_id', $barangayId ? [$barangayId] : $coveredBarangayIds)
            ->whereNotNull('markers.lat')
            ->whereNotNull('markers.lng')
            ->when($barangayId, fn ($query) => $query->where('barangay_halls.barangay_id', $barangayId))
            ->orderBy('barangay_halls.barangay_id')
            ->select('barangay_halls.barangay_id', 'barangays.barangay_name', 'markers.lat', 'markers.lng')
            ->first();
    }

    private function isCompleteRow(object $row): bool
    {
        return (int) ($row->hourly_observation_count ?? 0) >= 20
            && $row->temperature_avg_c !== null
            && $row->humidity_avg_percent !== null
            && $row->wind_avg_kph !== null;
    }

    private function weatherVisualType(string $condition): string
    {
        $condition = strtolower($condition);

        return match (true) {
            str_contains($condition, 'thunder'), str_contains($condition, 'storm') => 'storm',
            str_contains($condition, 'snow'), str_contains($condition, 'sleet') => 'snow',
            str_contains($condition, 'rain'), str_contains($condition, 'drizzle'), str_contains($condition, 'shower') => 'rain',
            str_contains($condition, 'fog'), str_contains($condition, 'mist'), str_contains($condition, 'haze') => 'fog',
            str_contains($condition, 'partly'), str_contains($condition, 'mainly clear') => 'partly',
            str_contains($condition, 'cloud'), str_contains($condition, 'overcast') => 'cloud',
            str_contains($condition, 'clear'), str_contains($condition, 'sun') => 'sunny',
            default => 'partly',
        };
    }
}
