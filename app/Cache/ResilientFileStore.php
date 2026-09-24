<?php

namespace App\Cache;

use Illuminate\Cache\FileStore;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResilientFileStore extends FileStore
{
    /**
     * Write through the normal file store, repairing Render's ephemeral hashed
     * directory once before treating cache persistence as optional.
     */
    public function put($key, $value, $seconds)
    {
        $this->repairDirectoryFor($key);

        try {
            return parent::put($key, $value, $seconds);
        } catch (Throwable) {
            $this->repairDirectoryFor($key);

            try {
                return parent::put($key, $value, $seconds);
            } catch (Throwable $exception) {
                $this->logSkippedWrite($exception);

                return false;
            }
        }
    }

    /**
     * Cache::add is also used by file-backed locks and must not take down an
     * HTTP request when the ephemeral cache directory disappears.
     */
    public function add($key, $value, $seconds)
    {
        $this->repairDirectoryFor($key);

        try {
            return parent::add($key, $value, $seconds);
        } catch (Throwable) {
            $this->repairDirectoryFor($key);

            try {
                return parent::add($key, $value, $seconds);
            } catch (Throwable $exception) {
                $this->logSkippedWrite($exception);

                return false;
            }
        }
    }

    protected function repairDirectoryFor(string $key): void
    {
        if (! is_dir($this->getDirectory())) {
            @mkdir($this->getDirectory(), 0777, true);
        }

        $directory = dirname($this->path($key));

        if (! is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        @chmod($directory, 0775);
        clearstatcache(true, $directory);
    }

    protected function logSkippedWrite(Throwable $exception): void
    {
        Log::warning('File cache write was skipped after directory repair failed.', [
            'cache_directory' => $this->getDirectory(),
            'cache_directory_exists' => is_dir($this->getDirectory()),
            'cache_directory_writable' => is_writable($this->getDirectory()),
            'error' => $exception->getMessage(),
        ]);
    }
}
