<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Faculty;
use App\Services\Faculty\FacultyClassService;
use App\Services\Faculty\FacultyScheduleService;
use App\Services\Faculty\FacultyStatsService;
use App\Services\GeneralSettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class FacultyClassController extends Controller
{
    public function __construct(
        private readonly FacultyClassService $classService,
        private readonly FacultyScheduleService $scheduleService,
        private readonly FacultyStatsService $statsService,
        private readonly GeneralSettingsService $settingsService
    ) {}

    /**
     * Display a listing of faculty classes
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Ensure the user is a faculty member
        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can access this page.');
        }

        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        if (!$faculty) {
            abort(404, 'Faculty record not found.');
        }

        try {
            // Get all classes for the faculty
            $classes = $this->classService->getFacultyClasses($faculty);

            // Get class statistics
            $stats = $this->statsService->getStats($faculty);

            // Get subjects taught by faculty
            $subjects = $this->classService->getSubjectsTaught($faculty);

            return Inertia::render('Faculty/ClassList', [
                'classes' => $classes,
                'stats' => $stats,
                'subjects' => $subjects,
                'faculty' => [
                    'id' => $faculty->id,
                    'name' => $faculty->first_name . ' ' . $faculty->last_name,
                    'full_name' => $faculty->full_name,
                    'email' => $faculty->email,
                ],
                'currentSemester' => $this->settingsService->getCurrentSemester(),
                'schoolYear' => $this->settingsService->getCurrentSchoolYearString(),
                'availableSemesters' => $this->settingsService->getAvailableSemesters(),
                'availableSchoolYears' => $this->settingsService->getAvailableSchoolYears(),
            ]);
        } catch (\Exception $e) {
            // Log the error and return a fallback response
            logger()->error('Faculty class list error', [
                'faculty_id' => $faculty->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('Faculty/ClassList', [
                'error' => 'Unable to load class data. Please try again later.',
                'classes' => [],
                'stats' => [],
                'subjects' => [],
                'faculty' => [
                    'name' => $faculty->first_name . ' ' . $faculty->last_name,
                    'email' => $faculty->email,
                ],
                'currentSemester' => $this->settingsService->getCurrentSemester(),
                'schoolYear' => $this->settingsService->getCurrentSchoolYearString(),
            ]);
        }
    }

    /**
     * Display the specified class for faculty management
     */
    public function show(Request $request, Classes $class): Response
    {
        $user = $request->user();
        
        // Ensure the user is a faculty member
        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can access this page.');
        }

        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        if (!$faculty) {
            abort(404, 'Faculty record not found.');
        }

        // Verify that this class belongs to the faculty member
        if ($class->faculty_id !== $faculty->id) {
            abort(403, 'You do not have permission to view this class.');
        }

        try {
            // Get comprehensive class data
            $classData = $this->getClassData($faculty, $class);

            return Inertia::render('Faculty/ClassView', $classData);
        } catch (\Exception $e) {
            // Log the error and return a fallback response
            logger()->error('Faculty class view error', [
                'faculty_id' => $faculty->id,
                'class_id' => $class->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Inertia::render('Faculty/ClassView', [
                'error' => 'Unable to load class data. Please try again later.',
                'class' => [
                    'id' => $class->id,
                    'subject_code' => $class->subject_code,
                    'section' => $class->section,
                ],
                'faculty' => [
                    'name' => $faculty->first_name . ' ' . $faculty->last_name,
                    'email' => $faculty->email,
                ],
                'students' => [],
                'schedules' => [],
                'stats' => [],
                'currentSemester' => $this->settingsService->getCurrentSemester(),
                'schoolYear' => $this->settingsService->getCurrentSchoolYearString(),
            ]);
        }
    }

    /**
     * Get comprehensive class data for the view
     */
    private function getClassData(Faculty $faculty, Classes $class): array
    {
        // Get detailed class information
        $classDetails = $this->classService->getClassDetails($faculty, $class->id);
        
        // Get class schedules
        $schedules = $this->scheduleService->getClassSchedules($faculty, $class->id);
        
        // Get class statistics
        $stats = $this->getClassStats($faculty, $class);
        
        // Get class performance metrics
        $performance = $this->classService->getClassPerformance($faculty, $class->id);

        return [
            'class' => $classDetails,
            'schedules' => $schedules,
            'stats' => $stats,
            'performance' => $performance,
            'faculty' => [
                'id' => $faculty->id,
                'name' => $faculty->first_name . ' ' . $faculty->last_name,
                'full_name' => $faculty->full_name,
                'email' => $faculty->email,
            ],
            'currentSemester' => $this->settingsService->getCurrentSemester(),
            'schoolYear' => $this->settingsService->getCurrentSchoolYearString(),
            'availableSemesters' => $this->settingsService->getAvailableSemesters(),
            'availableSchoolYears' => $this->settingsService->getAvailableSchoolYears(),
        ];
    }

    /**
     * Get class-specific statistics
     */
    private function getClassStats(Faculty $faculty, Classes $class): array
    {
        $enrollments = $class->ClassStudents;
        $totalStudents = $enrollments->count();
        
        // Calculate attendance rate (placeholder - would need actual attendance data)
        $attendanceRate = 85; // Default placeholder
        
        // Calculate grade distribution
        $gradeDistribution = [
            'A' => $enrollments->where('total_average', '>=', 90)->count(),
            'B' => $enrollments->whereBetween('total_average', [80, 89])->count(),
            'C' => $enrollments->whereBetween('total_average', [70, 79])->count(),
            'D' => $enrollments->whereBetween('total_average', [60, 69])->count(),
            'F' => $enrollments->where('total_average', '<', 60)->count(),
        ];
        
        // Calculate average grade
        $averageGrade = $enrollments->whereNotNull('total_average')->avg('total_average') ?? 0;
        
        return [
            'total_students' => $totalStudents,
            'attendance_rate' => $attendanceRate,
            'average_grade' => round($averageGrade, 2),
            'grade_distribution' => $gradeDistribution,
            'passing_rate' => $totalStudents > 0 ? round((($totalStudents - $gradeDistribution['F']) / $totalStudents) * 100, 1) : 0,
        ];
    }
}
