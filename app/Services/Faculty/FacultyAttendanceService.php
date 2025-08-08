<?php

declare(strict_types=1);

namespace App\Services\Faculty;

use App\Services\AttendanceService;
use App\Models\Classes;
use App\Models\class_enrollments;
use App\Models\Faculty;
use App\Models\Attendance;
use App\Enums\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Faculty-specific Attendance Service
 * 
 * Handles attendance operations from the faculty perspective
 */
final class FacultyAttendanceService
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    /**
     * Get all classes for a faculty member with attendance summary
     */
    public function getFacultyClassesWithAttendance(string $facultyId): Collection
    {
        return Classes::with(['Subject', 'ShsSubject', 'Room', 'Schedule'])
            ->where('faculty_id', $facultyId)
            ->get()
            ->map(function ($class) {
                $enrollmentCount = class_enrollments::where('class_id', $class->id)->count();
                $recentStats = $this->attendanceService->calculateClassStats($class->id);
                $lastSession = $this->getLastAttendanceSession($class->id);

                return [
                    'class' => $class,
                    'enrollment_count' => $enrollmentCount,
                    'attendance_stats' => $recentStats,
                    'last_session' => $lastSession,
                    'needs_attention' => $recentStats['attendance_rate'] < 75, // Flag classes with low attendance
                ];
            });
    }

    /**
     * Get class roster with attendance data for a specific date
     */
    public function getClassRosterWithAttendance(int $classId, \DateTimeInterface $date): array
    {
        $class = Classes::with(['Subject', 'ShsSubject', 'Faculty', 'Room'])->findOrFail($classId);
        
        $enrollments = class_enrollments::with(['student', 'ShsStudent'])
            ->where('class_id', $classId)
            ->get();

        $roster = [];
        foreach ($enrollments as $enrollment) {
            $student = $enrollment->EnrolledStudent();
            
            // Get attendance for the specific date
            $attendance = Attendance::where('class_enrollment_id', $enrollment->id)
                ->where('date', $date->format('Y-m-d'))
                ->first();

            // Get student's overall stats for this class
            $studentStats = $this->attendanceService->calculateStudentClassStats(
                $enrollment->student_id,
                $classId
            );

            $roster[] = [
                'enrollment' => $enrollment,
                'student' => $student,
                'attendance' => $attendance,
                'stats' => $studentStats,
                'student_name' => $student->fullname ?? ($student->first_name . ' ' . $student->last_name),
                'student_id_display' => $student instanceof \App\Models\ShsStudents ? $student->student_lrn : $student->id,
            ];
        }

        // Sort by student name
        usort($roster, fn($a, $b) => strcmp($a['student_name'], $b['student_name']));

        return [
            'class' => $class,
            'date' => $date,
            'roster' => $roster,
            'session_stats' => $this->getSessionStats($classId, $date),
        ];
    }

    /**
     * Mark attendance for an entire class session
     */
    public function markClassSessionAttendance(
        int $classId,
        \DateTimeInterface $date,
        array $attendanceData,
        string $facultyId
    ): array {
        $results = DB::transaction(function () use ($classId, $date, $attendanceData, $facultyId) {
            $marked = [];
            $errors = [];

            foreach ($attendanceData as $data) {
                try {
                    $status = AttendanceStatus::from($data['status']);
                    $attendance = $this->attendanceService->markAttendance(
                        $classId,
                        $data['student_id'],
                        $status,
                        $date,
                        $data['remarks'] ?? null,
                        $facultyId
                    );
                    $marked[] = $attendance;
                } catch (\Exception $e) {
                    $errors[] = [
                        'student_id' => $data['student_id'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return ['marked' => $marked, 'errors' => $errors];
        });

        return $results;
    }

    /**
     * Get attendance sessions for a class (dates when attendance was taken)
     */
    public function getClassAttendanceSessions(int $classId, int $limit = 20): Collection
    {
        return Attendance::where('class_id', $classId)
            ->select('date', DB::raw('COUNT(*) as student_count'))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($session) use ($classId) {
                $date = Carbon::parse($session->date);
                $stats = $this->getSessionStats($classId, $date);
                
                return [
                    'date' => $date,
                    'student_count' => $session->student_count,
                    'stats' => $stats,
                    'formatted_date' => $date->format('M j, Y'),
                    'day_name' => $date->format('l'),
                ];
            });
    }

    /**
     * Get attendance statistics for a specific session
     */
    public function getSessionStats(int $classId, \DateTimeInterface $date): array
    {
        $attendances = Attendance::where('class_id', $classId)
            ->where('date', $date->format('Y-m-d'))
            ->get();

        return $this->attendanceService->calculateAttendanceStats($attendances);
    }

    /**
     * Get students with poor attendance in a class
     */
    public function getStudentsWithPoorAttendance(
        int $classId,
        float $threshold = 75.0
    ): Collection {
        $enrollments = class_enrollments::with(['student', 'ShsStudent'])
            ->where('class_id', $classId)
            ->get();

        return $enrollments->filter(function ($enrollment) use ($classId, $threshold) {
            $stats = $this->attendanceService->calculateStudentClassStats(
                $enrollment->student_id,
                $classId
            );
            return $stats['attendance_rate'] < $threshold;
        })->map(function ($enrollment) use ($classId) {
            $student = $enrollment->EnrolledStudent();
            $stats = $this->attendanceService->calculateStudentClassStats(
                $enrollment->student_id,
                $classId
            );

            return [
                'enrollment' => $enrollment,
                'student' => $student,
                'stats' => $stats,
                'student_name' => $student->fullname ?? ($student->first_name . ' ' . $student->last_name),
                'student_id_display' => $student instanceof \App\Models\ShsStudents ? $student->student_lrn : $student->id,
            ];
        })->sortBy('stats.attendance_rate');
    }

    /**
     * Generate quick attendance summary for faculty dashboard
     */
    public function getFacultyDashboardSummary(string $facultyId): array
    {
        $classes = Classes::where('faculty_id', $facultyId)->get();
        
        $totalClasses = $classes->count();
        $totalStudents = class_enrollments::whereIn('class_id', $classes->pluck('id'))->count();
        
        // Get recent attendance data (last 30 days)
        $recentDate = now()->subDays(30);
        $recentAttendances = Attendance::whereIn('class_id', $classes->pluck('id'))
            ->where('date', '>=', $recentDate->format('Y-m-d'))
            ->get();

        $overallStats = $this->attendanceService->calculateAttendanceStats($recentAttendances);

        // Get classes that need attention
        $classesNeedingAttention = $classes->filter(function ($class) {
            $stats = $this->attendanceService->calculateClassStats($class->id);
            return $stats['attendance_rate'] < 75;
        });

        // Get recent sessions
        $recentSessions = Attendance::whereIn('class_id', $classes->pluck('id'))
            ->select('class_id', 'date')
            ->distinct()
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($session) {
                $class = Classes::find($session->class_id);
                $stats = $this->getSessionStats($session->class_id, Carbon::parse($session->date));
                
                return [
                    'class' => $class,
                    'date' => Carbon::parse($session->date),
                    'stats' => $stats,
                ];
            });

        return [
            'total_classes' => $totalClasses,
            'total_students' => $totalStudents,
            'overall_stats' => $overallStats,
            'classes_needing_attention' => $classesNeedingAttention->count(),
            'recent_sessions' => $recentSessions,
            'attendance_trend' => $this->getAttendanceTrend($facultyId),
        ];
    }

    /**
     * Get attendance trend for faculty (last 4 weeks)
     */
    private function getAttendanceTrend(string $facultyId): array
    {
        $classes = Classes::where('faculty_id', $facultyId)->pluck('id');
        $weeks = [];
        
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            
            $weekAttendances = Attendance::whereIn('class_id', $classes)
                ->whereBetween('date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
                ->get();

            $stats = $this->attendanceService->calculateAttendanceStats($weekAttendances);
            
            $weeks[] = [
                'week' => $weekStart->format('M j'),
                'attendance_rate' => $stats['attendance_rate'],
            ];
        }

        return $weeks;
    }

    /**
     * Get the last attendance session for a class
     */
    private function getLastAttendanceSession(int $classId): ?array
    {
        $lastSession = Attendance::where('class_id', $classId)
            ->orderBy('date', 'desc')
            ->first();

        if (!$lastSession) {
            return null;
        }

        $date = Carbon::parse($lastSession->date);
        $stats = $this->getSessionStats($classId, $date);

        return [
            'date' => $date,
            'stats' => $stats,
            'days_ago' => $date->diffInDays(now()),
        ];
    }
}
