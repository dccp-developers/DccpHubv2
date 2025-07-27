<?php

namespace App\Enums;

enum NotificationPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::NORMAL => 'Normal',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::LOW => 'gray',
            self::NORMAL => 'primary',
            self::HIGH => 'warning',
            self::URGENT => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::LOW => 'heroicon-o-minus',
            self::NORMAL => 'heroicon-o-information-circle',
            self::HIGH => 'heroicon-o-exclamation-triangle',
            self::URGENT => 'heroicon-o-fire',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
