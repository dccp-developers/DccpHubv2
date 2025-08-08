<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClassAttendanceSettings;
use App\Models\Attendance;
use App\Models\class_enrollments;
use App\Enums\AttendanceMethod;
use App\Enums\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceMethodService
{
    /**
     * Initialize attendance session based on method
     */
    public function initializeSession(ClassAttendanceSettings $settings, \DateTimeInterface $date): array
    {
        return match ($settings->attendance_method) {
            AttendanceMethod::MANUAL => $this->initializeManualSession($settings, $date),
            AttendanceMethod::QR_CODE => $this->initializeQrCodeSession($settings, $date),
            AttendanceMethod::ATTENDANCE_CODE => $this->initializeAttendanceCodeSession($settings, $date),
            AttendanceMethod::SELF_CHECKIN => $this->initializeSelfCheckinSession($settings, $date),
            AttendanceMethod::HYBRID => $this->initializeHybridSession($settings, $date),
        };
    }

    /**
     * Process student check-in based on method
     */
    public function processStudentCheckin(
        ClassAttendanceSettings $settings,
        string $studentId,
        array $data = [],
        ?\DateTimeInterface $date = null
    ): array {
        $date = $date ?? now();

        return match ($settings->attendance_method) {
            AttendanceMethod::QR_CODE => $this->processQrCodeCheckin($settings, $studentId, $data, $date),
            AttendanceMethod::ATTENDANCE_CODE => $this->processAttendanceCodeCheckin($settings, $studentId, $data, $date),
            AttendanceMethod::SELF_CHECKIN => $this->processSelfCheckin($settings, $studentId, $data, $date),
            AttendanceMethod::HYBRID => $this->processHybridCheckin($settings, $studentId, $data, $date),
            default => ['success' => false, 'message' => 'Method does not support student check-in'],
        };
    }

    /**
     * Get method-specific data for frontend
     */
    public function getMethodData(ClassAttendanceSettings $settings): array
    {
        return match ($settings->attendance_method) {
            AttendanceMethod::MANUAL => $this->getManualData($settings),
            AttendanceMethod::QR_CODE => $this->getQrCodeData($settings),
            AttendanceMethod::ATTENDANCE_CODE => $this->getAttendanceCodeData($settings),
            AttendanceMethod::SELF_CHECKIN => $this->getSelfCheckinData($settings),
            AttendanceMethod::HYBRID => $this->getHybridData($settings),
        };
    }

    /**
     * Manual attendance session initialization
     */
    private function initializeManualSession(ClassAttendanceSettings $settings, \DateTimeInterface $date): array
    {
        return [
            'method' => 'manual',
            'message' => 'Manual attendance session ready. Mark students as present or absent.',
            'instructions' => 'Use the attendance roster to manually mark each student\'s attendance status.',
            'teacher_action_required' => true,
            'student_action_required' => false,
        ];
    }

    /**
     * QR Code session initialization
     */
    private function initializeQrCodeSession(ClassAttendanceSettings $settings, \DateTimeInterface $date): array
    {
        // Generate or refresh QR code if needed
        if ($settings->needsQrRefresh()) {
            $settings->generateQrCode();
        }

        return [
            'method' => 'qr_code',
            'message' => 'QR Code generated. Display it for students to scan.',
            'instructions' => 'Show the QR code to students. They will scan it to mark their attendance.',
            'qr_code_url' => $settings->getQrCodeUrl(),
            'qr_code_token' => $settings->qr_code_token,
            'expires_at' => $settings->qr_code_expires_at,
            'teacher_action_required' => false,
            'student_action_required' => true,
        ];
    }

    /**
     * Attendance Code session initialization
     */
    private function initializeAttendanceCodeSession(ClassAttendanceSettings $settings, \DateTimeInterface $date): array
    {
        // Generate or refresh attendance code if needed
        if ($settings->needsAttendanceCodeRefresh()) {
            $settings->generateAttendanceCode();
        }

        return [
            'method' => 'attendance_code',
            'message' => 'Attendance code generated. Share it with students.',
            'instructions' => 'Share the attendance code with students. They will enter it to mark their attendance.',
            'attendance_code' => $settings->attendance_code,
            'expires_at' => $settings->attendance_code_expires_at,
            'teacher_action_required' => false,
            'student_action_required' => true,
        ];
    }

    /**
     * Self Check-in session initialization
     */
    private function initializeSelfCheckinSession(ClassAttendanceSettings $settings, \DateTimeInterface $date): array
    {
        return [
            'method' => 'self_checkin',
            'message' => 'Self check-in enabled. Students can mark themselves present.',
            'instructions' => 'Students can now mark themselves present through the student portal.',
            'checkin_window' => [
                'start' => $settings->checkin_start_time,
                'end' => $settings->checkin_end_time,
                'is_valid' => $settings->isCheckinTimeValid(),
            ],
            'require_confirmation' => $settings->require_confirmation,
            'show_class_list' => $settings->show_class_list,
            'teacher_action_required' => false,
            'student_action_required' => true,
        ];
    }

    /**
     * Hybrid session initialization
     */
    private function initializeHybridSession(ClassAttendanceSettings $settings, \DateTimeInterface $date): array
    {
        return [
            'method' => 'hybrid',
            'message' => 'Hybrid attendance enabled. Students can self-check-in, you can override.',
            'instructions' => 'Students can mark themselves present. You can override any attendance status.',
            'checkin_window' => [
                'start' => $settings->checkin_start_time,
                'end' => $settings->checkin_end_time,
                'is_valid' => $settings->isCheckinTimeValid(),
            ],
            'show_class_list' => $settings->show_class_list,
            'teacher_action_required' => true,
            'student_action_required' => true,
        ];
    }

    /**
     * Process QR code check-in
     */
    private function processQrCodeCheckin(
        ClassAttendanceSettings $settings,
        string $studentId,
        array $data,
        \DateTimeInterface $date
    ): array {
        $token = $data['token'] ?? '';

        if (!$settings->qr_code_token || $settings->qr_code_token !== $token) {
            return ['success' => false, 'message' => 'Invalid QR code'];
        }

        if ($settings->qr_code_expires_at && now()->gt($settings->qr_code_expires_at)) {
            return ['success' => false, 'message' => 'QR code has expired'];
        }

        return $this->markStudentPresent($settings, $studentId, $date, 'QR Code scan');
    }

    /**
     * Process attendance code check-in
     */
    private function processAttendanceCodeCheckin(
        ClassAttendanceSettings $settings,
        string $studentId,
        array $data,
        \DateTimeInterface $date
    ): array {
        $code = $data['code'] ?? '';

        if (!$settings->isValidAttendanceCode($code)) {
            return ['success' => false, 'message' => 'Invalid attendance code'];
        }

        return $this->markStudentPresent($settings, $studentId, $date, 'Attendance code: ' . $code);
    }

    /**
     * Process self check-in
     */
    private function processSelfCheckin(
        ClassAttendanceSettings $settings,
        string $studentId,
        array $data,
        \DateTimeInterface $date
    ): array {
        if (!$settings->isCheckinTimeValid()) {
            return ['success' => false, 'message' => 'Check-in window is closed'];
        }

        $status = $settings->require_confirmation ? 'pending' : 'present';
        $remarks = $settings->require_confirmation ? 'Self check-in (pending confirmation)' : 'Self check-in';

        return $this->markStudentPresent($settings, $studentId, $date, $remarks, $status);
    }

    /**
     * Process hybrid check-in
     */
    private function processHybridCheckin(
        ClassAttendanceSettings $settings,
        string $studentId,
        array $data,
        \DateTimeInterface $date
    ): array {
        if (!$settings->isCheckinTimeValid()) {
            return ['success' => false, 'message' => 'Check-in window is closed'];
        }

        return $this->markStudentPresent($settings, $studentId, $date, 'Self check-in (hybrid)');
    }

    /**
     * Mark student as present
     */
    private function markStudentPresent(
        ClassAttendanceSettings $settings,
        string $studentId,
        \DateTimeInterface $date,
        string $remarks = '',
        string $status = 'present'
    ): array {
        $enrollment = class_enrollments::where('class_id', $settings->class_id)
            ->where('student_id', $studentId)
            ->first();

        if (!$enrollment) {
            return ['success' => false, 'message' => 'Student not enrolled in this class'];
        }

        $attendance = Attendance::updateOrCreate(
            [
                'class_id' => $settings->class_id,
                'student_id' => $studentId,
                'date' => Carbon::parse($date)->format('Y-m-d'),
            ],
            [
                'class_enrollment_id' => $enrollment->id,
                'status' => $status,
                'marked_at' => now(),
                'remarks' => $remarks,
            ]
        );

        return [
            'success' => true,
            'message' => 'Attendance marked successfully',
            'attendance' => $attendance,
        ];
    }

    /**
     * Get manual method data
     */
    private function getManualData(ClassAttendanceSettings $settings): array
    {
        return [
            'type' => 'manual',
            'instructions' => 'Manually mark each student as present or absent',
        ];
    }

    /**
     * Get QR code method data
     */
    private function getQrCodeData(ClassAttendanceSettings $settings): array
    {
        return [
            'type' => 'qr_code',
            'qr_code_url' => $settings->getQrCodeUrl(),
            'expires_at' => $settings->qr_code_expires_at,
            'instructions' => 'Display this QR code for students to scan',
        ];
    }

    /**
     * Get attendance code method data
     */
    private function getAttendanceCodeData(ClassAttendanceSettings $settings): array
    {
        return [
            'type' => 'attendance_code',
            'code' => $settings->attendance_code,
            'expires_at' => $settings->attendance_code_expires_at,
            'instructions' => 'Share this code with students to enter',
        ];
    }

    /**
     * Get self check-in method data
     */
    private function getSelfCheckinData(ClassAttendanceSettings $settings): array
    {
        return [
            'type' => 'self_checkin',
            'checkin_window' => [
                'start' => $settings->checkin_start_time,
                'end' => $settings->checkin_end_time,
                'is_valid' => $settings->isCheckinTimeValid(),
            ],
            'instructions' => 'Students can mark themselves present during the check-in window',
        ];
    }

    /**
     * Get hybrid method data
     */
    private function getHybridData(ClassAttendanceSettings $settings): array
    {
        return [
            'type' => 'hybrid',
            'checkin_window' => [
                'start' => $settings->checkin_start_time,
                'end' => $settings->checkin_end_time,
                'is_valid' => $settings->isCheckinTimeValid(),
            ],
            'instructions' => 'Students can self-check-in, you can override any status',
        ];
    }
}
