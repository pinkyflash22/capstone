<?php

namespace App\Http\Controllers\Barangay;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangayNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->latest()
            ->limit((int) min(max($request->integer('limit', 8), 1), 20))
            ->get()
            ->map(function ($notification) {
                $data = is_array($notification->data) ? $notification->data : [];

                return [
                    'id' => $notification->id,
                    'title' => $data['title'] ?? 'Barangay notification',
                    'message' => $data['message'] ?? '',
                    'category' => $data['category'] ?? 'general',
                    'icon' => $data['icon'] ?? 'bi-bell-fill',
                    'url' => $data['url'] ?? null,
                    'meta' => $data['meta'] ?? [],
                    'is_read' => $notification->read_at !== null,
                    'created_at' => optional($notification->created_at)->toIso8601String(),
                    'created_at_human' => optional($notification->created_at)->diffForHumans(),
                ];
            })
            ->values();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(string $notificationId): JsonResponse
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllAsRead(): JsonResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }
}
