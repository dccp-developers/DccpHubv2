<?php

declare(strict_types=1);

namespace App\Services\Faculty;

use App\Models\Classes;
use App\Models\Faculty;
use App\Models\ClassAttendanceSettings;
use App\Models\class_enrollments;
use App\Models\Attendance;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceMethod;
use App\Enums\AttendancePolicy;
use App\Services\AttendanceService;
use App\Services\AttendanceMethodService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClassAttendanceManagementService
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly AttendanceMethodService $attendanceMethodService
    ) {}

    /**
     * Get attendance settings for a class
     */
    public function getAttendanceSettings(int $classId): ?ClassAttendanceSettings
    {
        return ClassAttendanceSettings::where('class_id', $classId)->first();
    }

    /**
     * Create or update attendance settings for a class
     */
    public function setupAttendanceSettings(
        int $classId,
        string $facultyId,
        array $settings
    ): ClassAttendanceSettings {
        return ClassAttendanceSettings::updateOrCreate(
            ['class_id' => $classId],
            array_merge($settings, ['faculty_id' => $facultyId])
        );
    }

    /**
     * Get attendance data for class management view
     */
    public function getClassAttendanceData(int $classId, ?\DateTimeInterface $date = null): array
    {
        $class = Classes::with(['Subject', 'ShsSubject', 'Faculty', 'Room'])->findOrFail($classId);
        $settings = $this->getAttendanceSettings($classId);
        $date = $date ? Carbon::parse($date) : now();

        // Get enrolled students
        $enrollments = class_enrollments::with(['student', 'ShsStudent'])
            ->where('class_id', $classId)
            ->get();

        // Get attendance records for the date
        $attendanceRecords = Attendance::where('class_id', $classId)
            ->where('date', $date->format('Y-m-d'))
            ->get()
            ->keyBy('student_id');

        // Build student roster with attendance status
        $roster = $enrollments->map(function ($enrollment) use ($attendanceRecords, $settings, $classId) {
            $student = $enrollment->student ?? $enrollment->ShsStudent;
            $studentId = (string) ($student instanceof \App\Models\Students ? $student->id : $student->student_lrn);

            $attendance = $attendanceRecords->get($studentId);

            // Determine default status based on policy
            $defaultStatus = $settings?->attendance_policy?->defaultStatus() ?? AttendanceStatus::PRESENT;

            // Format name based on student type
            $formattedName = $this->formatStudentName($student);
            $lastName = $this->getStudentLastName($student);

            return [
                'enrollment_id' => $enrollment->id,
                'student_id' => $studentId,
                'last_name' => $lastName, // For sorting
                'student' => [
                    'id' => $student->id ?? $student->student_lrn,
                    'name' => $formattedName,
                    'email' => $student->email ?? null,
                    'photo' => $student->profile_url ?? null,
                ],
                'attendance' => [
                    'id' => $attendance?->id,
                    'status' => $attendance?->status ?? $defaultStatus->value,
                    'marked_at' => $attendance?->marked_at,
                    'marked_by' => $attendance?->marked_by,
                    'remarks' => $attendance?->remarks,
                    'is_late' => $attendance ? $this->isLate($attendance, $settings) : false,
                ],
                'stats' => $this->getStudentAttendanceStats($studentId, $classId),
            ];
        })
        // Sort by last name
        ->sortBy('last_name')
        ->values(); // Reset array keys

        // Get session statistics
        $sessionStats = $this->getSessionStatistics($roster);

        // Get method-specific data
        $methodData = $settings ? $this->attendanceMethodService->getMethodData($settings) : null;

        return [
            'class' => $class,
            'settings' => $settings?->getFormattedSettings(),
            'date' => $date,
            'roster' => $roster->toArray(), // Convert to array for frontend
            'session_stats' => $sessionStats,
            'method_data' => $methodData,
            'is_setup' => $settings !== null && $settings->is_enabled,
            'can_take_attendance' => $this->canTakeAttendance($settings, $date),
        ];
    }

    /**
     * Initialize attendance session for a class
     */
    public function initializeAttendanceSession(
        int $classId,
        string $facultyId,
        ?\DateTimeInterface $date = null
    ): array {
        $settings = $this->getAttendanceSettings($classId);
        if (!$settings || !$settings->is_enabled) {
            throw new \Exception('Attendance tracking is not enabled for this class.');
        }

        $date = $date ? Carbon::parse($date) : now();
        
        // Check if session already exists
        $existingSession = Attendance::where('class_id', $classId)
            ->where('date', $date->format('Y-m-d'))
            ->exists();

        if ($existingSession) {
            return $this->getClassAttendanceData($classId, $date);
        }

        // Get all enrolled students
        $enrollments = class_enrollments::where('class_id', $classId)->get();
        
        // Create attendance records based on policy
        $defaultStatus = $settings->attendance_policy->defaultStatus();
        
        DB::transaction(function () use ($enrollments, $classId, $date, $defaultStatus, $facultyId) {
            foreach ($enrollments as $enrollment) {
                $studentId = (string) $enrollment->student_id;

                Attendance::create([
                    'class_id' => $classId,
                    'class_enrollment_id' => $enrollment->id,
                    'student_id' => $studentId,
                    'date' => $date->format('Y-m-d'),
                    'status' => $defaultStatus->value,
                    'marked_at' => now(),
                    'marked_by' => $facultyId,
                ]);
            }
        });

        // Initialize method-specific session data
        $sessionData = $this->attendanceMethodService->initializeSession($settings, $date);

        $attendanceData = $this->getClassAttendanceData($classId, $date);
        $attendanceData['session_data'] = $sessionData;

        return $attendanceData;
    }

    /**
     * Update student attendance status
     */
    public function updateStudentAttendance(
        int $classId,
        string $studentId,
        string $status,
        string $facultyId,
        ?\DateTimeInterface $date = null,
        ?string $remarks = null
    ): array {
        $date = $date ? Carbon::parse($date) : now();
        
        $attendance = Attendance::where('class_id', $classId)
            ->where('student_id', $studentId)
            ->where('date', $date->format('Y-m-d'))
            ->first();

        if (!$attendance) {
            // Create new attendance record
            $enrollment = class_enrollments::where('class_id', $classId)
                ->where('student_id', $studentId)
                ->firstOrFail();

            $attendance = Attendance::create([
                'class_id' => $classId,
                'class_enrollment_id' => $enrollment->id,
                'student_id' => $studentId,
                'date' => $date->format('Y-m-d'),
                'status' => $status,
                'marked_at' => now(),
                'marked_by' => $facultyId,
                'remarks' => $remarks,
            ]);
        } else {
            // Update existing record
            $attendance->update([
                'status' => $status,
                'marked_at' => now(),
                'marked_by' => $facultyId,
                'remarks' => $remarks,
            ]);
        }

        return [
            'success' => true,
            'attendance' => $attendance,
            'session_stats' => $this->getSessionStatistics(
                collect([$this->getStudentAttendanceData($classId, $studentId, $date)])
            ),
        ];
    }

    /**
     * Bulk update attendance for multiple students
     */
    public function bulkUpdateAttendance(
        int $classId,
        array $attendanceData,
        string $facultyId,
        ?\DateTimeInterface $date = null
    ): array {
        $date = $date ? Carbon::parse($date) : now();
        $updated = [];

        DB::transaction(function () use ($classId, $attendanceData, $facultyId, $date, &$updated) {
            foreach ($attendanceData as $data) {
                $result = $this->updateStudentAttendance(
                    $classId,
                    $data['student_id'],
                    $data['status'],
                    $facultyId,
                    $date,
                    $data['remarks'] ?? null
                );
                $updated[] = $result['attendance'];
            }
        });

        return [
            'success' => true,
            'updated_count' => count($updated),
            'updated_records' => $updated,
            'session_stats' => $this->getSessionStatistics(
                $this->getClassAttendanceData($classId, $date)['roster']
            ),
        ];
    }

    /**
     * Get student attendance statistics
     */
    private function getStudentAttendanceStats(string $studentId, int $classId): array
    {
        return $this->attendanceService->calculateStudentClassStats($studentId, $classId);
    }

    /**
     * Format student name as "Last Name, First Name Middle Name"
     */
    private function formatStudentName($student): string
    {
        if ($student instanceof \App\Models\Students) {
            // Regular Students model with separate name fields
            $firstName = trim($student->first_name ?? '');
            $middleName = trim($student->middle_name ?? '');
            $lastName = trim($student->last_name ?? '');

            if (empty($lastName)) {
                return $firstName . ($middleName ? ' ' . $middleName : '');
            }

            $fullFirstName = $firstName . ($middleName ? ' ' . $middleName : '');
            return $lastName . ', ' . $fullFirstName;
        } else {
            // ShsStudents model with fullname field
            $fullname = trim($student->fullname ?? '');

            // Try to parse the fullname to extract last name
            $nameParts = explode(' ', $fullname);
            if (count($nameParts) >= 2) {
                $lastName = array_pop($nameParts); // Last part is last name
                $firstMiddle = implode(' ', $nameParts); // Rest is first and middle
                return $lastName . ', ' . $firstMiddle;
            }

            return $fullname; // Return as-is if can't parse
        }
    }

    /**
     * Get student's last name for sorting
     */
    private function getStudentLastName($student): string
    {
        if ($student instanceof \App\Models\Students) {
            return trim($student->last_name ?? '');
        } else {
            // ShsStudents model - extract last name from fullname
            $fullname = trim($student->fullname ?? '');
            $nameParts = explode(' ', $fullname);

            if (count($nameParts) >= 2) {
                return array_pop($nameParts); // Last part is last name
            }

            return $fullname; // Return full name if can't parse
        }
    }

    /**
     * Get session statistics
     */
    private function getSessionStatistics(Collection $roster): array
    {
        $total = $roster->count();
        $present = $roster->where('attendance.status', AttendanceStatus::PRESENT->value)->count();
        $absent = $roster->where('attendance.status', AttendanceStatus::ABSENT->value)->count();
        $late = $roster->where('attendance.status', AttendanceStatus::LATE->value)->count();
        $excused = $roster->where('attendance.status', AttendanceStatus::EXCUSED->value)->count();

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'attendance_rate' => $total > 0 ? round(($present + $late) / $total * 100, 1) : 0,
        ];
    }

    /**
     * Check if attendance was marked late
     */
    private function isLate(Attendance $attendance, ?ClassAttendanceSettings $settings): bool
    {
        if (!$settings || !$attendance->marked_at) {
            return false;
        }

        // This would need class schedule information to determine if late
        // For now, return false - can be enhanced with schedule integration
        return false;
    }

    /**
     * Check if attendance can be taken for the given date
     */
    private function canTakeAttendance(?ClassAttendanceSettings $settings, ?\DateTimeInterface $date = null): bool
    {
        if (!$settings || !$settings->is_enabled) {
            return false;
        }

        return $settings->isActive();
    }

    /**
     * Get student attendance data
     */
    private function getStudentAttendanceData(int $classId, string $studentId, Carbon $date): array
    {
        $enrollment = class_enrollments::with(['student', 'ShsStudent'])
            ->where('class_id', $classId)
            ->where('student_id', $studentId)
            ->first();

        if (!$enrollment) {
            return [];
        }

        $student = $enrollment->student ?? $enrollment->ShsStudent;
        $attendance = Attendance::where('class_id', $classId)
            ->where('student_id', $studentId)
            ->where('date', $date->format('Y-m-d'))
            ->first();

        return [
            'enrollment_id' => $enrollment->id,
            'student_id' => $studentId,
            'student' => [
                'id' => $student->id ?? $student->student_lrn,
                'name' => $this->formatStudentName($student),
            ],
            'attendance' => [
                'status' => $attendance?->status ?? AttendanceStatus::ABSENT->value,
            ],
        ];
    }
}
