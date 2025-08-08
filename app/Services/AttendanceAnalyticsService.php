<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attendance;
use App\Models\Classes;
use App\Models\class_enrollments;
use App\Models\Faculty;
use App\Enums\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Attendance Analytics Service
 * 
 * Provides advanced analytics and insights for attendance data
 */
final class AttendanceAnalyticsService
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    /**
     * Generate comprehensive analytics for a faculty member
     */
    public function getFacultyAnalytics(
        string $facultyId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $startDate = $startDate ?? now()->subMonths(3);
        $endDate = $endDate ?? now();

        $classes = Classes::where('faculty_id', $facultyId)->get();
        $classIds = $classes->pluck('id');

        return [
            'overview' => $this->getOverviewMetrics($classIds, $startDate, $endDate),
            'trends' => $this->getAttendanceTrends($classIds, $startDate, $endDate),
            'class_comparison' => $this->getClassComparison($classIds, $startDate, $endDate),
            'student_insights' => $this->getStudentInsights($classIds, $startDate, $endDate),
            'time_patterns' => $this->getTimePatterns($classIds, $startDate, $endDate),
            'recommendations' => $this->generateRecommendations($classIds, $startDate, $endDate),
        ];
    }

    /**
     * Generate analytics for a specific class
     */
    public function getClassAnalytics(
        int $classId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $startDate = $startDate ?? now()->subMonths(3);
        $endDate = $endDate ?? now();

        return [
            'overview' => $this->getOverviewMetrics([$classId], $startDate, $endDate),
            'trends' => $this->getAttendanceTrends([$classId], $startDate, $endDate),
            'student_performance' => $this->getStudentPerformanceAnalysis($classId, $startDate, $endDate),
            'session_analysis' => $this->getSessionAnalysis($classId, $startDate, $endDate),
            'patterns' => $this->getAttendancePatterns($classId, $startDate, $endDate),
            'predictions' => $this->generateAttendancePredictions($classId),
        ];
    }

    /**
     * Get overview metrics
     */
    private function getOverviewMetrics(array $classIds, Carbon $startDate, Carbon $endDate): array
    {
        $attendances = Attendance::whereIn('class_id', $classIds)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $stats = $this->attendanceService->calculateAttendanceStats($attendances);

        // Calculate additional metrics
        $totalStudents = class_enrollments::whereIn('class_id', $classIds)->distinct('student_id')->count();
        $totalSessions = $attendances->groupBy(['class_id', 'date'])->count();
        $averageSessionAttendance = $totalSessions > 0 ? $attendances->count() / $totalSessions : 0;

        return array_merge($stats, [
            'total_students' => $totalStudents,
            'total_sessions' => $totalSessions,
            'average_session_attendance' => round($averageSessionAttendance, 2),
            'engagement_score' => $this->calculateEngagementScore($stats),
        ]);
    }

    /**
     * Get attendance trends over time
     */
    private function getAttendanceTrends(array $classIds, Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $period = $startDate->copy();

        while ($period->lte($endDate)) {
            $weekEnd = $period->copy()->endOfWeek();
            if ($weekEnd->gt($endDate)) {
                $weekEnd = $endDate->copy();
            }

            $weekAttendances = Attendance::whereIn('class_id', $classIds)
                ->whereBetween('date', [$period->format('Y-m-d'), $weekEnd->format('Y-m-d')])
                ->get();

            $weekStats = $this->attendanceService->calculateAttendanceStats($weekAttendances);

            $trends[] = [
                'period' => $period->format('Y-m-d'),
                'period_end' => $weekEnd->format('Y-m-d'),
                'week_label' => $period->format('M j'),
                'stats' => $weekStats,
                'sessions' => $weekAttendances->groupBy(['class_id', 'date'])->count(),
            ];

            $period->addWeek();
        }

        return $trends;
    }

    /**
     * Compare performance across classes
     */
    private function getClassComparison(array $classIds, Carbon $startDate, Carbon $endDate): array
    {
        $comparison = [];

        foreach ($classIds as $classId) {
            $class = Classes::find($classId);
            if (!$class) continue;

            $classAttendances = Attendance::where('class_id', $classId)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get();

            $stats = $this->attendanceService->calculateAttendanceStats($classAttendances);
            $enrollmentCount = class_enrollments::where('class_id', $classId)->count();

            $comparison[] = [
                'class' => $class,
                'stats' => $stats,
                'enrollment_count' => $enrollmentCount,
                'sessions_count' => $classAttendances->groupBy('date')->count(),
                'performance_grade' => $this->getPerformanceGrade($stats['attendance_rate']),
            ];
        }

        // Sort by attendance rate
        usort($comparison, fn($a, $b) => $b['stats']['attendance_rate'] <=> $a['stats']['attendance_rate']);

        return $comparison;
    }

    /**
     * Get student insights and risk analysis
     */
    private function getStudentInsights(array $classIds, Carbon $startDate, Carbon $endDate): array
    {
        $enrollments = class_enrollments::whereIn('class_id', $classIds)->get();
        $insights = [
            'at_risk_students' => [],
            'top_performers' => [],
            'improvement_needed' => [],
            'consistent_performers' => [],
        ];

        foreach ($enrollments as $enrollment) {
            $studentAttendances = Attendance::where('student_id', $enrollment->student_id)
                ->whereIn('class_id', $classIds)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get();

            $stats = $this->attendanceService->calculateAttendanceStats($studentAttendances);
            $student = $enrollment->EnrolledStudent();

            $studentData = [
                'student' => $student,
                'enrollment' => $enrollment,
                'stats' => $stats,
                'trend' => $this->getStudentTrend($enrollment->student_id, $classIds, $startDate, $endDate),
            ];

            // Categorize students
            if ($stats['attendance_rate'] < 60) {
                $insights['at_risk_students'][] = $studentData;
            } elseif ($stats['attendance_rate'] >= 95) {
                $insights['top_performers'][] = $studentData;
            } elseif ($stats['attendance_rate'] < 75) {
                $insights['improvement_needed'][] = $studentData;
            } else {
                $insights['consistent_performers'][] = $studentData;
            }
        }

        // Sort each category
        foreach ($insights as &$category) {
            usort($category, fn($a, $b) => $a['stats']['attendance_rate'] <=> $b['stats']['attendance_rate']);
        }

        return $insights;
    }

    /**
     * Analyze time-based patterns
     */
    private function getTimePatterns(array $classIds, Carbon $startDate, Carbon $endDate): array
    {
        $attendances = Attendance::whereIn('class_id', $classIds)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        // Day of week patterns
        $dayPatterns = [];
        $dayGroups = $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->date)->format('l');
        });

        foreach ($dayGroups as $day => $dayAttendances) {
            $stats = $this->attendanceService->calculateAttendanceStats($dayAttendances);
            $dayPatterns[] = [
                'day' => $day,
                'stats' => $stats,
                'sessions' => $dayAttendances->groupBy(['class_id', 'date'])->count(),
            ];
        }

        // Month patterns
        $monthPatterns = [];
        $monthGroups = $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->date)->format('Y-m');
        });

        foreach ($monthGroups as $month => $monthAttendances) {
            $stats = $this->attendanceService->calculateAttendanceStats($monthAttendances);
            $monthPatterns[] = [
                'month' => $month,
                'month_label' => Carbon::createFromFormat('Y-m', $month)->format('F Y'),
                'stats' => $stats,
                'sessions' => $monthAttendances->groupBy(['class_id', 'date'])->count(),
            ];
        }

        return [
            'by_day' => $dayPatterns,
            'by_month' => $monthPatterns,
            'peak_attendance_day' => collect($dayPatterns)->sortByDesc('stats.attendance_rate')->first()['day'] ?? null,
            'lowest_attendance_day' => collect($dayPatterns)->sortBy('stats.attendance_rate')->first()['day'] ?? null,
        ];
    }

    /**
     * Generate recommendations based on analytics
     */
    private function generateRecommendations(array $classIds, Carbon $startDate, Carbon $endDate): array
    {
        $recommendations = [];
        $overview = $this->getOverviewMetrics($classIds, $startDate, $endDate);
        $patterns = $this->getTimePatterns($classIds, $startDate, $endDate);

        // Overall attendance recommendations
        if ($overview['attendance_rate'] < 75) {
            $recommendations[] = [
                'type' => 'critical',
                'title' => 'Low Overall Attendance',
                'description' => 'Overall attendance is below 75%. Consider implementing attendance incentives or reviewing class engagement strategies.',
                'action' => 'Review teaching methods and student engagement',
            ];
        }

        // Day-specific recommendations
        if ($patterns['lowest_attendance_day']) {
            $recommendations[] = [
                'type' => 'improvement',
                'title' => 'Low Attendance on ' . $patterns['lowest_attendance_day'],
                'description' => 'Consider scheduling important content or activities on this day to improve attendance.',
                'action' => 'Adjust schedule or add engaging content',
            ];
        }

        // Student risk recommendations
        $insights = $this->getStudentInsights($classIds, $startDate, $endDate);
        if (count($insights['at_risk_students']) > 0) {
            $recommendations[] = [
                'type' => 'urgent',
                'title' => 'Students at Risk',
                'description' => count($insights['at_risk_students']) . ' students have attendance below 60%. Immediate intervention recommended.',
                'action' => 'Contact students and provide support',
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate engagement score based on attendance patterns
     */
    private function calculateEngagementScore(array $stats): float
    {
        $attendanceWeight = 0.6;
        $consistencyWeight = 0.4;

        $attendanceScore = $stats['attendance_rate'] / 100;
        $consistencyScore = 1 - ($stats['absent'] / max($stats['total'], 1));

        return round(($attendanceScore * $attendanceWeight + $consistencyScore * $consistencyWeight) * 100, 2);
    }

    /**
     * Get performance grade based on attendance rate
     */
    private function getPerformanceGrade(float $attendanceRate): string
    {
        if ($attendanceRate >= 95) return 'A+';
        if ($attendanceRate >= 90) return 'A';
        if ($attendanceRate >= 85) return 'B+';
        if ($attendanceRate >= 80) return 'B';
        if ($attendanceRate >= 75) return 'C+';
        if ($attendanceRate >= 70) return 'C';
        if ($attendanceRate >= 60) return 'D';
        return 'F';
    }

    /**
     * Get student attendance trend
     */
    private function getStudentTrend(string $studentId, array $classIds, Carbon $startDate, Carbon $endDate): string
    {
        $attendances = Attendance::where('student_id', $studentId)
            ->whereIn('class_id', $classIds)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('date')
            ->get();

        if ($attendances->count() < 4) {
            return 'insufficient_data';
        }

        $firstHalf = $attendances->take($attendances->count() / 2);
        $secondHalf = $attendances->skip($attendances->count() / 2);

        $firstHalfRate = $this->attendanceService->calculateAttendanceStats($firstHalf)['attendance_rate'];
        $secondHalfRate = $this->attendanceService->calculateAttendanceStats($secondHalf)['attendance_rate'];

        $difference = $secondHalfRate - $firstHalfRate;

        if ($difference > 10) return 'improving';
        if ($difference < -10) return 'declining';
        return 'stable';
    }

    /**
     * Additional methods for class-specific analytics would go here...
     */
    private function getStudentPerformanceAnalysis(int $classId, Carbon $startDate, Carbon $endDate): array
    {
        // Implementation for detailed student performance analysis
        return [];
    }

    private function getSessionAnalysis(int $classId, Carbon $startDate, Carbon $endDate): array
    {
        // Implementation for session-by-session analysis
        return [];
    }

    private function getAttendancePatterns(int $classId, Carbon $startDate, Carbon $endDate): array
    {
        // Implementation for detailed attendance patterns
        return [];
    }

    private function generateAttendancePredictions(int $classId): array
    {
        // Implementation for attendance predictions
        return [];
    }
}
