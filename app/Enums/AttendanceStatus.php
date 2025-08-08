<?php

declare(strict_types=1);

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case LATE = 'late';
    case EXCUSED = 'excused';
    case PARTIAL = 'partial';

    /**
     * Get the display label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::PRESENT => 'Present',
            self::ABSENT => 'Absent',
            self::LATE => 'Late',
            self::EXCUSED => 'Excused',
            self::PARTIAL => 'Partial',
        };
    }

    /**
     * Get the color class for UI display
     */
    public function color(): string
    {
        return match($this) {
            self::PRESENT => 'success',
            self::LATE => 'warning',
            self::PARTIAL => 'info',
            self::EXCUSED => 'secondary',
            self::ABSENT => 'destructive',
        };
    }

    /**
     * Get the icon for the status
     */
    public function icon(): string
    {
        return match($this) {
            self::PRESENT => 'check-circle',
            self::ABSENT => 'x-circle',
            self::LATE => 'clock',
            self::EXCUSED => 'shield-check',
            self::PARTIAL => 'minus-circle',
        };
    }

    /**
     * Check if the status counts as present
     */
    public function isPresent(): bool
    {
        return in_array($this, [self::PRESENT, self::LATE, self::PARTIAL]);
    }

    /**
     * Check if the status counts as absent
     */
    public function isAbsent(): bool
    {
        return $this === self::ABSENT;
    }

    /**
     * Get all status options for forms
     */
    public static function options(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
                'icon' => $status->icon(),
            ],
            self::cases()
        );
    }

    /**
     * Get statuses that count as present
     */
    public static function presentStatuses(): array
    {
        return [self::PRESENT, self::LATE, self::PARTIAL];
    }

    /**
     * Get statuses that count as absent
     */
    public static function absentStatuses(): array
    {
        return [self::ABSENT];
    }
}
