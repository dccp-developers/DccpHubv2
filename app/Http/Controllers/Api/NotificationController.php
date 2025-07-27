<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 15);
        $unreadOnly = $request->boolean('unread_only', false);

        $notifications = $this->notificationService->getUserNotifications(
            $user->id,
            $perPage,
            $unreadOnly
        );

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $this->notificationService->getUnreadCount($user->id),
        ]);
    }

    /**
     * Get unread notification count.
     */
    public function unreadCount(): JsonResponse
    {
        $user = Auth::user();
        $count = $this->notificationService->getUnreadCount($user->id);

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(int $id): JsonResponse
    {
        $user = Auth::user();
        $success = $this->notificationService->markAsRead($id, $user->id);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found or already read.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'unread_count' => $this->notificationService->getUnreadCount($user->id),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();
        $count = $this->notificationService->markAllAsRead($user->id);

        return response()->json([
            'success' => true,
            'message' => "Marked {$count} notifications as read.",
            'count' => $count,
            'unread_count' => 0,
        ]);
    }

    /**
     * Clear all notifications.
     */
    public function clearAll(): JsonResponse
    {
        $user = Auth::user();
        $count = $this->notificationService->clearAll($user->id);

        return response()->json([
            'success' => true,
            'message' => "Cleared {$count} notifications.",
            'count' => $count,
            'unread_count' => 0,
        ]);
    }

    /**
     * Send a test notification.
     */
    public function sendTest(): JsonResponse
    {
        $user = Auth::user();
        $notification = $this->notificationService->sendTestNotification($user);

        return response()->json([
            'success' => true,
            'message' => 'Test notification sent successfully.',
            'notification' => $notification,
            'unread_count' => $this->notificationService->getUnreadCount($user->id),
        ]);
    }
}
