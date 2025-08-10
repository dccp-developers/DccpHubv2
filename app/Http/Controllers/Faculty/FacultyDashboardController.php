<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Faculty;
use App\Services\Faculty\FacultyDashboardService;
use App\Services\Faculty\FacultyAttendanceService;
use App\Services\Faculty\FacultyActivityService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

final class FacultyDashboardController extends Controller
{
    public function __construct(
        private readonly FacultyDashboardService $dashboardService,
        private readonly FacultyAttendanceService $attendanceService,
        private readonly FacultyActivityService $activityService,
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
     * Lazy-load recent activities for the Sheet
     */
    public function activities(): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->isFaculty()) {
            abort(403);
        }
        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        $offset = (int) request()->query('offset', 0);
        $limit = (int) request()->query('limit', 20);
        $type = request()->query('type');
        $classId = request()->query('class_id');

        $items = $this->activityService->getRecentActivitiesPaginated($faculty, $offset, $limit, $type, $classId ? (int)$classId : null);

        return response()->json([
            'success' => true,
            'data' => $items,
            'nextOffset' => $items->count() < $limit ? null : $offset + $limit,
        ]);
    }
}