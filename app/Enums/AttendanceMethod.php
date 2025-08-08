<?php

declare(strict_types=1);

namespace App\Enums;

enum AttendanceMethod: string
{
    case MANUAL = 'manual';
    case QR_CODE = 'qr_code';
    case ATTENDANCE_CODE = 'attendance_code';
    case SELF_CHECKIN = 'self_checkin';
    case HYBRID = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual Roll Call',
            self::QR_CODE => 'QR Code Scanning',
            self::ATTENDANCE_CODE => 'Attendance Code',
            self::SELF_CHECKIN => 'Student Self Check-in',
            self::HYBRID => 'Hybrid (Manual + Student)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::MANUAL => 'Teacher manually marks each student present or absent during class',
            self::QR_CODE => 'Teacher displays QR code, students scan to mark attendance',
            self::ATTENDANCE_CODE => 'Teacher provides a simple code, students enter to mark attendance',
            self::SELF_CHECKIN => 'Students mark themselves present through the portal during class time',
            self::HYBRID => 'Students can self-check-in, teacher can override and mark absent students',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::MANUAL => 'clipboard-document-list',
            self::QR_CODE => 'qr-code',
            self::ATTENDANCE_CODE => 'key',
            self::SELF_CHECKIN => 'hand-raised',
            self::HYBRID => 'user-group',
        };
    }

    public function requiresStudentAction(): bool
    {
        return match ($this) {
            self::MANUAL => false,
            self::QR_CODE => true,
            self::ATTENDANCE_CODE => true,
            self::SELF_CHECKIN => true,
            self::HYBRID => true,
        };
    }

    public function allowsTeacherOverride(): bool
    {
        return match ($this) {
            self::MANUAL => true,
            self::QR_CODE => true,
            self::ATTENDANCE_CODE => true,
            self::SELF_CHECKIN => false,
            self::HYBRID => true,
        };
    }

    public function isRealTimeMethod(): bool
    {
        return match ($this) {
            self::MANUAL => false,
            self::QR_CODE => true,
            self::ATTENDANCE_CODE => true,
            self::SELF_CHECKIN => true,
            self::HYBRID => true,
        };
    }

    public static function options(): array
    {
        return array_map(fn($method) => [
            'value' => $method->value,
            'label' => $method->label(),
            'description' => $method->description(),
            'icon' => $method->icon(),
            'requires_student_action' => $method->requiresStudentAction(),
            'allows_teacher_override' => $method->allowsTeacherOverride(),
            'is_realtime' => $method->isRealTimeMethod(),
        ], self::cases());
    }
}
