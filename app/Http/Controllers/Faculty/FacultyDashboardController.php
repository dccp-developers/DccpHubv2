<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Faculty;
use App\Services\Faculty\FacultyDashboardService;
use App\Services\Faculty\FacultyAttendanceService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

final class FacultyDashboardController extends Controller
{
    public function __construct(
        private readonly FacultyDashboardService $dashboardService,
        private readonly FacultyAttendanceService $attendanceService
    ) {}

    /**
     * Main faculty dashboard action
     */
    public function __invoke(): Response|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Eager load team relationships to prevent lazy loading issues
        $user->load(['currentTeam', 'ownedTeams', 'teams']);

        // Ensure the user is a faculty member
        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can access this dashboard.');
        }

        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        if (!$faculty) {
            abort(404, 'Faculty record not found.');
        }

        try {
            // Get comprehensive dashboard data using the service
            $dashboardData = $this->dashboardService->getDashboardData($faculty);

            // Get attendance data for the widget
            $attendanceData = $this->getAttendanceData($faculty);
            $dashboardData['attendanceData'] = $attendanceData;

            return Inertia::render('Faculty/Dashboard', $dashboardData);
        } catch (\Exception $e) {
            // Log the error and return a fallback response
            logger()->error('Faculty dashboard error', [
                'faculty_id' => $faculty->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return basic dashboard with error message
            return Inertia::render('Faculty/Dashboard', [
                'faculty' => [
                    'name' => $faculty->first_name . ' ' . $faculty->last_name,
                    'email' => $faculty->email,
                ],
                'error' => 'Unable to load dashboard data. Please try again later.',
                'stats' => [],
                'classes' => [],
                'todaysSchedule' => [],
                'recentActivities' => [],
                'upcomingDeadlines' => [],
                'performanceMetrics' => [],
                'currentSemester' => '1st',
                'schoolYear' => now()->year . '-' . (now()->year + 1),
            ]);
        }
    }

    /**
     * Get attendance data for faculty dashboard widget
     */
    private function getAttendanceData(Faculty $faculty): array
    {
        try {
            $summary = $this->attendanceService->getFacultyDashboardSummary($faculty->id);

            return [
                'overallStats' => $summary['overall_stats'],
                'recentSessions' => collect($summary['recent_sessions'])->take(5)->toArray(),
                'attendanceTrend' => $summary['attendance_trend'],
                'studentsAtRisk' => $summary['classes_needing_attention']
            ];
        } catch (\Exception $e) {
            logger()->error('Failed to get faculty attendance data', [
                'faculty_id' => $faculty->id,
                'error' => $e->getMessage()
            ]);

            return [
                'overallStats' => ['attendance_rate' => 0, 'total' => 0, 'present_count' => 0],
                'recentSessions' => [],
                'attendanceTrend' => [],
                'studentsAtRisk' => 0
            ];
        }
    }
}