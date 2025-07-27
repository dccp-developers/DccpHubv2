<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case PENDING_VERIFICATION = 'pending_verification';
    case LOCKED = 'locked';
    case ARCHIVED = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::PENDING_VERIFICATION => 'Pending Verification',
            self::LOCKED => 'Locked',
            self::ARCHIVED => 'Archived',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ACTIVE => 'Account is active and fully functional',
            self::INACTIVE => 'Account is temporarily inactive',
            self::SUSPENDED => 'Account has been suspended due to policy violations',
            self::PENDING_VERIFICATION => 'Account is awaiting email verification',
            self::LOCKED => 'Account is locked due to security concerns',
            self::ARCHIVED => 'Account has been archived and is no longer in use',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'gray',
            self::SUSPENDED => 'danger',
            self::PENDING_VERIFICATION => 'warning',
            self::LOCKED => 'danger',
            self::ARCHIVED => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-o-check-circle',
            self::INACTIVE => 'heroicon-o-pause-circle',
            self::SUSPENDED => 'heroicon-o-no-symbol',
            self::PENDING_VERIFICATION => 'heroicon-o-clock',
            self::LOCKED => 'heroicon-o-lock-closed',
            self::ARCHIVED => 'heroicon-o-archive-box',
        };
    }

    public function canLogin(): bool
    {
        return match ($this) {
            self::ACTIVE => true,
            self::INACTIVE, self::SUSPENDED, self::PENDING_VERIFICATION, self::LOCKED, self::ARCHIVED => false,
        };
    }

    public function canReceiveNotifications(): bool
    {
        return match ($this) {
            self::ACTIVE, self::PENDING_VERIFICATION => true,
            self::INACTIVE, self::SUSPENDED, self::LOCKED, self::ARCHIVED => false,
        };
    }

    public function requiresAction(): bool
    {
        return match ($this) {
            self::PENDING_VERIFICATION, self::LOCKED => true,
            self::ACTIVE, self::INACTIVE, self::SUSPENDED, self::ARCHIVED => false,
        };
    }

    public function getActionMessage(): ?string
    {
        return match ($this) {
            self::PENDING_VERIFICATION => 'Please verify your email address to activate your account.',
            self::LOCKED => 'Your account has been locked. Please contact support for assistance.',
            self::SUSPENDED => 'Your account has been suspended. Please contact administration.',
            self::INACTIVE => 'Your account is currently inactive.',
            self::ARCHIVED => 'This account has been archived.',
            self::ACTIVE => null,
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    public static function getActiveStatuses(): array
    {
        return [self::ACTIVE];
    }

    public static function getInactiveStatuses(): array
    {
        return [self::INACTIVE, self::SUSPENDED, self::LOCKED, self::ARCHIVED];
    }

    public static function getPendingStatuses(): array
    {
        return [self::PENDING_VERIFICATION];
    }

    public static function getRestrictedStatuses(): array
    {
        return [self::SUSPENDED, self::LOCKED];
    }
}
