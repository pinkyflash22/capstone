<?php

namespace App\Support;

use Illuminate\Support\Facades\Process;

class PythonRuntime
{
    public static function resolve(?string $configuredBinary = null): string
    {
        $configuredBinary = trim((string) ($configuredBinary ?: (PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3')));
        $candidates = [$configuredBinary];

        if (PHP_OS_FAMILY === 'Windows') {
            $candidates = array_merge($candidates, [
                'C:/Program Files/Python314/python.exe',
                'C:/Program Files/Python313/python.exe',
                'C:/Program Files/Python312/python.exe',
                'python',
                'python3',
            ]);
        } else {
            $candidates = array_merge($candidates, [
                'python3',
                '/usr/bin/python3',
                '/usr/local/bin/python3',
                'python',
                '/usr/bin/python',
            ]);
        }

        foreach (array_values(array_unique(array_filter($candidates))) as $candidate) {
            try {
                $probe = Process::timeout(5)->run([$candidate, '--version']);
                if ($probe->successful()) {
                    return $candidate;
                }
            } catch (\Throwable) {
                // Continue through the platform-specific candidates.
            }
        }

        // Preserve the configured value so the eventual process error and
        // diagnostics still identify what the deployment attempted to use.
        return $configuredBinary;
    }

    public static function failureContext($process, string $binary): array
    {
        $error = trim((string) $process->errorOutput());
        $output = trim((string) $process->output());

        return [
            'python_binary' => $binary,
            'exit_code' => $process->exitCode(),
            'error' => $error !== '' ? $error : null,
            'output' => $output !== '' ? $output : null,
            'failure_summary' => $error !== ''
                ? $error
                : ($output !== '' ? $output : 'The Python process exited without stderr or stdout.'),
        ];
    }
}
