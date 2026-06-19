<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ForecastModelService
{
    private string $modelPath;
    private string $metadataPath;

    public function __construct()
    {
        $this->modelPath = base_path('capstone-ai/fire_model.pkl');
        $this->metadataPath = base_path('capstone-ai/fire_model_metadata.json');
    }

    public function ensureReady(): bool
    {
        if (! $this->needsRetraining()) {
            return true;
        }

        try {
            $exitCode = Artisan::call('ai:train-forecast-model', [
                '--dataset' => 'capstone-ai/station_barangay_monthly_db.json',
                '--export' => true,
            ]);

            if ($exitCode !== 0) {
                Log::warning('Forecast model training command failed.', [
                    'output' => Artisan::output(),
                ]);

                return false;
            }

            return File::exists($this->modelPath) && File::exists($this->metadataPath);
        } catch (\Throwable $e) {
            Log::warning('Unable to prepare forecast model automatically: ' . $e->getMessage());

            return false;
        }
    }

    public function needsRetraining(): bool
    {
        if (! File::exists($this->modelPath) || ! File::exists($this->metadataPath)) {
            return true;
        }

        $metadataMtime = File::lastModified($this->metadataPath);
        $latestDataUpdate = $this->resolveLatestDataUpdateTimestamp();

        if ($latestDataUpdate === null) {
            return false;
        }

        return $latestDataUpdate > $metadataMtime;
    }

    private function resolveLatestDataUpdateTimestamp(): ?int
    {
        $timestamps = [];

        foreach (['historical_data', 'historical_fire_incidents', 'risk_areas', 'incidents'] as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            $latest = DB::table($table)->max('updated_at');
            if ($latest) {
                try {
                    $timestamps[] = strtotime((string) $latest);
                } catch (\Throwable $e) {
                    // Ignore malformed timestamps.
                }
            }
        }

        $timestamps = array_filter($timestamps);

        return empty($timestamps) ? null : max($timestamps);
    }
}
