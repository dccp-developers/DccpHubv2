<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case FACULTY = 'faculty';
    case STUDENT = 'student';
    case GUEST = 'guest';
    case STAFF = 'staff';

    public function getLabel(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::FACULTY => 'Faculty Member',
            self::STUDENT => 'Student',
            self::GUEST => 'Guest User',
            self::STAFF => 'Staff Member',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ADMIN => 'Full system access with administrative privileges',
            self::FACULTY => 'Faculty member with teaching and academic privileges',
            self::STUDENT => 'Student with access to academic resources and enrollment',
            self::GUEST => 'Limited access guest user, typically pending enrollment',
            self::STAFF => 'Staff member with operational access',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ADMIN => 'danger',
            self::FACULTY => 'success',
            self::STUDENT => 'primary',
            self::GUEST => 'warning',
            self::STAFF => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::ADMIN => 'heroicon-o-shield-check',
            self::FACULTY => 'heroicon-o-user-group',
            self::STUDENT => 'heroicon-o-academic-cap',
            self::GUEST => 'heroicon-o-user',
            self::STAFF => 'heroicon-o-briefcase',
        };
    }

    public function getPermissions(): array
    {
        return match ($this) {
            self::ADMIN => [
                'manage_users',
                'manage_system',
                'view_all_data',
                'manage_settings',
                'manage_routes',
                'manage_notifications',
            ],
            self::FACULTY => [
                'view_students',
                'manage_classes',
                'view_schedules',
                'manage_grades',
                'view_faculty_notifications',
            ],
            self::STUDENT => [
                'view_own_data',
                'view_schedules',
                'view_grades',
                'manage_enrollment',
                'view_notifications',
            ],
            self::GUEST => [
                'view_public_content',
                'submit_enrollment',
            ],
            self::STAFF => [
                'view_operational_data',
                'manage_records',
                'view_reports',
            ],
        };
    }

    public function canAccessPanel(): bool
    {
        return match ($this) {
            self::ADMIN, self::STAFF => true,
            self::FACULTY, self::STUDENT, self::GUEST => false,
        };
    }

    public function getDefaultPersonType(): ?string
    {
        return match ($this) {
            self::FACULTY => \App\Models\Faculty::class,
            self::STUDENT => \App\Models\Students::class,
            self::ADMIN, self::STAFF, self::GUEST => null,
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    public static function getAdministrativeRoles(): array
    {
        return [self::ADMIN, self::STAFF];
    }

    public static function getAcademicRoles(): array
    {
        return [self::FACULTY, self::STUDENT];
    }

    public static function getActiveRoles(): array
    {
        return [self::ADMIN, self::FACULTY, self::STUDENT, self::STAFF];
    }
}
