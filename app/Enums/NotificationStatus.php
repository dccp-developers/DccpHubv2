<?php

namespace App\Enums;

enum NotificationStatus: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case SENT = 'sent';
    case FAILED = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SCHEDULED => 'Scheduled',
            self::SENT => 'Sent',
            self::FAILED => 'Failed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SCHEDULED => 'warning',
            self::SENT => 'success',
            self::FAILED => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-document',
            self::SCHEDULED => 'heroicon-o-clock',
            self::SENT => 'heroicon-o-check-circle',
            self::FAILED => 'heroicon-o-x-circle',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
