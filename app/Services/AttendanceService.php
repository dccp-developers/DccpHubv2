<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attendance;
use App\Models\Classes;
use App\Models\class_enrollments;
use App\Models\Faculty;
use App\Models\Students;
use App\Models\ShsStudents;
use App\Enums\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Centralized Attendance Service
 * 
 * Handles all attendance-related business logic for both faculty and students
 */
final class AttendanceService
{
    /**
     * Mark attendance for a student in a specific class
     */
    public function markAttendance(
        int $classId,
        string $studentId,
        AttendanceStatus $status,
        \DateTimeInterface $date,
        ?string $remarks = null,
        ?string $facultyId = null
    ): Attendance {
        // Get the class enrollment
        $classEnrollment = class_enrollments::where('class_id', $classId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        // Check if attendance already exists for this date
        $attendance = Attendance::where('class_enrollment_id', $classEnrollment->id)
            ->where('date', $date->format('Y-m-d'))
            ->first();

        $attendanceData = [
            'class_enrollment_id' => $classEnrollment->id,
            'student_id' => $studentId,
            'class_id' => $classId,
            'date' => $date->format('Y-m-d'),
            'status' => $status->value,
            'remarks' => $remarks,
            'marked_at' => now(),
            'marked_by' => $facultyId ?? Auth::user()?->faculty?->id,
            'ip_address' => request()->ip(),
        ];

        if ($attendance) {
            $attendance->update($attendanceData);
        } else {
            $attendance = Attendance::create($attendanceData);
        }

        return $attendance;
    }

    /**
     * Mark attendance for multiple students at once
     */
    public function markBulkAttendance(
        int $classId,
        array $attendanceData,
        \DateTimeInterface $date,
        ?string $facultyId = null
    ): Collection {
        $results = collect();

        DB::transaction(function () use ($classId, $attendanceData, $date, $facultyId, &$results) {
            foreach ($attendanceData as $data) {
                $status = AttendanceStatus::from($data['status']);
                $attendance = $this->markAttendance(
                    $classId,
                    $data['student_id'],
                    $status,
                    $date,
                    $data['remarks'] ?? null,
                    $facultyId
                );
                $results->push($attendance);
            }
        });

        return $results;
    }

    /**
     * Get attendance records for a specific class and date range
     */
    public function getClassAttendance(
        int $classId,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null
    ): Collection {
        $query = Attendance::with(['classEnrollment.student', 'classEnrollment.ShsStudent'])
            ->where('class_id', $classId);

        if ($startDate) {
            $query->where('date', '>=', $startDate->format('Y-m-d'));
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate->format('Y-m-d'));
        }

        return $query->orderBy('date', 'desc')
            ->orderBy('student_id')
            ->get();
    }

    /**
     * Get attendance records for a specific student
     */
    public function getStudentAttendance(
        string $studentId,
        ?int $classId = null,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null
    ): Collection {
        $query = Attendance::with(['class.Subject', 'class.ShsSubject', 'class.Faculty'])
            ->where('student_id', $studentId);

        if ($classId) {
            $query->where('class_id', $classId);
        }

        if ($startDate) {
            $query->where('date', '>=', $startDate->format('Y-m-d'));
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate->format('Y-m-d'));
        }

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Calculate attendance statistics for a student in a specific class
     */
    public function calculateStudentClassStats(string $studentId, int $classId): array
    {
        $attendances = $this->getStudentAttendance($studentId, $classId);
        return $this->calculateAttendanceStats($attendances);
    }

    /**
     * Calculate overall attendance statistics for a student
     */
    public function calculateStudentOverallStats(string $studentId): array
    {
        $attendances = $this->getStudentAttendance($studentId);
        return $this->calculateAttendanceStats($attendances);
    }

    /**
     * Calculate attendance statistics for a class
     */
    public function calculateClassStats(int $classId, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $attendances = $this->getClassAttendance($classId, $startDate, $endDate);
        return $this->calculateAttendanceStats($attendances);
    }

    /**
     * Get attendance summary for faculty dashboard
     */
    public function getFacultyAttendanceSummary(string $facultyId): array
    {
        $classes = Classes::where('faculty_id', $facultyId)->get();
        $summary = [];

        foreach ($classes as $class) {
            $stats = $this->calculateClassStats($class->id);
            $summary[] = [
                'class' => $class,
                'stats' => $stats,
                'recent_sessions' => $this->getRecentAttendanceSessions($class->id, 5),
            ];
        }

        return $summary;
    }

    /**
     * Get recent attendance sessions for a class
     */
    public function getRecentAttendanceSessions(int $classId, int $limit = 10): Collection
    {
        return Attendance::where('class_id', $classId)
            ->select('date')
            ->distinct()
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) use ($classId) {
                $date = Carbon::parse($item->date);
                $dayAttendances = Attendance::where('class_id', $classId)
                    ->where('date', $date->format('Y-m-d'))
                    ->get();

                return [
                    'date' => $date,
                    'stats' => $this->calculateAttendanceStats($dayAttendances),
                ];
            });
    }

    /**
     * Generate attendance report for a class
     */
    public function generateClassReport(
        int $classId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        $class = Classes::with(['Subject', 'ShsSubject', 'Faculty'])->findOrFail($classId);
        $enrollments = class_enrollments::with(['student', 'ShsStudent'])
            ->where('class_id', $classId)
            ->get();

        $report = [
            'class' => $class,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'students' => [],
            'summary' => [],
        ];

        foreach ($enrollments as $enrollment) {
            $studentAttendances = $this->getStudentAttendance(
                $enrollment->student_id,
                $classId,
                $startDate,
                $endDate
            );

            $stats = $this->calculateAttendanceStats($studentAttendances);
            $student = $enrollment->EnrolledStudent();

            $report['students'][] = [
                'student' => $student,
                'enrollment' => $enrollment,
                'attendances' => $studentAttendances,
                'stats' => $stats,
            ];
        }

        $report['summary'] = $this->calculateClassStats($classId, $startDate, $endDate);

        return $report;
    }

    /**
     * Calculate attendance statistics from a collection of attendance records
     */
    public function calculateAttendanceStats(Collection $attendances): array
    {
        $total = $attendances->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0,
                'partial' => 0,
                'present_count' => 0,
                'attendance_rate' => 0,
                'absence_rate' => 0,
            ];
        }

        $statusCounts = $attendances->groupBy('status')->map->count();
        
        $present = $statusCounts->get('present', 0);
        $absent = $statusCounts->get('absent', 0);
        $late = $statusCounts->get('late', 0);
        $excused = $statusCounts->get('excused', 0);
        $partial = $statusCounts->get('partial', 0);

        $presentCount = $present + $late + $partial;
        $attendanceRate = round(($presentCount / $total) * 100, 2);
        $absenceRate = round(($absent / $total) * 100, 2);

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'partial' => $partial,
            'present_count' => $presentCount,
            'attendance_rate' => $attendanceRate,
            'absence_rate' => $absenceRate,
        ];
    }

    /**
     * Get attendance trends for analytics
     */
    public function getAttendanceTrends(
        int $classId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        string $groupBy = 'week'
    ): array {
        $attendances = $this->getClassAttendance($classId, $startDate, $endDate);

        // Convert to Carbon for date manipulation
        $startCarbon = Carbon::parse($startDate);
        $endCarbon = Carbon::parse($endDate);

        $trends = [];
        $period = $startCarbon->copy();

        while ($period->lte($endCarbon)) {
            $periodEnd = match($groupBy) {
                'day' => $period->copy(),
                'week' => $period->copy()->endOfWeek(),
                'month' => $period->copy()->endOfMonth(),
                default => $period->copy()->endOfWeek(),
            };

            $periodAttendances = $attendances->filter(function ($attendance) use ($period, $periodEnd) {
                $date = Carbon::parse($attendance->date);
                return $date->between($period, $periodEnd);
            });

            $trends[] = [
                'period' => $period->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'stats' => $this->calculateAttendanceStats($periodAttendances),
            ];

            $period = match($groupBy) {
                'day' => $period->addDay(),
                'week' => $period->addWeek(),
                'month' => $period->addMonth(),
                default => $period->addWeek(),
            };
        }

        return $trends;
    }
}
