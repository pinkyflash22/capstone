<?php

namespace App\Support;

use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class UserActivityLogger
{
    public static function log(string $action, ?int $userId = null, ?Request $request = null): void
    {
        $resolvedUserId = $userId ?? Auth::id();

        if (!$resolvedUserId || !Schema::hasTable('user_logs')) {
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
