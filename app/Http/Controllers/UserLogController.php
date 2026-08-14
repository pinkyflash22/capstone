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
        $perPage = (int) min(max($request->integer('limit', 10), 1), 50);

        $logs = UserLog::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate($perPage, [
                'id as user_log_id',
                'action',
                'ip_address',
                'user_agent',
                'created_at',
            ]);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string|max:50',
        ]);

        if (!UserActivityLogger::shouldLog($validated['action'])) {
            return response()->noContent();
        }

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

        $perPage = (int) min(max($request->integer('limit', 10), 1), 50);

        $logs = UserLog::query()
            ->with(['user.role'])
            ->orderByDesc('created_at')
            ->paginate($perPage, [
                'id as user_log_id',
                'user_id',
                'action',
                'ip_address',
                'user_agent',
                'created_at',
            ]);

        $logs->setCollection(
            $logs->getCollection()->map(function (UserLog $log) {
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
            })
        );

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
        ]);
    }
}
