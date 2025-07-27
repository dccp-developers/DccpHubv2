<?php

namespace App\Enums;

enum RouteStatus: string
{
    case ACTIVE = 'active';
    case DISABLED = 'disabled';
    case MAINTENANCE = 'maintenance';
    case DEVELOPMENT = 'development';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::DISABLED => 'Disabled',
            self::MAINTENANCE => 'Under Maintenance',
            self::DEVELOPMENT => 'In Development',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ACTIVE => 'Route is fully functional and accessible to users',
            self::DISABLED => 'Route is temporarily disabled and will show a disabled message',
            self::MAINTENANCE => 'Route is under maintenance and will show a maintenance message',
            self::DEVELOPMENT => 'Route is in development and will show a development message',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::DISABLED => 'danger',
            self::MAINTENANCE => 'warning',
            self::DEVELOPMENT => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-o-check-circle',
            self::DISABLED => 'heroicon-o-x-circle',
            self::MAINTENANCE => 'heroicon-o-wrench-screwdriver',
            self::DEVELOPMENT => 'heroicon-o-code-bracket',
        };
    }

    public function getModalTitle(): string
    {
        return match ($this) {
            self::ACTIVE => 'Route Active',
            self::DISABLED => 'Route Disabled',
            self::MAINTENANCE => 'Under Maintenance',
            self::DEVELOPMENT => 'Feature in Development',
        };
    }

    public function getModalMessage(): string
    {
        return match ($this) {
            self::ACTIVE => 'This route is currently active and accessible.',
            self::DISABLED => 'This feature has been temporarily disabled. Please try again later or contact support if you need immediate access.',
            self::MAINTENANCE => 'This feature is currently under maintenance. We apologize for the inconvenience and will have it back online shortly.',
            self::DEVELOPMENT => 'This feature is currently in development. Stay tuned for updates!',
        };
    }

    public function isAccessible(): bool
    {
        return $this === self::ACTIVE;
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    public static function getAccessibleStatuses(): array
    {
        return [self::ACTIVE];
    }

    public static function getRestrictedStatuses(): array
    {
        return [self::DISABLED, self::MAINTENANCE, self::DEVELOPMENT];
    }
}
