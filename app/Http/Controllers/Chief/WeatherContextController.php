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
        $weatherCards = $scopeRows
            ->groupBy(fn ($row) => Carbon::parse($row->observation_date)->toDateString())
            ->map(function (Collection $rows, string $date) use ($weatherService) {
                $daySummary = $weatherService->summarizeStoredRows($rows) ?? [];
                $condition = trim((string) ($daySummary['dominant_condition'] ?? 'Unknown')) ?: 'Unknown';
                $averageTemperatures = $rows->pluck('temperature_avg_c')->filter(fn ($value) => $value !== null);
                $minimumTemperatures = $rows->pluck('temperature_min_c')->filter(fn ($value) => $value !== null);
                $maximumTemperatures = $rows->pluck('temperature_max_c')->filter(fn ($value) => $value !== null);
                $humidities = $rows->pluck('humidity_avg_percent')->filter(fn ($value) => $value !== null);
                $windSpeeds = $rows->pluck('wind_avg_kph')->filter(fn ($value) => $value !== null);
                $precipitation = $rows->pluck('precipitation_total_mm')->filter(fn ($value) => $value !== null);

                return [
                    'date' => $date,
                    'day' => Carbon::parse($date)->format('D'),
                    'date_label' => Carbon::parse($date)->format('M j'),
                    'condition' => $condition,
                    'visual' => $this->weatherVisualType($condition),
                    'avg_temp_c' => $averageTemperatures->isEmpty() ? null : round((float) $averageTemperatures->avg(), 1),
                    'min_temp_c' => $minimumTemperatures->isEmpty() ? null : round((float) $minimumTemperatures->min(), 1),
                    'max_temp_c' => $maximumTemperatures->isEmpty() ? null : round((float) $maximumTemperatures->max(), 1),
                    'humidity' => $humidities->isEmpty() ? null : round((float) $humidities->avg(), 1),
                    'wind_kph' => $windSpeeds->isEmpty() ? null : round((float) $windSpeeds->avg(), 1),
                    'precipitation_mm' => $precipitation->isEmpty() ? null : round((float) $precipitation->avg(), 1),
                    'locations' => $rows->pluck('barangay_id')->unique()->count(),
                ];
            })
            ->sortByDesc('date')
            ->take(5)
            ->sortBy('date')
            ->values();

        $selectedBarangay = $barangayId
            ? $coveredBarangays->firstWhere('barangay_id', $barangayId)
            : null;
        $coordinateRow = $barangayId
            ? $scopeRows->where('barangay_id', $barangayId)->sortByDesc('observation_date')->first()
            : $scopeRows->sortByDesc('observation_date')->first();
        $forecastLatitude = $barangayId
            ? ($coordinateRow?->latitude ?? $stationOfficer->station?->marker?->lat)
            : ($stationOfficer->station?->marker?->lat ?? $coordinateRow?->latitude);
        $forecastLongitude = $barangayId
            ? ($coordinateRow?->longitude ?? $stationOfficer->station?->marker?->lng)
            : ($stationOfficer->station?->marker?->lng ?? $coordinateRow?->longitude);
        $liveForecast = collect();
        $liveForecastCurrent = [];
        $liveForecastFetchedAt = null;
        $liveForecastError = null;

        if (is_numeric($forecastLatitude) && is_numeric($forecastLongitude)) {
            try {
                $forecastPayload = $weatherService->fetchForecast((float) $forecastLatitude, (float) $forecastLongitude, 5);
                $liveForecast = collect($forecastPayload['cards'] ?? [])->map(function (array $card) {
                    $card['visual'] = $this->weatherVisualType($card['condition'] ?? 'Unknown');

                    return $card;
                });
                $liveForecastCurrent = $forecastPayload['current'] ?? [];
                $liveForecastFetchedAt = $forecastPayload['fetched_at'] ?? null;
            } catch (\Throwable $exception) {
                $liveForecastError = 'Live forecast is temporarily unavailable.';
                Log::warning('Weather Context live forecast failed.', [
                    'station_id' => $stationId,
                    'error' => $exception->getMessage(),
                ]);
            }
        } else {
            $liveForecastError = 'Add a station map location to enable the live forecast.';
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
            'liveForecast' => $liveForecast,
            'liveForecastCurrent' => $liveForecastCurrent,
            'liveForecastFetchedAt' => $liveForecastFetchedAt,
            'liveForecastError' => $liveForecastError,
            'forecastLocationLabel' => $selectedBarangay?->barangay_name ?? $stationOfficer->station?->station_name ?? 'Station location',
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

        $validated = $request->validate(['days' => ['required', 'integer', 'min:1', 'max:31']]);
        $summary = $service->collectMissingRecentDays((int) $validated['days'], $stationId);

        return back()->with(
            $summary['failed'] > 0 ? 'warning' : 'success',
            "Weather backfill saved {$summary['saved']} missing row(s); {$summary['failed']} failed."
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
