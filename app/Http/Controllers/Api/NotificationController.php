<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * NotificationController
 *
 * Manages in-app (database) notifications for the authenticated user.
 *
 * Routes:
 *   GET    /api/v1/notifications              → index
 *   POST   /api/v1/notifications/{id}/read    → markRead
 *   POST   /api/v1/notifications/read-all     → markAllRead
 *   DELETE /api/v1/notifications/{id}         → destroy
 *   DELETE /api/v1/notifications              → destroyAll (read only)
 */
class NotificationController extends Controller
{
    /**
     * List all notifications for the authenticated user.
     * Unread first, then read — paginated 20 per page.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->orderByRaw('read_at IS NOT NULL')  // unread first
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data'        => $notifications->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->data['type']  ?? null,
                'title'      => $n->data['title'] ?? null,
                'body'       => $n->data['body']  ?? null,
                'url'        => $n->data['url']   ?? null,
                'data'       => $n->data['data']  ?? [],
                'read'       => ! is_null($n->read_at),
                'read_at'    => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ]),
            'unread_count'=> $request->user()->unreadNotifications()->count(),
            'total'       => $notifications->total(),
            'per_page'    => $notifications->perPage(),
            'current_page'=> $notifications->currentPage(),
            'last_page'   => $notifications->lastPage(),
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'message'     => 'Notification marked as read.',
            'unread_count'=> $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message'     => 'All notifications marked as read.',
            'unread_count'=> 0,
        ]);
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $deleted = $request->user()
            ->notifications()
            ->where('id', $id)
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        return response()->json(['message' => 'Notification deleted.']);
    }

    /**
     * Delete all READ notifications (keeps unread).
     */
    public function destroyRead(Request $request): JsonResponse
    {
        $count = $request->user()
            ->notifications()
            ->whereNotNull('read_at')
            ->delete();

        return response()->json([
            'message' => "{$count} read notification(s) deleted.",
        ]);
    }
}
