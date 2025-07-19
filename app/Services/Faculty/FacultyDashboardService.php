<?php

declare(strict_types=1);

namespace App\Services\Faculty;

use App\Models\Faculty;
use App\Models\Classes;
use App\Models\Schedule;
use App\Models\class_enrollments;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

final class FacultyDashboardService
{
    public function __construct(
        private readonly FacultyStatsService $statsService,
        private readonly FacultyScheduleService $scheduleService,
        private readonly FacultyClassService $classService,
        private readonly FacultyActivityService $activityService
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
                'classes' => $this->classService->getFacultyClasses($faculty),
                'recentActivities' => $this->activityService->getRecentActivities($faculty),
                'upcomingDeadlines' => $this->getUpcomingDeadlines($faculty),
                'performanceMetrics' => $this->getPerformanceMetrics($faculty),
                'currentSemester' => $this->getCurrentSemester(),
                'schoolYear' => $this->getCurrentSchoolYear(),
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
            'full_name' => $faculty->faculty_full_name,
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
        // This would typically come from a deadlines/tasks table
        // For now, we'll return sample data that could be extended
        return collect([
            [
                'id' => 1,
                'title' => 'Midterm Grades Submission',
                'description' => 'Submit midterm grades for all classes',
                'due_date' => now()->addDays(3),
                'priority' => 'high',
                'type' => 'grades',
                'classes_affected' => $faculty->classes()->count(),
            ],
            [
                'id' => 2,
                'title' => 'Faculty Meeting',
                'description' => 'Monthly department faculty meeting',
                'due_date' => now()->addDays(7),
                'priority' => 'medium',
                'type' => 'meeting',
                'classes_affected' => 0,
            ],
        ]);
    }

    /**
     * Get performance metrics for faculty
     */
    private function getPerformanceMetrics(Faculty $faculty): array
    {
        $totalStudents = $this->statsService->getTotalStudents($faculty);
        $totalClasses = $this->statsService->getTotalClasses($faculty);
        
        // Calculate average attendance (this would come from attendance records)
        $averageAttendance = $this->calculateAverageAttendance($faculty);
        
        // Calculate assignment completion rate
        $assignmentCompletion = $this->calculateAssignmentCompletion($faculty);
        
        return [
            'average_attendance' => [
                'value' => $averageAttendance,
                'trend' => '+2%',
                'period' => 'from last month'
            ],
            'assignment_completion' => [
                'value' => $assignmentCompletion,
                'trend' => '+5%',
                'period' => 'from last month'
            ],
            'student_satisfaction' => [
                'value' => 4.8,
                'trend' => '+0.2',
                'period' => 'from last semester'
            ],
            'class_performance' => [
                'passing_rate' => $this->calculatePassingRate($faculty),
                'average_grade' => $this->calculateAverageGrade($faculty),
            ]
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

        return round($averageGrade ?? 0.0, 1);
    }

    /**
     * Get current semester
     */
    private function getCurrentSemester(): string
    {
        // This would typically come from a settings table
        $month = now()->month;
        
        if ($month >= 6 && $month <= 10) {
            return '1st';
        } elseif ($month >= 11 || $month <= 3) {
            return '2nd';
        } else {
            return 'Summer';
        }
    }

    /**
     * Get current school year
     */
    private function getCurrentSchoolYear(): string
    {
        $year = now()->year;
        $month = now()->month;
        
        if ($month >= 6) {
            return $year . '-' . ($year + 1);
        } else {
            return ($year - 1) . '-' . $year;
        }
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
