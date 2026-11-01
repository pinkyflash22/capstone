<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class InvestigationDocumentPreviewRenderer
{
    public function render(string $docxPath): string
    {
        $pdfPath = $docxPath . '.pdf';
        // Office automation must not open competing instances on the same server.
        $lock = fopen(sys_get_temp_dir() . '/pyrosense-investigation-preview.lock', 'c');
        if (! $lock) {
            throw new RuntimeException('The document renderer lock could not be opened.');
        }
        @set_time_limit(240);
        if (! flock($lock, LOCK_EX)) {
            fclose($lock);
            throw new RuntimeException('The document renderer lock could not be acquired.');
        }

        $workspace = storage_path('framework/cache/investigation-preview/' . Str::uuid());
        try {
            if (config('investigation-preview.driver') === 'word') {
                $this->renderWithWord($docxPath, $pdfPath);
            } else {
                $this->renderWithLibreOffice($docxPath, $pdfPath, $workspace);
            }
            if (! is_file($pdfPath) || filesize($pdfPath) === 0) {
                throw new RuntimeException('The document renderer did not produce a PDF.');
            }
            return $pdfPath;
        } catch (\Throwable $exception) {
            if (is_file($pdfPath)) unlink($pdfPath);
            throw $exception;
        } finally {
            if (is_dir($workspace)) File::deleteDirectory($workspace);
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function renderWithLibreOffice(string $docxPath, string $pdfPath, string $workspace): void
    {
        $diagnostics = [];
        if (config('investigation-preview.persistent')) {
            try {
                $this->renderWithPersistentLibreOffice($docxPath, $pdfPath, $workspace);
                return;
            } catch (\Throwable $exception) {
                $diagnostics[] = 'Persistent worker failed: ' . $exception->getMessage();
            }
        }

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $outputDirectory = $workspace . DIRECTORY_SEPARATOR . 'output-' . $attempt;
            $profilePath = $workspace . DIRECTORY_SEPARATOR . 'profile-' . $attempt;
            $sourceCopy = $workspace . DIRECTORY_SEPARATOR . 'source-' . $attempt . '.docx';
            File::ensureDirectoryExists($outputDirectory, 0700);

            try {
                if (! copy($docxPath, $sourceCopy)) {
                    throw new RuntimeException('The generated Word document could not be copied into the renderer workspace.');
                }
                $this->assertReadableDocx($sourceCopy);
                clearstatcache(true, $sourceCopy);
                usleep(100000);
                $result = Process::env($this->officeProcessEnvironment())->timeout(120)->run([
                    config('investigation-preview.soffice_binary'),
                    '-env:UserInstallation=' . $this->fileUri($profilePath),
                    '--headless', '--norestore', '--nodefault', '--nofirststartwizard',
                    '--convert-to', 'pdf:writer_pdf_Export', '--outdir', $outputDirectory, $sourceCopy,
                ]);
                $converted = collect(File::files($outputDirectory))
                    ->first(fn ($file) => strtolower($file->getExtension()) === 'pdf');
                if ($result->successful() && $converted && copy($converted->getPathname(), $pdfPath)) {
                    return;
                }
                $diagnostics[] = sprintf(
                    'Attempt %d exited %s. %s %s',
                    $attempt,
                    $result->exitCode() ?? 'without a code',
                    trim($result->output()),
                    trim($result->errorOutput())
                );
            } catch (\Throwable $exception) {
                $diagnostics[] = sprintf('Attempt %d failed: %s', $attempt, $exception->getMessage());
            }

            if ($attempt < 2) usleep(300000);
        }

        throw new RuntimeException('LibreOffice could not render the investigation template. ' . implode(' | ', $diagnostics));
    }

    private function renderWithPersistentLibreOffice(string $docxPath, string $pdfPath, string $workspace): void
    {
        $this->ensureLibreOfficeListener();
        $diagnostics = [];

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $sourceCopy = $workspace . DIRECTORY_SEPARATOR . 'persistent-source-' . $attempt . '.docx';
            $converted = $workspace . DIRECTORY_SEPARATOR . 'persistent-output-' . $attempt . '.pdf';

            try {
                File::ensureDirectoryExists($workspace, 0700);
                if (! copy($docxPath, $sourceCopy)) {
                    throw new RuntimeException('The source DOCX could not be copied into the persistent-worker workspace.');
                }
                $this->assertReadableDocx($sourceCopy);
                $result = Process::env($this->officeProcessEnvironment())->timeout(45)->run([
                    config('investigation-preview.uno_python_binary'),
                    base_path('scripts/convert_docx_with_uno.py'),
                    $sourceCopy,
                    $converted,
                    '--host', (string) config('investigation-preview.listener_host'),
                    '--port', (string) config('investigation-preview.listener_port'),
                ]);
                if ($result->successful() && is_file($converted) && filesize($converted) > 0
                    && copy($converted, $pdfPath)) {
                    return;
                }
                $diagnostics[] = sprintf(
                    'Attempt %d exited %s. %s %s',
                    $attempt,
                    $result->exitCode() ?? 'without a code',
                    trim($result->output()),
                    trim($result->errorOutput())
                );
            } catch (\Throwable $exception) {
                $diagnostics[] = sprintf('Attempt %d failed: %s', $attempt, $exception->getMessage());
            }

            if (! $this->libreOfficeListenerIsReady()) {
                $this->ensureLibreOfficeListener();
            }
        }

        throw new RuntimeException(implode(' | ', $diagnostics));
    }

    private function ensureLibreOfficeListener(): void
    {
        if ($this->libreOfficeListenerIsReady()) return;

        $profile = (string) config('investigation-preview.listener_profile');
        File::ensureDirectoryExists($profile, 0700);
        $host = (string) config('investigation-preview.listener_host');
        $port = (int) config('investigation-preview.listener_port');
        $accept = sprintf('socket,host=%s,port=%d;urp;StarOffice.ComponentContext', $host, $port);
        $binary = (string) config('investigation-preview.soffice_binary');

        if (PHP_OS_FAMILY === 'Windows') {
            $executable = preg_replace('/\.com$/i', '.exe', $binary);
            if (! is_string($executable) || ! is_file($executable)) $executable = $binary;
            $arguments = [
                '-env:UserInstallation=' . $this->fileUri($profile),
                '--headless', '--invisible', '--nologo', '--nodefault', '--nofirststartwizard',
                '--accept=' . $accept,
            ];
            $quotedArguments = implode(', ', array_map(
                fn ($argument) => "'" . str_replace("'", "''", $argument) . "'",
                $arguments
            ));
            $script = sprintf(
                "Start-Process -FilePath '%s' -ArgumentList @(%s) -WindowStyle Hidden",
                str_replace("'", "''", $executable),
                $quotedArguments
            );
            Process::env($this->officeProcessEnvironment())->timeout(15)->run([
                'C:\\WINDOWS\\System32\\WindowsPowerShell\\v1.0\\powershell.exe',
                '-NoProfile', '-NonInteractive', '-WindowStyle', 'Hidden',
                '-EncodedCommand', base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8')),
            ]);
        } else {
            $command = sprintf(
                'nohup %s %s --headless --invisible --nologo --nodefault --nofirststartwizard %s >/tmp/pyrosense-libreoffice.log 2>&1 </dev/null &',
                escapeshellarg($binary),
                escapeshellarg('-env:UserInstallation=' . $this->fileUri($profile)),
                escapeshellarg('--accept=' . $accept)
            );
            Process::env($this->officeProcessEnvironment())->timeout(15)->run(['sh', '-c', $command]);
        }

        $deadline = microtime(true) + 20;
        do {
            usleep(250000);
            if ($this->libreOfficeListenerIsReady()) return;
        } while (microtime(true) < $deadline);

        throw new RuntimeException('The persistent LibreOffice worker did not become ready.');
    }

    private function libreOfficeListenerIsReady(): bool
    {
        $socket = @fsockopen(
            (string) config('investigation-preview.listener_host'),
            (int) config('investigation-preview.listener_port'),
            $errorCode,
            $errorMessage,
            0.25
        );
        if (! is_resource($socket)) return false;
        fclose($socket);

        return true;
    }

    private function assertReadableDocx(string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('The generated Word document is not a readable DOCX package.');
        }

        try {
            $entries = [];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                if (is_string($name)) $entries[] = strtolower(str_replace('\\', '/', $name));
            }
            if (! in_array('[content_types].xml', $entries, true)
                || ! in_array('word/document.xml', $entries, true)) {
                throw new RuntimeException('The generated Word document is missing required DOCX entries.');
            }
        } finally {
            $zip->close();
        }
    }

    private function fileUri(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $encoded = str_replace(['%2F', '%3A'], ['/', ':'], rawurlencode($normalized));

        return preg_match('/^[A-Za-z]:\//', $normalized) ? 'file:///' . $encoded : 'file://' . $encoded;
    }

    /**
     * Apache on Windows does not always expose the system variables that
     * Symfony Process normally inherits. Without them, LibreOffice's Python
     * runtime cannot initialize the Windows socket provider used by UNO.
     */
    private function officeProcessEnvironment(): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [];
        }

        $systemRoot = getenv('SystemRoot') ?: getenv('WINDIR') ?: 'C:\\Windows';
        $officeDirectory = dirname((string) config('investigation-preview.soffice_binary'));
        $path = array_filter([
            $officeDirectory,
            $systemRoot . '\\System32',
            $systemRoot,
            $systemRoot . '\\System32\\Wbem',
            getenv('PATH') ?: null,
        ]);

        return [
            'SystemRoot' => $systemRoot,
            'WINDIR' => $systemRoot,
            'ComSpec' => $systemRoot . '\\System32\\cmd.exe',
            'PATH' => implode(';', array_unique($path)),
            'TEMP' => sys_get_temp_dir(),
            'TMP' => sys_get_temp_dir(),
            'UNO_PATH' => $officeDirectory,
        ];
    }

    private function renderWithWord(string $docxPath, string $pdfPath): void
    {
        $script = <<<'PS'
$ErrorActionPreference = 'Stop'
$word = $null
$document = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $word.AutomationSecurity = 3
    $document = $word.Documents.Open('__DOCX__', $false, $true)
    $document.ExportAsFixedFormat('__PDF__', 17)
} finally {
    if ($document) {
        $document.Close(0)
        [void][Runtime.InteropServices.Marshal]::ReleaseComObject($document)
    }
    if ($word) {
        $word.Quit()
        [void][Runtime.InteropServices.Marshal]::ReleaseComObject($word)
    }
}
PS;
        $script = str_replace(['__DOCX__', '__PDF__'], [
            str_replace("'", "''", $docxPath), str_replace("'", "''", $pdfPath),
        ], $script);
        $result = Process::env($this->officeProcessEnvironment())->timeout(90)->run([
            'C:\\WINDOWS\\System32\\WindowsPowerShell\\v1.0\\powershell.exe',
            '-NoProfile', '-NonInteractive', '-WindowStyle', 'Hidden',
            '-EncodedCommand', base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8')),
        ]);
        if ($result->failed()) {
            throw new RuntimeException('Microsoft Word could not render the investigation template. Check that Word is installed and available to the application.');
        }
    }
}
