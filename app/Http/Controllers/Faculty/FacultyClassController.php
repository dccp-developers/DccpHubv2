<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Faculty;
use App\Services\Faculty\FacultyClassService;
use App\Services\Faculty\FacultyScheduleService;
use App\Services\Faculty\ClassAttendanceManagementService;
use App\Services\Faculty\FacultyStatsService;
use App\Enums\AttendanceMethod;
use App\Enums\AttendancePolicy;
use App\Services\GeneralSettingsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

final class FacultyClassController extends Controller
{
    public function __construct(
        private readonly FacultyClassService $classService,
        private readonly FacultyScheduleService $scheduleService,
        private readonly FacultyStatsService $statsService,
        private readonly ClassAttendanceManagementService $attendanceManagementService,
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
                'currentSemester' => (string) $this->settingsService->getCurrentSemester(),
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
                'currentSemester' => (string) $this->settingsService->getCurrentSemester(),
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
                'classData' => [
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
                'currentSemester' => (string) $this->settingsService->getCurrentSemester(),
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

        // Get attendance settings and data
        $attendanceSettings = $this->attendanceManagementService->getAttendanceSettings($class->id);
        $attendanceData = null;

        if ($attendanceSettings && $attendanceSettings->is_enabled) {
            try {
                $attendanceData = $this->attendanceManagementService->getClassAttendanceData($class->id);
            } catch (\Exception $e) {
                // Log error but don't fail the entire page
                logger()->error('Failed to load attendance data', [
                    'class_id' => $class->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'classData' => $classDetails,
            'schedules' => $schedules,
            'stats' => $stats,
            'performance' => $performance,
            'attendance' => [
                'settings' => $attendanceSettings?->getFormattedSettings(),
                'data' => $attendanceData,
                'is_setup' => $attendanceSettings !== null && $attendanceSettings->is_enabled,
                'methods' => AttendanceMethod::options(),
                'policies' => AttendancePolicy::options(),
            ],
            'faculty' => [
                'id' => $faculty->id,
                'name' => $faculty->first_name . ' ' . $faculty->last_name,
                'full_name' => $faculty->full_name,
                'email' => $faculty->email,
            ],
            'currentSemester' => (string) $this->settingsService->getCurrentSemester(),
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

    /**
     * Setup attendance settings for a class
     */
    public function setupAttendance(Request $request, Classes $class): JsonResponse
    {
        $user = $request->user();

        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can setup attendance.');
        }

        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        if ($class->faculty_id !== $faculty->id) {
            abort(403, 'You can only setup attendance for your own classes.');
        }

        $validated = $request->validate([
            'is_enabled' => 'required|boolean',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'attendance_method' => 'required|string|in:' . implode(',', array_column(AttendanceMethod::cases(), 'value')),
            'attendance_policy' => 'required|string|in:' . implode(',', array_column(AttendancePolicy::cases(), 'value')),
            'grace_period_minutes' => 'required|integer|min:0|max:60',
            'auto_mark_absent_minutes' => 'nullable|integer|min:1',
            'allow_late_checkin' => 'required|boolean',
            'checkin_start_time' => 'nullable|date_format:H:i',
            'checkin_end_time' => 'nullable|date_format:H:i|after:checkin_start_time',
            'require_confirmation' => 'nullable|boolean',
            'show_class_list' => 'nullable|boolean',
            'notify_absent_students' => 'required|boolean',
            'notify_late_students' => 'required|boolean',
            'send_daily_summary' => 'required|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $settings = $this->attendanceManagementService->setupAttendanceSettings(
                $class->id,
                $faculty->id,
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Attendance settings saved successfully.',
                'settings' => $settings->getFormattedSettings(),
            ]);
        } catch (\Exception $e) {
            logger()->error('Failed to setup attendance settings', [
                'class_id' => $class->id,
                'faculty_id' => $faculty->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save attendance settings. Please try again.',
            ], 500);
        }
    }

    /**
     * Get attendance data for a specific date
     */
    public function getAttendanceData(Request $request, Classes $class): JsonResponse
    {
        $user = $request->user();

        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can access attendance data.');
        }

        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        if ($class->faculty_id !== $faculty->id) {
            abort(403, 'You can only access attendance for your own classes.');
        }

        $validated = $request->validate([
            'date' => 'nullable|date',
        ]);

        try {
            $date = $validated['date'] ? Carbon::parse($validated['date']) : now();
            $attendanceData = $this->attendanceManagementService->getClassAttendanceData($class->id, $date);

            return response()->json([
                'success' => true,
                'data' => $attendanceData,
            ]);
        } catch (\Exception $e) {
            logger()->error('Failed to get attendance data', [
                'class_id' => $class->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load attendance data.',
            ], 500);
        }
    }

    /**
     * Initialize attendance session
     */
    public function initializeAttendance(Request $request, Classes $class): JsonResponse
    {
        $user = $request->user();

        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can initialize attendance.');
        }

        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        if ($class->faculty_id !== $faculty->id) {
            abort(403, 'You can only initialize attendance for your own classes.');
        }

        $validated = $request->validate([
            'date' => 'nullable|date',
        ]);

        try {
            $date = $validated['date'] ? Carbon::parse($validated['date']) : now();
            $attendanceData = $this->attendanceManagementService->initializeAttendanceSession(
                $class->id,
                $faculty->id,
                $date
            );

            return response()->json([
                'success' => true,
                'message' => 'Attendance session initialized successfully.',
                'data' => $attendanceData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update student attendance
     */
    public function updateAttendance(Request $request, Classes $class): JsonResponse
    {
        $user = $request->user();

        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can update attendance.');
        }

        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        if ($class->faculty_id !== $faculty->id) {
            abort(403, 'You can only update attendance for your own classes.');
        }

        $validated = $request->validate([
            'student_id' => 'required|string',
            'status' => 'required|string|in:present,absent,late,excused',
            'date' => 'nullable|date',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $date = $validated['date'] ? Carbon::parse($validated['date']) : now();
            $result = $this->attendanceManagementService->updateStudentAttendance(
                $class->id,
                $validated['student_id'],
                $validated['status'],
                $faculty->id,
                $date,
                $validated['remarks'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully.',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            logger()->error('Failed to update attendance', [
                'class_id' => $class->id,
                'student_id' => $validated['student_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update attendance.',
            ], 500);
        }
    }

    /**
     * Bulk update attendance
     */
    public function bulkUpdateAttendance(Request $request, Classes $class): JsonResponse
    {
        $user = $request->user();

        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can update attendance.');
        }

        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        if ($class->faculty_id !== $faculty->id) {
            abort(403, 'You can only update attendance for your own classes.');
        }

        $validated = $request->validate([
            'attendance_data' => 'required|array',
            'attendance_data.*.student_id' => 'required|string',
            'attendance_data.*.status' => 'required|string|in:present,absent,late,excused',
            'attendance_data.*.remarks' => 'nullable|string|max:500',
            'date' => 'nullable|date',
        ]);

        try {
            $date = $validated['date'] ? Carbon::parse($validated['date']) : now();
            $result = $this->attendanceManagementService->bulkUpdateAttendance(
                $class->id,
                $validated['attendance_data'],
                $faculty->id,
                $date
            );

            return response()->json([
                'success' => true,
                'message' => "Updated attendance for {$result['updated_count']} students.",
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            logger()->error('Failed to bulk update attendance', [
                'class_id' => $class->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update attendance.',
            ], 500);
        }
    }
}
