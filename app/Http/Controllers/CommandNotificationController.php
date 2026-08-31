<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommandNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $limit = min(max($request->integer('limit', 12), 1), 30);

        $notifications = $user->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($notification) {
                $data = is_array($notification->data) ? $notification->data : [];

                return [
                    'id' => $notification->id,
                    'title' => $data['title'] ?? 'Command update',
                    'message' => $data['message'] ?? '',
                    'category' => $data['category'] ?? 'general',
                    'url' => $data['url'] ?? null,
                    'is_read' => $notification->read_at !== null,
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
        $notification = Auth::user()->notifications()->where('id', $notificationId)->firstOrFail();
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllAsRead(): JsonResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true, 'unread_count' => 0]);
    }
}
