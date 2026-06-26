<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class BarangayPredictionService
{
    public function assertRuntimeReady(): void
    {
        $scriptPath = $this->resolveScriptPath();
        $modelPath = $this->resolveModelPath();
        $metadataPath = $this->resolveMetadataPath();

        if (! File::exists($scriptPath)) {
            throw new RuntimeException("Prediction script not found at [{$scriptPath}].");
        }

        if (! File::exists($modelPath) || ! File::exists($metadataPath)) {
            throw new RuntimeException(
                "Prediction model files are missing. Expected [{$modelPath}] and [{$metadataPath}]. Train the model before running predictions."
            );
        }

        $versionCheck = Process::path(base_path())->run([$this->pythonBinary(), '--version']);

        if ($versionCheck->failed()) {
            throw new RuntimeException(
                "Python runtime is unavailable. Set AI_PYTHON_BINARY in your environment to a valid Python executable."
            );
        }
    }

    public function predict(array $payload): ?array
    {
        $scriptPath = $this->relativeScriptPath();

        $process = Process::path(base_path())
            ->input(json_encode($payload))
            ->run([$this->pythonBinary(), $scriptPath]);

        if ($process->failed()) {
            Log::warning('Python barangay prediction process failed', [
                'error' => $process->errorOutput(),
                'output' => $process->output(),
                'payload' => $payload,
                'python_binary' => $this->pythonBinary(),
                'script_path' => $scriptPath,
            ]);

            return null;
        }

        $decoded = json_decode($process->output(), true);

        if (! is_array($decoded)) {
            Log::warning('Python barangay prediction output was not valid JSON', [
                'output' => $process->output(),
                'payload' => $payload,
            ]);

            return null;
        }

        return $decoded;
    }

    private function pythonBinary(): string
    {
        return (string) config('services.ai_prediction.python_binary', 'python');
    }

    private function relativeScriptPath(): string
    {
        return (string) config('services.ai_prediction.prediction_script', 'capstone-ai/predict_event.py');
    }

    private function resolveScriptPath(): string
    {
        return base_path($this->relativeScriptPath());
    }

    private function resolveModelPath(): string
    {
        return base_path((string) config('services.ai_prediction.model_path', 'capstone-ai/fire_model.pkl'));
    }

    private function resolveMetadataPath(): string
    {
        return base_path((string) config('services.ai_prediction.metadata_path', 'capstone-ai/fire_model_metadata.json'));
    }
}
