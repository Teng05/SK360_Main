<?php

// File guide: Handles route logic and page data for app/Http/Controllers/NotificationController.php.

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function feed(): JsonResponse
    {
        abort_unless(auth()->check(), 403);

        $user = auth()->user();

        $notifications = Notification::query()
            ->where('user_id', $user->user_id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(function (Notification $notification) {
                return [
                    'id' => $notification->notification_id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'url' => $notification->url,
                    'is_read' => $notification->is_read,
                    'created_at' => optional($notification->created_at)->diffForHumans(),
                ];
            });

        return response()->json([
            'unread_count' => Notification::query()
                ->where('user_id', $user->user_id)
                ->where('is_read', 0)
                ->count(),
            'notifications' => $notifications,
        ]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_unless(auth()->check(), 403);
        abort_unless((int) $notification->user_id === (int) auth()->user()->user_id, 403);

        if (!$notification->is_read) {
            $notification->update([
                'is_read' => 1,
                'read_at' => now(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
