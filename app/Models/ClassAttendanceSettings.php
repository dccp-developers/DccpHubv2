<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceMethod;
use App\Enums\AttendancePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class ClassAttendanceSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'faculty_id',
        'is_enabled',
        'start_date',
        'end_date',
        'attendance_method',
        'attendance_policy',
        'grace_period_minutes',
        'auto_mark_absent_minutes',
        'allow_late_checkin',
        'checkin_start_time',
        'checkin_end_time',
        'qr_code_token',
        'qr_code_expires_at',
        'qr_code_auto_refresh',
        'qr_code_refresh_minutes',
        'attendance_code',
        'attendance_code_expires_at',
        'attendance_code_auto_refresh',
        'require_confirmation',
        'show_class_list',
        'notify_absent_students',
        'notify_late_students',
        'send_daily_summary',
        'additional_settings',
        'notes',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'attendance_method' => AttendanceMethod::class,
        'attendance_policy' => AttendancePolicy::class,
        'grace_period_minutes' => 'integer',
        'auto_mark_absent_minutes' => 'integer',
        'allow_late_checkin' => 'boolean',
        'checkin_start_time' => 'datetime:H:i',
        'checkin_end_time' => 'datetime:H:i',
        'qr_code_expires_at' => 'datetime',
        'qr_code_auto_refresh' => 'boolean',
        'qr_code_refresh_minutes' => 'integer',
        'attendance_code_expires_at' => 'datetime',
        'attendance_code_auto_refresh' => 'boolean',
        'require_confirmation' => 'boolean',
        'show_class_list' => 'boolean',
        'notify_absent_students' => 'boolean',
        'notify_late_students' => 'boolean',
        'send_daily_summary' => 'boolean',
        'additional_settings' => 'array',
    ];

    /**
     * Get the class that owns the attendance settings
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    /**
     * Get the faculty that owns the attendance settings
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }

    /**
     * Check if attendance tracking is currently active
     */
    public function isActive(): bool
    {
        if (!$this->is_enabled) {
            return false;
        }

        $now = now()->toDateString();
        
        if ($this->start_date && $now < $this->start_date->toDateString()) {
            return false;
        }

        if ($this->end_date && $now > $this->end_date->toDateString()) {
            return false;
        }

        return true;
    }

    /**
     * Check if QR code needs refresh
     */
    public function needsQrRefresh(): bool
    {
        if ($this->attendance_method !== AttendanceMethod::QR_CODE) {
            return false;
        }

        if (!$this->qr_code_auto_refresh) {
            return false;
        }

        if (!$this->qr_code_expires_at) {
            return true;
        }

        return now()->gte($this->qr_code_expires_at);
    }

    /**
     * Generate new QR code token
     */
    public function generateQrCode(): string
    {
        $token = bin2hex(random_bytes(16));
        
        $this->update([
            'qr_code_token' => $token,
            'qr_code_expires_at' => now()->addMinutes($this->qr_code_refresh_minutes),
        ]);

        return $token;
    }

    /**
     * Get QR code URL
     */
    public function getQrCodeUrl(): ?string
    {
        if ($this->attendance_method !== AttendanceMethod::QR_CODE || !$this->qr_code_token) {
            return null;
        }

        return route('student.attendance.checkin.qr', [
            'class' => $this->class_id,
            'token' => $this->qr_code_token,
        ]);
    }

    /**
     * Generate new attendance code
     */
    public function generateAttendanceCode(): string
    {
        $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));

        $this->update([
            'attendance_code' => $code,
            'attendance_code_expires_at' => now()->addHours(2), // Code valid for 2 hours
        ]);

        return $code;
    }

    /**
     * Check if attendance code needs refresh
     */
    public function needsAttendanceCodeRefresh(): bool
    {
        if ($this->attendance_method !== AttendanceMethod::ATTENDANCE_CODE) {
            return false;
        }

        if (!$this->attendance_code_auto_refresh) {
            return false;
        }

        if (!$this->attendance_code_expires_at) {
            return true;
        }

        return now()->gte($this->attendance_code_expires_at);
    }

    /**
     * Check if student can check in at current time
     */
    public function isCheckinTimeValid(): bool
    {
        if (!$this->checkin_start_time || !$this->checkin_end_time) {
            return true; // No time restrictions
        }

        $now = now()->format('H:i');
        $startTime = $this->checkin_start_time;
        $endTime = $this->checkin_end_time;

        return $now >= $startTime && $now <= $endTime;
    }

    /**
     * Validate attendance code
     */
    public function isValidAttendanceCode(string $code): bool
    {
        if ($this->attendance_method !== AttendanceMethod::ATTENDANCE_CODE) {
            return false;
        }

        if (!$this->attendance_code) {
            return false;
        }

        if ($this->attendance_code_expires_at && now()->gt($this->attendance_code_expires_at)) {
            return false;
        }

        return strtoupper($code) === strtoupper($this->attendance_code);
    }

    /**
     * Get formatted settings for display
     */
    public function getFormattedSettings(): array
    {
        return [
            'method' => [
                'value' => $this->attendance_method->value,
                'label' => $this->attendance_method->label(),
                'description' => $this->attendance_method->description(),
                'requires_student_action' => $this->attendance_method->requiresStudentAction(),
                'allows_teacher_override' => $this->attendance_method->allowsTeacherOverride(),
                'is_realtime' => $this->attendance_method->isRealTimeMethod(),
            ],
            'policy' => [
                'value' => $this->attendance_policy->value,
                'label' => $this->attendance_policy->label(),
                'description' => $this->attendance_policy->description(),
                'default_status' => $this->attendance_policy->defaultStatus()->value,
            ],
            'timing' => [
                'grace_period' => $this->grace_period_minutes,
                'auto_mark_absent' => $this->auto_mark_absent_minutes,
                'allow_late_checkin' => $this->allow_late_checkin,
                'checkin_start_time' => $this->checkin_start_time,
                'checkin_end_time' => $this->checkin_end_time,
                'is_checkin_time_valid' => $this->isCheckinTimeValid(),
            ],
            'qr_code' => [
                'token' => $this->qr_code_token,
                'expires_at' => $this->qr_code_expires_at,
                'auto_refresh' => $this->qr_code_auto_refresh,
                'refresh_minutes' => $this->qr_code_refresh_minutes,
                'url' => $this->getQrCodeUrl(),
                'needs_refresh' => $this->needsQrRefresh(),
            ],
            'attendance_code' => [
                'code' => $this->attendance_code,
                'expires_at' => $this->attendance_code_expires_at,
                'auto_refresh' => $this->attendance_code_auto_refresh,
                'needs_refresh' => $this->needsAttendanceCodeRefresh(),
            ],
            'self_checkin' => [
                'require_confirmation' => $this->require_confirmation,
                'show_class_list' => $this->show_class_list,
            ],
            'notifications' => [
                'notify_absent' => $this->notify_absent_students,
                'notify_late' => $this->notify_late_students,
                'daily_summary' => $this->send_daily_summary,
            ],
        ];
    }
}
