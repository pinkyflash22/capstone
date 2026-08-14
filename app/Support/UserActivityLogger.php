<?php

namespace App\Support;

use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class UserActivityLogger
{
    /**
     * Read-only navigation must never create an audit record.
     */
    public static function shouldLog(string $action): bool
    {
        $normalizedAction = strtolower(trim((string) preg_replace('/\s+/', ' ', $action)));

        return ! preg_match('/^(?:view(?:ed|ing)?|open(?:ed|ing)?|load(?:ed|ing)?|access(?:ed|ing)?|read(?:ing)?|visit(?:ed|ing)?|navigate(?:d|ing)?)(?:\s|$)/', $normalizedAction);
    }

    public static function log(string $action, ?int $userId = null, ?Request $request = null): void
    {
        $resolvedUserId = $userId ?? Auth::id();

        if (!self::shouldLog($action) || !$resolvedUserId || !Schema::hasTable('user_logs')) {
            return;
        }

        try {
            UserLog::create([
                'user_id' => $resolvedUserId,
                'action' => mb_substr($action, 0, 50),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
