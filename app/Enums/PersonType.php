<?php

declare(strict_types=1);

namespace App\Enums;

enum PersonType: string
{
    case STUDENT = 'App\\Models\\Students';
    case FACULTY = 'App\\Models\\Faculty';
    case SHS_STUDENT = 'App\\Models\\ShsStudents';
    case ADMIN = 'admin';
    case STAFF = 'staff';

    public function getLabel(): string
    {
        return match ($this) {
            self::STUDENT => 'College Student',
            self::FACULTY => 'Faculty Member',
            self::SHS_STUDENT => 'Senior High School Student',
            self::ADMIN => 'Administrator',
            self::STAFF => 'Staff Member',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::STUDENT => 'College-level student enrolled in degree programs',
            self::FACULTY => 'Faculty member teaching courses and managing academic activities',
            self::SHS_STUDENT => 'Senior High School student in grades 11-12',
            self::ADMIN => 'System administrator with full access privileges',
            self::STAFF => 'Administrative or support staff member',
        };
    }

    public function getModelClass(): ?string
    {
        return match ($this) {
            self::STUDENT => \App\Models\Students::class,
            self::FACULTY => \App\Models\Faculty::class,
            self::SHS_STUDENT => \App\Models\ShsStudents::class,
            self::ADMIN, self::STAFF => null,
        };
    }

    public function getDefaultRole(): UserRole
    {
        return match ($this) {
            self::STUDENT, self::SHS_STUDENT => UserRole::STUDENT,
            self::FACULTY => UserRole::FACULTY,
            self::ADMIN => UserRole::ADMIN,
            self::STAFF => UserRole::STAFF,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::STUDENT => 'primary',
            self::FACULTY => 'success',
            self::SHS_STUDENT => 'info',
            self::ADMIN => 'danger',
            self::STAFF => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::STUDENT => 'heroicon-o-academic-cap',
            self::FACULTY => 'heroicon-o-user-group',
            self::SHS_STUDENT => 'heroicon-o-book-open',
            self::ADMIN => 'heroicon-o-shield-check',
            self::STAFF => 'heroicon-o-briefcase',
        };
    }

    public function getIdentifierField(): ?string
    {
        return match ($this) {
            self::STUDENT => 'id',
            self::FACULTY => 'faculty_code',
            self::SHS_STUDENT => 'student_lrn',
            self::ADMIN, self::STAFF => null,
        };
    }

    public function getDisplayNameFields(): array
    {
        return match ($this) {
            self::STUDENT, self::SHS_STUDENT => ['first_name', 'last_name', 'middle_name'],
            self::FACULTY => ['first_name', 'last_name', 'middle_name'],
            self::ADMIN, self::STAFF => [],
        };
    }

    public function getEmailField(): ?string
    {
        return match ($this) {
            self::STUDENT, self::FACULTY, self::SHS_STUDENT => 'email',
            self::ADMIN, self::STAFF => null,
        };
    }

    public function hasPersonRecord(): bool
    {
        return match ($this) {
            self::STUDENT, self::FACULTY, self::SHS_STUDENT => true,
            self::ADMIN, self::STAFF => false,
        };
    }

    public function canHaveMultipleAccounts(): bool
    {
        return match ($this) {
            self::STUDENT, self::SHS_STUDENT => false,
            self::FACULTY, self::ADMIN, self::STAFF => true,
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    public static function getStudentTypes(): array
    {
        return [self::STUDENT, self::SHS_STUDENT];
    }

    public static function getStaffTypes(): array
    {
        return [self::FACULTY, self::ADMIN, self::STAFF];
    }

    public static function getTypesWithPersonRecords(): array
    {
        return [self::STUDENT, self::FACULTY, self::SHS_STUDENT];
    }

    public static function fromModelClass(string $modelClass): ?self
    {
        return match ($modelClass) {
            \App\Models\Students::class => self::STUDENT,
            \App\Models\Faculty::class => self::FACULTY,
            \App\Models\ShsStudents::class => self::SHS_STUDENT,
            default => null,
        };
    }
}
