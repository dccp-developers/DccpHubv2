<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Faculty;
use App\Services\Faculty\FacultyDashboardService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

final class FacultyDashboardController extends Controller
{
    public function __construct(
        private readonly FacultyDashboardService $dashboardService
    ) {}

    /**
     * Main faculty dashboard action
     */
    public function __invoke(): Response|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

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
}