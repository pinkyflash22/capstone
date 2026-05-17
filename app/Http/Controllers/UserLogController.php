<?php

namespace App\Http\Controllers;

use App\Models\UserLog;
use App\Support\UserActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = UserLog::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->limit((int) min(max($request->integer('limit', 100), 1), 200))
            ->get([
                'user_log_id',
                'action',
                'ip_address',
                'user_agent',
                'created_at',
            ]);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string|max:50',
        ]);

        UserActivityLogger::log($validated['action'], auth()->id(), $request);

        return response()->json([
            'success' => true,
            'message' => 'User action logged successfully.',
        ]);
    }

    public function all(Request $request)
    {
        $user = Auth::user();
        $roleName = strtolower($user?->role?->role ?? '');

        abort_unless(in_array($roleName, ['chief', 'station admin'], true), 403);

        $logs = UserLog::query()
            ->with(['user.role'])
            ->orderByDesc('created_at')
            ->get([
                'user_log_id',
                'user_id',
                'action',
                'ip_address',
                'user_agent',
                'created_at',
            ]);

        return response()->json([
            'success' => true,
            'data' => $logs->map(function (UserLog $log) {
                return [
                    'user_log_id' => $log->user_log_id,
                    'user_id' => $log->user_id,
                    'action' => $log->action,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'created_at' => $log->created_at,
                    'user_name' => trim(($log->user?->first_name ?? '') . ' ' . ($log->user?->last_name ?? '')) ?: 'Unknown user',
                    'role' => $log->user?->role?->role ?? 'Unknown role',
                    'email' => $log->user?->email,
                ];
            }),
        ]);
    }
}
