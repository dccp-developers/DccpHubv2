<?php

namespace App\Helpers;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Collection;

class NotificationHelper
{
    protected static NotificationService $notificationService;

    /**
     * Get the notification service instance.
     */
    protected static function getService(): NotificationService
    {
        if (!isset(self::$notificationService)) {
            self::$notificationService = app(NotificationService::class);
        }
        
        return self::$notificationService;
    }

    /**
     * Send an info notification.
     */
    public static function info(
        User|Collection $users,
        string $title,
        string $message,
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'normal',
        ?\DateTime $expiresAt = null
    ) {
        return self::send($users, $title, $message, 'info', $data, $actionUrl, $actionText, $priority, $expiresAt);
    }

    /**
     * Send a success notification.
     */
    public static function success(
        User|Collection $users,
        string $title,
        string $message,
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'normal',
        ?\DateTime $expiresAt = null
    ) {
        return self::send($users, $title, $message, 'success', $data, $actionUrl, $actionText, $priority, $expiresAt);
    }

    /**
     * Send a warning notification.
     */
    public static function warning(
        User|Collection $users,
        string $title,
        string $message,
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'high',
        ?\DateTime $expiresAt = null
    ) {
        return self::send($users, $title, $message, 'warning', $data, $actionUrl, $actionText, $priority, $expiresAt);
    }

    /**
     * Send an error notification.
     */
    public static function error(
        User|Collection $users,
        string $title,
        string $message,
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'urgent',
        ?\DateTime $expiresAt = null
    ) {
        return self::send($users, $title, $message, 'error', $data, $actionUrl, $actionText, $priority, $expiresAt);
    }

    /**
     * Send a notification to faculty members.
     */
    public static function toFaculty(
        string $title,
        string $message,
        string $type = 'info',
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'normal',
        ?\DateTime $expiresAt = null
    ) {
        return self::getService()->sendToAllFaculty(
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
     * Send a notification to specific users by role.
     */
    public static function toRole(
        string $role,
        string $title,
        string $message,
        string $type = 'info',
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'normal',
        ?\DateTime $expiresAt = null
    ) {
        $users = User::where('role', $role)->get();
        
        return self::getService()->sendToUsers(
            $users,
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
     * Send a notification to users by email.
     */
    public static function toEmails(
        array $emails,
        string $title,
        string $message,
        string $type = 'info',
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'normal',
        ?\DateTime $expiresAt = null
    ) {
        $users = User::whereIn('email', $emails)->get();
        
        return self::getService()->sendToUsers(
            $users,
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
     * Send a notification to users by IDs.
     */
    public static function toUserIds(
        array $userIds,
        string $title,
        string $message,
        string $type = 'info',
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'normal',
        ?\DateTime $expiresAt = null
    ) {
        $users = User::whereIn('id', $userIds)->get();
        
        return self::getService()->sendToUsers(
            $users,
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
     * Send a notification (main method).
     */
    protected static function send(
        User|Collection $users,
        string $title,
        string $message,
        string $type = 'info',
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'normal',
        ?\DateTime $expiresAt = null
    ) {
        if ($users instanceof User) {
            return self::getService()->sendToUser(
                $users,
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

        return self::getService()->sendToUsers(
            $users,
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
     * Quick notification methods for common scenarios.
     */
    
    /**
     * Notify about a new enrollment.
     */
    public static function newEnrollment(User $user, array $enrollmentData)
    {
        return self::info(
            $user,
            'New Enrollment Submitted',
            'A new student enrollment has been submitted and requires your review.',
            $enrollmentData,
            route('admin.enrollments.pending'),
            'Review Enrollment',
            'high'
        );
    }

    /**
     * Notify about a class schedule change.
     */
    public static function scheduleChange(Collection $users, string $className, string $details)
    {
        return self::warning(
            $users,
            'Class Schedule Updated',
            "The schedule for {$className} has been updated. {$details}",
            ['class' => $className, 'details' => $details],
            route('faculty.schedule.index'),
            'View Schedule',
            'high'
        );
    }

    /**
     * Notify about system maintenance.
     */
    public static function systemMaintenance(string $startTime, string $duration)
    {
        return self::toRole(
            'faculty',
            'Scheduled System Maintenance',
            "System maintenance is scheduled for {$startTime} and will last approximately {$duration}. Please save your work before this time.",
            'warning',
            ['start_time' => $startTime, 'duration' => $duration],
            null,
            null,
            'urgent'
        );
    }

    /**
     * Notify about grade submission deadline.
     */
    public static function gradeDeadline(Collection $facultyUsers, string $deadline)
    {
        return self::warning(
            $facultyUsers,
            'Grade Submission Deadline Reminder',
            "Reminder: Final grades must be submitted by {$deadline}. Please ensure all grades are entered and finalized.",
            ['deadline' => $deadline],
            route('faculty.grades.index'),
            'Submit Grades',
            'high'
        );
    }
}
