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
use Illuminate\View\View;

class WeatherContextController extends Controller
{
    public function index(Request $request, DailyWeatherContextService $weatherService): View
    {
        $stationOfficer = StationOfficer::query()
            ->with(['station', 'user'])
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
        ]);

        $month = Carbon::createFromFormat('Y-m', $validated['month'] ?? now('Asia/Manila')->format('Y-m'), 'Asia/Manila')->startOfMonth();
        $barangayId = isset($validated['barangay_id']) && in_array((int) $validated['barangay_id'], $coveredBarangayIds, true)
            ? (int) $validated['barangay_id']
            : null;
        $quality = $validated['quality'] ?? 'all';
        $source = trim((string) ($validated['source'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 15);
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
            'sources' => $sources,
            'sourceSummaries' => $sourceSummaries,
            'summary' => $summary,
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
}
