<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Helpers\NotificationHelper;

/**
 * @method static mixed info(\App\Models\User|\Illuminate\Support\Collection $users, string $title, string $message, ?array $data = null, ?string $actionUrl = null, ?string $actionText = null, string $priority = 'normal', ?\DateTime $expiresAt = null)
 * @method static mixed success(\App\Models\User|\Illuminate\Support\Collection $users, string $title, string $message, ?array $data = null, ?string $actionUrl = null, ?string $actionText = null, string $priority = 'normal', ?\DateTime $expiresAt = null)
 * @method static mixed warning(\App\Models\User|\Illuminate\Support\Collection $users, string $title, string $message, ?array $data = null, ?string $actionUrl = null, ?string $actionText = null, string $priority = 'high', ?\DateTime $expiresAt = null)
 * @method static mixed error(\App\Models\User|\Illuminate\Support\Collection $users, string $title, string $message, ?array $data = null, ?string $actionUrl = null, ?string $actionText = null, string $priority = 'urgent', ?\DateTime $expiresAt = null)
 * @method static mixed toFaculty(string $title, string $message, string $type = 'info', ?array $data = null, ?string $actionUrl = null, ?string $actionText = null, string $priority = 'normal', ?\DateTime $expiresAt = null)
 * @method static mixed toRole(string $role, string $title, string $message, string $type = 'info', ?array $data = null, ?string $actionUrl = null, ?string $actionText = null, string $priority = 'normal', ?\DateTime $expiresAt = null)
 * @method static mixed toEmails(array $emails, string $title, string $message, string $type = 'info', ?array $data = null, ?string $actionUrl = null, ?string $actionText = null, string $priority = 'normal', ?\DateTime $expiresAt = null)
 * @method static mixed toUserIds(array $userIds, string $title, string $message, string $type = 'info', ?array $data = null, ?string $actionUrl = null, ?string $actionText = null, string $priority = 'normal', ?\DateTime $expiresAt = null)
 * @method static mixed newEnrollment(\App\Models\User $user, array $enrollmentData)
 * @method static mixed scheduleChange(\Illuminate\Support\Collection $users, string $className, string $details)
 * @method static mixed systemMaintenance(string $startTime, string $duration)
 * @method static mixed gradeDeadline(\Illuminate\Support\Collection $facultyUsers, string $deadline)
 *
 * @see \App\Helpers\NotificationHelper
 */
class Notify extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return NotificationHelper::class;
    }
}
