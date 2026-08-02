<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('station_barangay_coverage', function (Blueprint $table) {
            $table->id('station_barangay_coverage_id');
            $table->foreignId('station_id')->constrained('stations', 'station_id')->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained('barangays', 'barangay_id')->cascadeOnDelete();
            $table->string('coverage_type', 40)->default('primary');
            $table->string('source', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['station_id', 'barangay_id'], 'station_barangay_coverage_unique');
            $table->index(['station_id', 'is_active'], 'station_barangay_coverage_station_active_idx');
        });

        $this->backfillCoverage();
    }

    public function down(): void
    {
        Schema::dropIfExists('station_barangay_coverage');
    }

    private function backfillCoverage(): void
    {
        $now = now();
        $stations = DB::table('stations')
            ->select('station_id', 'station_name', 'address_id')
            ->orderBy('station_id')
            ->get();

        foreach ($stations as $station) {
            $barangays = collect();

            $addressBarangays = DB::table('stations')
                ->leftJoin('full_addresses', 'stations.address_id', '=', 'full_addresses.address_id')
                ->leftJoin('barangays', 'full_addresses.barangay_id', '=', 'barangays.barangay_id')
                ->leftJoin('cities', 'barangays.city_id', '=', 'cities.city_id')
                ->where('stations.station_id', $station->station_id)
                ->select('barangays.barangay_id', 'cities.city_id', 'cities.city_name')
                ->get()
                ->filter(fn ($row) => !empty($row->barangay_id));

            $barangays = $barangays->merge(
                $addressBarangays->map(fn ($row) => [
                    'barangay_id' => (int) $row->barangay_id,
                    'coverage_type' => 'primary',
                    'source' => 'station_address',
                ])
            );

            $cityIds = $addressBarangays
                ->pluck('city_id')
                ->filter()
                ->unique()
                ->values();

            if ($cityIds->isNotEmpty()) {
                $barangays = $barangays->merge(
                    DB::table('barangays')
                        ->whereIn('city_id', $cityIds)
                        ->get(['barangay_id'])
                        ->map(fn ($row) => [
                            'barangay_id' => (int) $row->barangay_id,
                            'coverage_type' => 'primary',
                            'source' => 'station_city',
                        ])
                );
            }

            if ($barangays->isEmpty() && !empty($station->station_name)) {
                $matchedCity = DB::table('cities')
                    ->whereRaw('? LIKE CONCAT("%", city_name, "%")', [$station->station_name])
                    ->orderByRaw('CHAR_LENGTH(city_name) DESC')
                    ->first(['city_id', 'city_name']);

                if ($matchedCity) {
                    $barangays = $barangays->merge(
                        DB::table('barangays')
                            ->where('city_id', $matchedCity->city_id)
                            ->get(['barangay_id'])
                            ->map(fn ($row) => [
                                'barangay_id' => (int) $row->barangay_id,
                                'coverage_type' => 'primary',
                                'source' => 'station_name_city',
                            ])
                    );
                }
            }

            $riskAreaBarangays = DB::table('risk_areas')
                ->join('barangay_halls', 'risk_areas.barangay_hall_id', '=', 'barangay_halls.barangay_hall_id')
                ->where('risk_areas.station_id', $station->station_id)
                ->distinct()
                ->get(['barangay_halls.barangay_id'])
                ->map(fn ($row) => [
                    'barangay_id' => (int) $row->barangay_id,
                    'coverage_type' => 'support',
                    'source' => 'risk_area',
                ]);

            $barangays = $barangays->merge($riskAreaBarangays);

            if ($barangays->isEmpty()) {
                $urbiztondoCityId = DB::table('cities')
                    ->where('city_name', 'Urbiztondo')
                    ->value('city_id');

                if ($urbiztondoCityId) {
                    $barangays = $barangays->merge(
                        DB::table('barangays')
                            ->where('city_id', $urbiztondoCityId)
                            ->get(['barangay_id'])
                            ->map(fn ($row) => [
                                'barangay_id' => (int) $row->barangay_id,
                                'coverage_type' => 'primary',
                                'source' => 'fallback_urbiztondo',
                            ])
                    );
                }
            }

            $rows = $barangays
                ->unique('barangay_id')
                ->values()
                ->map(fn ($row) => [
                    'station_id' => (int) $station->station_id,
                    'barangay_id' => (int) $row['barangay_id'],
                    'coverage_type' => $row['coverage_type'],
                    'source' => $row['source'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            if (!empty($rows)) {
                DB::table('station_barangay_coverage')->insertOrIgnore($rows);
            }
        }
    }
};
