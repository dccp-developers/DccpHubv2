<?php

declare(strict_types=1);

namespace App\Services\Faculty;

use App\Models\Faculty;
use App\Models\Classes;
use App\Models\Schedule;
use App\Models\class_enrollments;
use App\Services\GeneralSettingsService;
use Carbon\Carbon;
use App\Models\FacultyDeadline;
use App\Models\Attendance;
use App\Enums\AttendanceStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

final class FacultyDashboardService
{
    public function __construct(
        private readonly FacultyStatsService $statsService,
        private readonly FacultyScheduleService $scheduleService,
        private readonly FacultyClassService $classService,
        private readonly FacultyActivityService $activityService,
        private readonly GeneralSettingsService $settingsService
    ) {}

    /**
     * Get comprehensive dashboard data for a faculty member
     */
    public function getDashboardData(Faculty $faculty): array
    {
        $cacheKey = "faculty_dashboard_{$faculty->id}_" . now()->format('Y-m-d-H');

        return Cache::remember($cacheKey, 3600, function () use ($faculty) {
            return [
                'faculty' => $this->getFacultyInfo($faculty),
                'stats' => $this->statsService->getStats($faculty),
                'todaysSchedule' => $this->scheduleService->getTodaysSchedule($faculty),
                'weeklySchedule' => $this->scheduleService->getWeeklySchedule($faculty),
                'classes' => $this->classService->getFacultyClasses($faculty),
                'classEnrollments' => $this->getClassEnrollments($faculty),
                'recentActivities' => $this->activityService->getRecentActivities($faculty),
                'upcomingDeadlines' => $this->getUpcomingDeadlines($faculty),
                'performanceMetrics' => $this->getPerformanceMetrics($faculty),
                'currentSemester' => $this->getCurrentSemester(),
                'schoolYear' => $this->getCurrentSchoolYear(),
                'scheduleOverview' => $this->getScheduleOverview($faculty),
            ];
        });
    }

    /**
     * Get faculty basic information
     */
    private function getFacultyInfo(Faculty $faculty): array
    {
        return [
            'id' => $faculty->id,
            'name' => $faculty->first_name . ' ' . $faculty->last_name,
            'full_name' => $faculty->full_name,
            'email' => $faculty->email,
            'department' => $faculty->department ?? 'Computer Science',
            'office_hours' => $faculty->office_hours ?? 'Mon-Fri 2:00-4:00 PM',
            'photo_url' => $faculty->photo_url,
            'status' => $faculty->status ?? 'active',
        ];
    }

    /**
     * Get upcoming deadlines for faculty
     */
    private function getUpcomingDeadlines(Faculty $faculty): Collection
    {
        return FacultyDeadline::query()
            ->where('is_active', true)
            ->where('faculty_id', $faculty->id)
            ->where('due_date', '>=', now()->subDay())
            ->orderBy('due_date')
            ->limit(10)
            ->get()
            ->map(function (FacultyDeadline $d) {
                return [
                    'id' => $d->id,
                    'title' => $d->title,
                    'description' => $d->description,
                    'due_date' => $d->due_date,
                    'priority' => $d->priority,
                    'type' => $d->type,
                    'class_id' => $d->class_id,
                    'class_code' => optional($d->class)->subject_code,
                ];
            });
    }

    /**
     * Get performance metrics for faculty
     */
    private function getPerformanceMetrics(Faculty $faculty): array
    {
        $classIds = $faculty->classes()->pluck('id');

        // Attendance (last 30 days)
        $attendanceQuery = Attendance::query()
            ->whereIn('class_id', $classIds)
            ->recent(30);

        $totalAttendance = (clone $attendanceQuery)->count();
        $presentAttendance = (clone $attendanceQuery)->present()->count();
        $absentAttendance = (clone $attendanceQuery)->absent()->count();
        $attendanceRate = $totalAttendance > 0 ? round(($presentAttendance / $totalAttendance) * 100, 1) : 0.0;

        // Attendance trend (last 6 weeks)
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->copy()->startOfWeek()->subWeeks($i);
            $end = $start->copy()->endOfWeek();
            $weekTotal = (clone $attendanceQuery)->whereBetween('date', [$start->toDateString(), $end->toDateString()])->count();
            $weekPresent = (clone $attendanceQuery)->whereBetween('date', [$start->toDateString(), $end->toDateString()])->present()->count();
            $weekRate = $weekTotal > 0 ? round(($weekPresent / $weekTotal) * 100, 1) : 0.0;
            $trend[] = [
                'label' => $start->format('M j'),
                'rate' => $weekRate,
            ];
        }

        // Grades/Completion
        $enrollmentsQuery = class_enrollments::whereIn('class_id', $classIds);
        $totalEnrollments = (clone $enrollmentsQuery)->count();
        $finalizedCount = (clone $enrollmentsQuery)
            ->where(function ($q) {
                $q->whereNotNull('total_average')
                  ->orWhereNotNull('finals_grade');
            })
            ->count();
        $completionRate = $totalEnrollments > 0 ? round(($finalizedCount / $totalEnrollments) * 100, 1) : 0.0;

        $passingRate = $this->calculatePassingRate($faculty);
        $averageGrade = $this->calculateAverageGrade($faculty);

        // Students at risk (>= 3 absences in last 30 days)
        $atRisk = Attendance::query()
            ->whereIn('class_id', $classIds)
            ->recent(30)
            ->where('status', AttendanceStatus::ABSENT)
            ->select('class_enrollment_id')
            ->groupBy('class_enrollment_id')
            ->havingRaw('COUNT(*) >= 3')
            ->get()
            ->count();

        // Teaching counts
        $totalClasses = $faculty->classes()->count();
        $totalStudents = class_enrollments::whereIn('class_id', $classIds)->distinct('student_id')->count('student_id');

        return [
            'attendance' => [
                'rate' => $attendanceRate,
                'present' => $presentAttendance,
                'absent' => $absentAttendance,
                'total' => $totalAttendance,
                'trend' => $trend,
                'period' => 'last 30 days',
            ],
            'grades' => [
                'completion_rate' => $completionRate,
                'finalized_count' => $finalizedCount,
                'total_enrollments' => $totalEnrollments,
                'passing_rate' => $passingRate,
                'average_grade' => $averageGrade,
            ],
            'teaching' => [
                'total_classes' => $totalClasses,
                'total_students' => $totalStudents,
                'students_at_risk' => $atRisk,
            ],
        ];
    }

    /**
     * Calculate average attendance for faculty's classes
     */
    private function calculateAverageAttendance(Faculty $faculty): float
    {
        // This would query actual attendance records
        // For now, return a calculated value based on class enrollments
        $classes = $faculty->classes()->with('ClassStudents')->get();

        if ($classes->isEmpty()) {
            return 0.0;
        }

        // Simulate attendance calculation
        return round(94.0 + (rand(-200, 200) / 100), 1);
    }

    /**
     * Calculate assignment completion rate
     */
    private function calculateAssignmentCompletion(Faculty $faculty): float
    {
        // This would query actual assignment submissions
        // For now, return a simulated value
        return round(87.0 + (rand(-500, 500) / 100), 1);
    }

    /**
     * Calculate passing rate for faculty's classes
     */
    private function calculatePassingRate(Faculty $faculty): float
    {
        $enrollments = class_enrollments::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id);
        })->whereNotNull('total_average')->get();

        if ($enrollments->isEmpty()) {
            return 0.0;
        }

        $passingCount = $enrollments->where('total_average', '>=', 75)->count();
        return round(($passingCount / $enrollments->count()) * 100, 1);
    }

    /**
     * Calculate average grade for faculty's classes
     */
    private function calculateAverageGrade(Faculty $faculty): float
    {
        $averageGrade = class_enrollments::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id);
        })->whereNotNull('total_average')->avg('total_average');

        // avg() may return a numeric string; cast to float for PHP 8.4 strict typing
        return round((float)($averageGrade ?? 0.0), 1);
    }

    /**
     * Get subject title for a class (handles both College and SHS)
     */
    private function getClassSubjectTitle($class): string
    {
        // Get the appropriate subject based on classification
        $subject = $class->classification === 'shs' ? $class->ShsSubject : $class->subject;
        return $subject ? $subject->title : 'Unknown Subject';
    }

    /**
     * Get class enrollments for faculty
     */
    private function getClassEnrollments(Faculty $faculty): Collection
    {
        return class_enrollments::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->with(['class.subject', 'class.ShsSubject', 'student.course', 'ShsStudent'])
        ->get()
        ->map(function ($enrollment) {
            return [
                'id' => $enrollment->id,
                'class_id' => $enrollment->class_id,
                'student_id' => $enrollment->student_id,
                'student_name' => $enrollment->student_name,
                'student_year_standing' => $enrollment->student_year_standing,
                'course_strand' => $enrollment->course_strand,
                'subject_code' => $enrollment->class->subject_code,
                'subject_title' => $this->getClassSubjectTitle($enrollment->class),
                'section' => $enrollment->class->section,
                'prelim_grade' => $enrollment->prelim_grade,
                'midterm_grade' => $enrollment->midterm_grade,
                'finals_grade' => $enrollment->finals_grade,
                'total_average' => $enrollment->total_average,
                'grade_status' => $enrollment->grade_status,
                'is_completed' => $enrollment->is_completed,
                'status' => $enrollment->status,
            ];
        });
    }

    /**
     * Get schedule overview for faculty
     */
    private function getScheduleOverview(Faculty $faculty): array
    {
        $weeklySchedule = $this->scheduleService->getWeeklySchedule($faculty);
        $todaysSchedule = $this->scheduleService->getTodaysSchedule($faculty);

        // Calculate schedule statistics
        $totalWeeklyHours = 0;
        $daysWithClasses = 0;
        $busyDays = [];

        foreach ($weeklySchedule as $day => $schedules) {
            if (!empty($schedules)) {
                $daysWithClasses++;
                $dayHours = 0;

                foreach ($schedules as $schedule) {
                    $start = \Carbon\Carbon::parse($schedule['raw_start_time']);
                    $end = \Carbon\Carbon::parse($schedule['raw_end_time']);
                    $dayHours += $start->diffInHours($end);
                }

                $totalWeeklyHours += $dayHours;
                $busyDays[$day] = $dayHours;
            }
        }

        return [
            'total_weekly_hours' => $totalWeeklyHours,
            'days_with_classes' => $daysWithClasses,
            'busiest_day' => !empty($busyDays) ? array_keys($busyDays, max($busyDays))[0] : null,
            'lightest_day' => !empty($busyDays) ? array_keys($busyDays, min($busyDays))[0] : null,
            'average_daily_hours' => $daysWithClasses > 0 ? round($totalWeeklyHours / $daysWithClasses, 1) : 0,
            'todays_classes_count' => count($todaysSchedule),
            'next_class' => $this->scheduleService->getNextClass($faculty),
        ];
    }

    /**
     * Get current semester using GeneralSettingsService
     */
    private function getCurrentSemester(): int
    {
        return $this->settingsService->getCurrentSemester();
    }

    /**
     * Get current school year using GeneralSettingsService
     */
    private function getCurrentSchoolYear(): string
    {
        return $this->settingsService->getCurrentSchoolYearString();
    }

    /**
     * Clear faculty dashboard cache
     */
    public function clearCache(Faculty $faculty): void
    {
        $pattern = "faculty_dashboard_{$faculty->id}_*";
        Cache::forget($pattern);
    }
}
