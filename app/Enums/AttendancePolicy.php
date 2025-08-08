<?php

declare(strict_types=1);

namespace App\Enums;

enum AttendancePolicy: string
{
    case PRESENT_BY_DEFAULT = 'present_by_default';
    case ABSENT_BY_DEFAULT = 'absent_by_default';
    case REQUIRE_CHECK_IN = 'require_check_in';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT_BY_DEFAULT => 'Present by Default',
            self::ABSENT_BY_DEFAULT => 'Absent by Default',
            self::REQUIRE_CHECK_IN => 'Require Check-in',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PRESENT_BY_DEFAULT => 'Students are marked present by default. Teacher marks absent students only.',
            self::ABSENT_BY_DEFAULT => 'Students are marked absent by default. Teacher marks present students only.',
            self::REQUIRE_CHECK_IN => 'Students must actively check-in to be marked present.',
        };
    }

    public function defaultStatus(): AttendanceStatus
    {
        return match ($this) {
            self::PRESENT_BY_DEFAULT => AttendanceStatus::PRESENT,
            self::ABSENT_BY_DEFAULT => AttendanceStatus::ABSENT,
            self::REQUIRE_CHECK_IN => AttendanceStatus::ABSENT,
        };
    }

    public static function options(): array
    {
        return array_map(fn($policy) => [
            'value' => $policy->value,
            'label' => $policy->label(),
            'description' => $policy->description(),
            'default_status' => $policy->defaultStatus()->value,
        ], self::cases());
    }
}
