<?php

namespace App\Support;

class DeviceInfo
{
    public static function fromUserAgent(?string $userAgent): string
    {
        $userAgent = trim((string) $userAgent);

        if ($userAgent === '') {
            return 'Unavailable';
        }

        $parts = [];

        if (preg_match('/Android\s+([\d.]+)/i', $userAgent, $match)) {
            $parts[] = 'Android '.$match[1];

            $model = self::androidModel($userAgent);
            if ($model !== null) {
                $parts[] = $model;
            }
        } elseif (stripos($userAgent, 'iPhone') !== false) {
            $parts[] = 'iPhone';
            if (preg_match('/(?:CPU )?iPhone OS ([\d_]+)/i', $userAgent, $match)) {
                $parts[] = 'iOS '.str_replace('_', '.', $match[1]);
            }
        } elseif (stripos($userAgent, 'iPad') !== false) {
            $parts[] = 'iPad';
            if (preg_match('/CPU OS ([\d_]+)/i', $userAgent, $match)) {
                $parts[] = 'iPadOS '.str_replace('_', '.', $match[1]);
            }
        } elseif (preg_match('/Windows NT ([\d.]+)/i', $userAgent, $match)) {
            $parts[] = self::windowsName($match[1]);
        } elseif (preg_match('/Mac OS X ([\d_]+)/i', $userAgent, $match)) {
            $parts[] = 'macOS '.str_replace('_', '.', $match[1]);
        } elseif (stripos($userAgent, 'CrOS') !== false) {
            $parts[] = 'ChromeOS';
        } elseif (stripos($userAgent, 'Linux') !== false) {
            $parts[] = 'Linux';
        }

        $browser = self::browser($userAgent);
        if ($browser !== null) {
            $parts[] = $browser;
        }

        return $parts === [] ? 'Unknown device' : implode(' · ', array_unique($parts));
    }

    private static function androidModel(string $userAgent): ?string
    {
        if (! preg_match('/Android[^;)]*;\s*(?:[a-z]{2}(?:-[a-z]{2})?;\s*)?([^;)]*?)(?:\s+Build\/[^;)]*)?[;)]/i', $userAgent, $match)) {
            return null;
        }

        $model = trim((string) preg_replace('/\s+/', ' ', $match[1]));
        $model = trim((string) preg_replace('/[^A-Za-z0-9._+() -]/', '', $model));

        if ($model === '' || in_array(strtolower($model), ['k', 'mobile', 'wv'], true)) {
            return null;
        }

        return mb_substr($model, 0, 60);
    }

    private static function browser(string $userAgent): ?string
    {
        return match (true) {
            preg_match('/SamsungBrowser\//i', $userAgent) === 1 => 'Samsung Internet',
            preg_match('/EdgA?\//i', $userAgent) === 1,
            preg_match('/EdgiOS\//i', $userAgent) === 1 => 'Edge',
            preg_match('/OPR\//i', $userAgent) === 1,
            preg_match('/Opera Mini\//i', $userAgent) === 1 => 'Opera',
            preg_match('/CriOS\//i', $userAgent) === 1,
            preg_match('/Chrome\//i', $userAgent) === 1 => 'Chrome',
            preg_match('/FxiOS\//i', $userAgent) === 1,
            preg_match('/Firefox\//i', $userAgent) === 1 => 'Firefox',
            preg_match('/Safari\//i', $userAgent) === 1 => 'Safari',
            default => null,
        };
    }

    private static function windowsName(string $version): string
    {
        return match ($version) {
            '10.0' => 'Windows 10/11',
            '6.3' => 'Windows 8.1',
            '6.2' => 'Windows 8',
            '6.1' => 'Windows 7',
            default => 'Windows',
        };
    }
}
