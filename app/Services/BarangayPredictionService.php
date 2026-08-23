<?php

namespace App\Services;

use App\Support\PythonRuntime;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class BarangayPredictionService
{
    private string $pythonBinary;

    public function __construct()
    {
        $this->pythonBinary = PythonRuntime::resolve(
            (string) config('services.ai_prediction.python_binary', PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3')
        );
    }

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

        $versionCheck = Process::path(base_path())
            ->env($this->pythonEnvironment())
            ->run([$this->pythonBinary(), '--version']);

        if ($versionCheck->failed()) {
            $failure = PythonRuntime::failureContext($versionCheck, $this->pythonBinary);
            throw new RuntimeException(
                'Python runtime is unavailable. Set AI_PYTHON_BINARY to a valid executable. '
                .$failure['failure_summary']
            );
        }
    }

    public function predict(array $payload): ?array
    {
        $results = $this->predictMany([$payload]);

        return $results[0] ?? null;
    }

    public function predictMany(array $payloads): array
    {
        if ($payloads === []) {
            return [];
        }

        $scriptPath = $this->relativeScriptPath();

        $process = Process::path(base_path())
            ->env($this->pythonEnvironment())
            ->input(json_encode(array_values($payloads)))
            ->run([$this->pythonBinary(), $scriptPath]);

        if ($process->failed()) {
            Log::warning('Python barangay prediction process failed', array_merge(
                PythonRuntime::failureContext($process, $this->pythonBinary),
                [
                    'payload_count' => count($payloads),
                    'script_path' => $scriptPath,
                ]
            ));

            return [];
        }

        $decoded = json_decode($process->output(), true);

        if (! is_array($decoded)) {
            Log::warning('Python barangay prediction output was not valid JSON', [
                'output' => $process->output(),
                'payload_count' => count($payloads),
            ]);

            return [];
        }

        return array_is_list($decoded) ? $decoded : [$decoded];
    }

    private function pythonBinary(): string
    {
        return $this->pythonBinary;
    }

    private function pythonEnvironment(): array
    {
        $pythonPath = trim((string) config('services.ai_prediction.python_path', ''));
        $environment = $pythonPath !== '' ? ['PYTHONPATH' => $pythonPath] : [];

        if (PHP_OS_FAMILY === 'Windows') {
            foreach (['SYSTEMROOT', 'WINDIR', 'PATH', 'TEMP', 'TMP', 'APPDATA', 'LOCALAPPDATA'] as $name) {
                $value = getenv($name);
                if (is_string($value) && $value !== '') {
                    $environment[$name] = $value;
                }
            }
        }

        return $environment;
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
