<?php

namespace App\Enums;

enum RouteType: string
{
    case STUDENT = 'student';
    case FACULTY = 'faculty';
    case ADMIN = 'admin';
    case PUBLIC = 'public';
    case API = 'api';
    case GUEST = 'guest';
    case ENROLLMENT = 'enrollment';

    public function getLabel(): string
    {
        return match ($this) {
            self::STUDENT => 'Student Portal',
            self::FACULTY => 'Faculty Portal',
            self::ADMIN => 'Admin Panel',
            self::PUBLIC => 'Public Access',
            self::API => 'API Endpoint',
            self::GUEST => 'Guest Access',
            self::ENROLLMENT => 'Enrollment System',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::STUDENT => 'Routes accessible to authenticated students',
            self::FACULTY => 'Routes accessible to faculty members',
            self::ADMIN => 'Routes accessible to administrators only',
            self::PUBLIC => 'Routes accessible to everyone without authentication',
            self::API => 'API endpoints for mobile and external applications',
            self::GUEST => 'Routes accessible to guest users',
            self::ENROLLMENT => 'Routes for the enrollment process',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::STUDENT => 'primary',
            self::FACULTY => 'success',
            self::ADMIN => 'danger',
            self::PUBLIC => 'gray',
            self::API => 'info',
            self::GUEST => 'warning',
            self::ENROLLMENT => 'purple',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::STUDENT => 'heroicon-o-academic-cap',
            self::FACULTY => 'heroicon-o-user-group',
            self::ADMIN => 'heroicon-o-shield-check',
            self::PUBLIC => 'heroicon-o-globe-alt',
            self::API => 'heroicon-o-code-bracket',
            self::GUEST => 'heroicon-o-user',
            self::ENROLLMENT => 'heroicon-o-clipboard-document-list',
        };
    }

    public function getRequiredRole(): ?string
    {
        return match ($this) {
            self::STUDENT => 'student',
            self::FACULTY => 'faculty',
            self::ADMIN => 'admin',
            self::PUBLIC => null,
            self::API => null,
            self::GUEST => null,
            self::ENROLLMENT => null,
        };
    }

    public function getRequiredMiddleware(): array
    {
        return match ($this) {
            self::STUDENT => ['auth:sanctum', 'role:student'],
            self::FACULTY => ['auth:sanctum', 'role:faculty'],
            self::ADMIN => ['auth:sanctum', 'role:admin'],
            self::PUBLIC => [],
            self::API => ['api'],
            self::GUEST => ['guest'],
            self::ENROLLMENT => ['web'],
        };
    }

    public function getDefaultLayout(): string
    {
        return match ($this) {
            self::STUDENT => 'AppLayout',
            self::FACULTY => 'FacultyLayout',
            self::ADMIN => 'AdminLayout',
            self::PUBLIC => 'PublicLayout',
            self::API => null,
            self::GUEST => 'GuestLayout',
            self::ENROLLMENT => 'EnrollmentLayout',
        };
    }

    public function requiresAuthentication(): bool
    {
        return match ($this) {
            self::STUDENT, self::FACULTY, self::ADMIN => true,
            self::PUBLIC, self::API, self::GUEST, self::ENROLLMENT => false,
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    public static function getAuthenticatedTypes(): array
    {
        return [self::STUDENT, self::FACULTY, self::ADMIN];
    }

    public static function getPublicTypes(): array
    {
        return [self::PUBLIC, self::GUEST, self::ENROLLMENT];
    }

    public static function getPortalTypes(): array
    {
        return [self::STUDENT, self::FACULTY, self::ADMIN];
    }
}
