<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Services\AttendanceService;
use App\Models\Classes;
use App\Models\class_enrollments;
use App\Models\Students;
use App\Models\ShsStudents;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Student-specific Attendance Service
 * 
 * Handles attendance operations from the student perspective
 */
final class StudentAttendanceService
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    /**
     * Get student's attendance dashboard data
     */
    public function getStudentDashboardData(string $studentId): array
    {
        // Get all classes the student is enrolled in
        $enrollments = class_enrollments::with(['class.Subject', 'class.ShsSubject', 'class.Faculty'])
            ->where('student_id', $studentId)
            ->get();

        $classesData = [];
        $overallStats = [
            'total' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0,
            'partial' => 0,
            'present_count' => 0,
            'attendance_rate' => 0,
        ];

        foreach ($enrollments as $enrollment) {
            $classStats = $this->attendanceService->calculateStudentClassStats(
                $studentId,
                $enrollment->class_id
            );

            $recentAttendances = $this->attendanceService->getStudentAttendance(
                $studentId,
                $enrollment->class_id,
                now()->subDays(30)
            );

            $classesData[] = [
                'class' => $enrollment->class,
                'enrollment' => $enrollment,
                'stats' => $classStats,
                'recent_attendances' => $recentAttendances->take(5),
                'needs_attention' => $classStats['attendance_rate'] < 75,
                'last_attendance' => $recentAttendances->first(),
            ];

            // Aggregate overall stats
            $overallStats['total'] += $classStats['total'];
            $overallStats['present'] += $classStats['present'];
            $overallStats['absent'] += $classStats['absent'];
            $overallStats['late'] += $classStats['late'];
            $overallStats['excused'] += $classStats['excused'];
            $overallStats['partial'] += $classStats['partial'];
            $overallStats['present_count'] += $classStats['present_count'];
        }

        // Calculate overall attendance rate
        if ($overallStats['total'] > 0) {
            $overallStats['attendance_rate'] = round(
                ($overallStats['present_count'] / $overallStats['total']) * 100,
                2
            );
        }

        return [
            'classes' => $classesData,
            'overall_stats' => $overallStats,
            'attendance_trend' => $this->getStudentAttendanceTrend($studentId),
            'upcoming_classes' => $this->getUpcomingClasses($studentId),
            'attendance_alerts' => $this->getAttendanceAlerts($studentId),
        ];
    }

    /**
     * Get detailed attendance history for a student
     */
    public function getStudentAttendanceHistory(
        string $studentId,
        ?int $classId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        int $perPage = 20
    ): array {
        $attendances = $this->attendanceService->getStudentAttendance(
            $studentId,
            $classId,
            $startDate,
            $endDate
        );

        // Group by month for better organization
        $groupedAttendances = $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->date)->format('Y-m');
        });

        $history = [];
        foreach ($groupedAttendances as $month => $monthAttendances) {
            $monthDate = Carbon::createFromFormat('Y-m', $month);
            $monthStats = $this->attendanceService->calculateAttendanceStats($monthAttendances);

            $history[] = [
                'month' => $monthDate,
                'month_label' => $monthDate->format('F Y'),
                'attendances' => $monthAttendances->values(),
                'stats' => $monthStats,
            ];
        }

        return [
            'history' => $history,
            'total_records' => $attendances->count(),
            'overall_stats' => $this->attendanceService->calculateAttendanceStats($attendances),
        ];
    }

    /**
     * Get attendance statistics for a specific class
     */
    public function getClassAttendanceDetails(string $studentId, int $classId): array
    {
        $class = Classes::with(['Subject', 'ShsSubject', 'Faculty', 'Room'])->findOrFail($classId);
        $enrollment = class_enrollments::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->firstOrFail();

        $attendances = $this->attendanceService->getStudentAttendance($studentId, $classId);
        $stats = $this->attendanceService->calculateStudentClassStats($studentId, $classId);

        // Get attendance by month
        $monthlyData = $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->date)->format('Y-m');
        })->map(function ($monthAttendances) {
            return $this->attendanceService->calculateAttendanceStats($monthAttendances);
        });

        // Get recent sessions
        $recentSessions = $attendances->take(10)->map(function ($attendance) {
            return [
                'date' => Carbon::parse($attendance->date),
                'status' => $attendance->status,
                'remarks' => $attendance->remarks,
                'marked_at' => $attendance->marked_at,
            ];
        });

        return [
            'class' => $class,
            'enrollment' => $enrollment,
            'stats' => $stats,
            'attendances' => $attendances,
            'monthly_data' => $monthlyData,
            'recent_sessions' => $recentSessions,
            'attendance_pattern' => $this->getAttendancePattern($attendances),
        ];
    }

    /**
     * Get student's attendance trend (last 8 weeks)
     */
    private function getStudentAttendanceTrend(string $studentId): array
    {
        $weeks = [];
        
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            
            $weekAttendances = $this->attendanceService->getStudentAttendance(
                $studentId,
                null,
                $weekStart,
                $weekEnd
            );

            $stats = $this->attendanceService->calculateAttendanceStats($weekAttendances);
            
            $weeks[] = [
                'week' => $weekStart->format('M j'),
                'attendance_rate' => $stats['attendance_rate'],
                'total_sessions' => $stats['total'],
            ];
        }

        return $weeks;
    }

    /**
     * Get upcoming classes for the student
     */
    private function getUpcomingClasses(string $studentId): Collection
    {
        $enrollments = class_enrollments::with(['class.Subject', 'class.ShsSubject', 'class.Schedule'])
            ->where('student_id', $studentId)
            ->get();

        $today = now()->format('l'); // Day name (Monday, Tuesday, etc.)
        
        return $enrollments->filter(function ($enrollment) use ($today) {
            return $enrollment->class->Schedule->contains('day_of_week', $today);
        })->map(function ($enrollment) {
            $todaySchedules = $enrollment->class->Schedule->where('day_of_week', now()->format('l'));
            
            return [
                'class' => $enrollment->class,
                'schedules' => $todaySchedules,
                'next_schedule' => $todaySchedules->sortBy('start_time')->first(),
            ];
        })->sortBy('next_schedule.start_time');
    }

    /**
     * Get attendance alerts for the student
     */
    private function getAttendanceAlerts(string $studentId): array
    {
        $enrollments = class_enrollments::with(['class.Subject', 'class.ShsSubject'])
            ->where('student_id', $studentId)
            ->get();

        $alerts = [];

        foreach ($enrollments as $enrollment) {
            $stats = $this->attendanceService->calculateStudentClassStats(
                $studentId,
                $enrollment->class_id
            );

            // Low attendance alert
            if ($stats['attendance_rate'] < 75 && $stats['total'] > 0) {
                $alerts[] = [
                    'type' => 'low_attendance',
                    'severity' => $stats['attendance_rate'] < 60 ? 'high' : 'medium',
                    'class' => $enrollment->class,
                    'message' => "Your attendance in {$enrollment->class->subject_code} is {$stats['attendance_rate']}%",
                    'stats' => $stats,
                ];
            }

            // Consecutive absences alert
            $recentAttendances = $this->attendanceService->getStudentAttendance(
                $studentId,
                $enrollment->class_id,
                now()->subDays(14)
            );

            $consecutiveAbsences = $this->getConsecutiveAbsences($recentAttendances);
            if ($consecutiveAbsences >= 3) {
                $alerts[] = [
                    'type' => 'consecutive_absences',
                    'severity' => 'high',
                    'class' => $enrollment->class,
                    'message' => "You have {$consecutiveAbsences} consecutive absences in {$enrollment->class->subject_code}",
                    'consecutive_count' => $consecutiveAbsences,
                ];
            }
        }

        return $alerts;
    }

    /**
     * Get attendance pattern analysis
     */
    private function getAttendancePattern(Collection $attendances): array
    {
        if ($attendances->isEmpty()) {
            return [];
        }

        // Group by day of week
        $dayPattern = $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->date)->format('l');
        })->map(function ($dayAttendances) {
            return $this->attendanceService->calculateAttendanceStats($dayAttendances);
        });

        // Group by time of day (if we have schedule data)
        $timePattern = [];

        return [
            'by_day' => $dayPattern,
            'by_time' => $timePattern,
            'most_absent_day' => $dayPattern->sortBy('attendance_rate')->keys()->first(),
            'best_attendance_day' => $dayPattern->sortByDesc('attendance_rate')->keys()->first(),
        ];
    }

    /**
     * Count consecutive absences from recent attendances
     */
    private function getConsecutiveAbsences(Collection $attendances): int
    {
        $consecutive = 0;
        $sortedAttendances = $attendances->sortByDesc('date');

        foreach ($sortedAttendances as $attendance) {
            if ($attendance->status === 'absent') {
                $consecutive++;
            } else {
                break;
            }
        }

        return $consecutive;
    }

    /**
     * Export student attendance data
     */
    public function exportStudentAttendance(
        string $studentId,
        ?int $classId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        string $format = 'csv'
    ): array {
        $attendances = $this->attendanceService->getStudentAttendance(
            $studentId,
            $classId,
            $startDate,
            $endDate
        );

        $exportData = $attendances->map(function ($attendance) {
            return [
                'Date' => Carbon::parse($attendance->date)->format('Y-m-d'),
                'Class' => $attendance->class->subject_code,
                'Subject' => $attendance->class->Subject?->title ?? $attendance->class->ShsSubject?->title,
                'Status' => ucfirst($attendance->status),
                'Remarks' => $attendance->remarks ?? '',
                'Marked At' => $attendance->marked_at?->format('Y-m-d H:i:s') ?? '',
            ];
        });

        return [
            'data' => $exportData,
            'filename' => "attendance_report_{$studentId}_" . now()->format('Y-m-d'),
            'stats' => $this->attendanceService->calculateAttendanceStats($attendances),
        ];
    }
}
