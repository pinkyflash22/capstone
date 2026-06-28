<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class MediaPath
{
    private const SUPABASE_PREFIX = 'supabase://';

    public static function diskFor(?string $path): string
    {
        return str_starts_with((string) $path, self::SUPABASE_PREFIX) ? 'supabase' : 'public';
    }

    public static function stripPrefix(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return $path;
        }

        if (self::isAbsoluteUrl($path)) {
            return $path;
        }

        if (str_starts_with($path, self::SUPABASE_PREFIX)) {
            return ltrim(substr($path, strlen(self::SUPABASE_PREFIX)), '/');
        }

        return ltrim(str_replace('\\', '/', $path), '/');
    }

    public static function storeForDisk(string $path, string $disk): string
    {
        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');

        return $disk === 'supabase'
            ? self::SUPABASE_PREFIX.$normalizedPath
            : $normalizedPath;
    }

    public static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (self::isAbsoluteUrl($path)) {
            return $path;
        }

        return Storage::disk(self::diskFor($path))->url((string) self::stripPrefix($path));
    }

    public static function delete(?string $path): void
    {
        if ($path === null || $path === '' || self::isAbsoluteUrl($path)) {
            return;
        }

        Storage::disk(self::diskFor($path))->delete((string) self::stripPrefix($path));
    }

    private static function isAbsoluteUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }
}
