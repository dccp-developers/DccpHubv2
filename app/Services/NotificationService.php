<?php

namespace App\Services;

use App\Models\User;
use App\Models\FacultyNotification;
use App\Models\StudentNotification;
use App\Notifications\FacultyNotificationAlert;
use App\Notifications\StudentNotificationAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Send a notification to a user.
     */
    public function sendToUser(
        User $user,
        string $title,
        string $message,
        string $type = 'info',
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'normal',
        ?\DateTime $expiresAt = null
    ): FacultyNotification|StudentNotification {
        // Determine notification type based on user role
        if ($user->role === 'faculty') {
            return $this->sendToFaculty($user, $title, $message, $type, $data, $actionUrl, $actionText, $priority, $expiresAt);
        } else {
            return $this->sendToStudent($user, $title, $message, $type, $data, $actionUrl, $actionText, $priority, $expiresAt);
        }
    }

    /**
     * Send a notification to a faculty user.
     */
    public function sendToFaculty(
        User $user,
        string $title,
        string $message,
        string $type = 'info',
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'normal',
        ?\DateTime $expiresAt = null
    ): FacultyNotification {
        // Create the database record
        $notification = FacultyNotification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data,
            'action_url' => $actionUrl,
            'action_text' => $actionText,
            'priority' => $priority,
            'expires_at' => $expiresAt,
        ]);

        // Send the Laravel notification for real-time updates and web push
        $user->notify(new FacultyNotificationAlert(
            $title,
            $message,
            $type,
            $data,
            $actionUrl,
            $actionText,
            $priority
        ));

        return $notification;
    }

    /**
     * Send a notification to a student user.
     */
    public function sendToStudent(
        User $user,
        string $title,
        string $message,
        string $type = 'info',
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'normal',
        ?\DateTime $expiresAt = null
    ): StudentNotification {
        // Create the database record
        $notification = StudentNotification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data,
            'action_url' => $actionUrl,
            'action_text' => $actionText,
            'priority' => $priority,
            'expires_at' => $expiresAt,
        ]);

        // Send the Laravel notification for real-time updates and web push
        $user->notify(new StudentNotificationAlert(
            $title,
            $message,
            $type,
            $data,
            $actionUrl,
            $actionText,
            $priority
        ));

        return $notification;
    }

    /**
     * Send a notification to multiple users.
     */
    public function sendToUsers(
        Collection $users,
        string $title,
        string $message,
        string $type = 'info',
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'normal',
        ?\DateTime $expiresAt = null
    ): Collection {
        $notifications = collect();

        foreach ($users as $user) {
            $notifications->push(
                $this->sendToUser(
                    $user,
                    $title,
                    $message,
                    $type,
                    $data,
                    $actionUrl,
                    $actionText,
                    $priority,
                    $expiresAt
                )
            );
        }

        return $notifications;
    }

    /**
     * Send a notification to all faculty members.
     */
    public function sendToAllFaculty(
        string $title,
        string $message,
        string $type = 'info',
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'normal',
        ?\DateTime $expiresAt = null
    ): Collection {
        $facultyUsers = User::where('role', 'faculty')->get();

        return $this->sendToUsers(
            $facultyUsers,
            $title,
            $message,
            $type,
            $data,
            $actionUrl,
            $actionText,
            $priority,
            $expiresAt
        );
    }

    /**
     * Send a notification to all students.
     */
    public function sendToAllStudents(
        string $title,
        string $message,
        string $type = 'info',
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'normal',
        ?\DateTime $expiresAt = null
    ): Collection {
        $studentUsers = User::where('role', 'student')->get();

        return $this->sendToUsers(
            $studentUsers,
            $title,
            $message,
            $type,
            $data,
            $actionUrl,
            $actionText,
            $priority,
            $expiresAt
        );
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        if ($user->role === 'faculty') {
            $notification = FacultyNotification::where('id', $notificationId)
                ->where('user_id', $userId)
                ->first();
        } else {
            $notification = StudentNotification::where('id', $notificationId)
                ->where('user_id', $userId)
                ->first();
        }

        if ($notification) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        $user = User::find($userId);
        if (!$user) {
            return 0;
        }

        if ($user->role === 'faculty') {
            return FacultyNotification::where('user_id', $userId)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        } else {
            return StudentNotification::where('user_id', $userId)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        }
    }

    /**
     * Clear all notifications for a user.
     */
    public function clearAll(int $userId): int
    {
        $user = User::find($userId);
        if (!$user) {
            return 0;
        }

        if ($user->role === 'faculty') {
            return FacultyNotification::where('user_id', $userId)->delete();
        } else {
            return StudentNotification::where('user_id', $userId)->delete();
        }
    }

    /**
     * Get notifications for a user with pagination.
     */
    public function getUserNotifications(
        int $userId,
        int $perPage = 15,
        bool $unreadOnly = false
    ) {
        $user = User::find($userId);
        if (!$user) {
            return collect();
        }

        if ($user->role === 'faculty') {
            $query = FacultyNotification::where('user_id', $userId)
                ->notExpired()
                ->orderBy('created_at', 'desc');
        } else {
            $query = StudentNotification::where('user_id', $userId)
                ->notExpired()
                ->orderBy('created_at', 'desc');
        }

        if ($unreadOnly) {
            $query->unread();
        }

        return $query->paginate($perPage);
    }

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        $user = User::find($userId);
        if (!$user) {
            return 0;
        }

        if ($user->role === 'faculty') {
            return FacultyNotification::where('user_id', $userId)
                ->unread()
                ->notExpired()
                ->count();
        } else {
            return StudentNotification::where('user_id', $userId)
                ->unread()
                ->notExpired()
                ->count();
        }
    }

    /**
     * Send a test notification.
     */
    public function sendTestNotification(User $user): FacultyNotification|StudentNotification
    {
        $types = ['info', 'success', 'warning', 'error'];
        $priorities = ['low', 'normal', 'high', 'urgent'];

        $type = $types[array_rand($types)];
        $priority = $priorities[array_rand($priorities)];

        $messages = [
            'info' => 'This is a test information notification.',
            'success' => 'Test notification sent successfully!',
            'warning' => 'This is a test warning notification.',
            'error' => 'This is a test error notification.',
        ];

        $dashboardRoute = $user->role === 'faculty' ? 'faculty.dashboard' : 'dashboard';

        return $this->sendToUser(
            $user,
            'Test Notification',
            $messages[$type],
            $type,
            ['test' => true, 'timestamp' => now()->toISOString()],
            route($dashboardRoute),
            'View Dashboard',
            $priority
        );
    }
}
