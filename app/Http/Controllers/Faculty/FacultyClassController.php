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
use App\Services\AttendanceService;
use App\Enums\AttendanceMethod;
use App\Enums\AttendancePolicy;
use App\Services\GeneralSettingsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

final class FacultyClassController extends Controller
{
    public function __construct(
        private readonly FacultyClassService $classService,
        private readonly FacultyScheduleService $scheduleService,
        private readonly FacultyStatsService $statsService,
        private readonly ClassAttendanceManagementService $attendanceManagementService,
        private readonly GeneralSettingsService $settingsService,
        private readonly AttendanceService $attendanceService
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

            // Get class statistics with enhanced data for Today's Overview
            $stats = $this->getEnhancedStats($faculty, $classes);

            // Get subjects taught by faculty
            $subjects = $this->classService->getSubjectsTaught($faculty);

            // Get today's schedule data
            $todaysData = $this->getTodaysData($faculty, $classes);

            return Inertia::render('Faculty/ClassList', [
                'classes' => $classes,
                'stats' => $stats,
                'subjects' => $subjects,
                'todaysData' => $todaysData,
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

            return Inertia::render('Faculty/ClassViewFixed', $classData);
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
     * Get enhanced statistics for Today's Overview
     */
    private function getEnhancedStats(Faculty $faculty, $classes): array
    {
        $stats = [];

        foreach ($classes as $classData) {
            // Since getFacultyClasses returns arrays, we need to get the actual model
            $class = Classes::find($classData['id']);
            if (!$class) {
                continue;
            }

            $classStats = $this->getClassStats($faculty, $class);
            $classStats['class_id'] = $class->id;
            $classStats['subject_code'] = $classData['subject_code'];
            $classStats['section'] = $classData['section'];

            // Add graded students count
            $enrollments = $class->ClassStudents;
            $gradedStudents = $enrollments->whereNotNull('total_average')->count();
            $classStats['graded_students'] = $gradedStudents;

            $stats[] = $classStats;
        }

        return $stats;
    }

    /**
     * Get today's data for the overview section
     */
    private function getTodaysData(Faculty $faculty, $classes): array
    {
        $today = Carbon::now();
        $todaySchedules = [];
        $recentActivities = [];

        // Get today's schedules (this would be more robust with actual schedule data)
        foreach ($classes as $classData) {
            // Since getFacultyClasses returns arrays, we need to get the actual model
            $class = Classes::find($classData['id']);
            if (!$class) {
                continue;
            }

            // Mock schedule data - in real implementation, this would come from schedule service
            $schedules = $this->scheduleService->getClassSchedules($faculty, $class->id);

            foreach ($schedules as $schedule) {
                if ($this->isScheduleForToday($schedule, $today)) {
                    $todaySchedules[] = [
                        'id' => $schedule['id'] ?? $class->id,
                        'class_id' => $class->id,
                        'subject_code' => $classData['subject_code'],
                        'section' => $classData['section'],
                        'time_range' => $schedule['time_range'] ?? 'TBA',
                        'room' => $schedule['room'] ?? $classData['room'] ?? 'TBA',
                        'status' => $this->getScheduleStatus($schedule, $today),
                        'class' => $classData // Pass the formatted class data
                    ];
                }
            }
        }

        // Get recent activities
        foreach ($classes as $classData) {
            $studentCount = $classData['student_count'] ?? 0;

            // Recent enrollment activity
            if ($studentCount > 0) {
                $recentActivities[] = [
                    'id' => 'enrollment-' . $classData['id'],
                    'type' => 'enrollment',
                    'title' => 'Student Enrollment',
                    'description' => "{$studentCount} students enrolled in {$classData['subject_code']}",
                    'timestamp' => $today->copy()->subHours(rand(1, 48))->toISOString(),
                    'class_id' => $classData['id']
                ];
            }

            // Recent attendance activity (if attendance data exists)
            try {
                $attendanceStats = $this->attendanceService->calculateClassStats($classData['id']);
                if ($attendanceStats['attendance_rate'] > 0) {
                    $recentActivities[] = [
                        'id' => 'attendance-' . $classData['id'],
                        'type' => 'attendance',
                        'title' => 'Attendance Updated',
                        'description' => "Attendance rate: {$attendanceStats['attendance_rate']}%",
                        'timestamp' => $today->copy()->subHours(rand(1, 72))->toISOString(),
                        'class_id' => $classData['id']
                    ];
                }
            } catch (\Exception $e) {
                // Skip if attendance data is not available
            }
        }

        // Sort activities by timestamp (most recent first)
        usort($recentActivities, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return [
            'scheduled_classes' => count($todaySchedules),
            'schedules' => array_slice($todaySchedules, 0, 5), // Limit to 5 most recent
            'activities' => array_slice($recentActivities, 0, 10), // Limit to 10 most recent
            'total_classes' => count($classes),
            'total_students' => collect($classes)->sum('student_count')
        ];
    }

    /**
     * Check if a schedule is for today
     */
    private function isScheduleForToday($schedule, Carbon $today): bool
    {
        // This is a simplified check - in real implementation, you'd have proper schedule data
        $todayName = $today->format('l'); // Full day name (e.g., 'Monday')
        $todayShort = substr($todayName, 0, 3); // Short day name (e.g., 'Mon')

        if (isset($schedule['day_of_week'])) {
            return $schedule['day_of_week'] === $todayName || $schedule['day_of_week'] === $todayShort;
        }

        // Fallback: assume some classes are scheduled for today
        return rand(0, 1) === 1;
    }

    /**
     * Get the current status of a schedule
     */
    private function getScheduleStatus($schedule, Carbon $now): string
    {
        if (!isset($schedule['start_time']) || !isset($schedule['end_time'])) {
            return 'upcoming';
        }

        try {
            $startTime = Carbon::createFromFormat('H:i', $schedule['start_time']);
            $endTime = Carbon::createFromFormat('H:i', $schedule['end_time']);

            $currentTime = $now->format('H:i');
            $current = Carbon::createFromFormat('H:i', $currentTime);

            if ($current->between($startTime, $endTime)) {
                return 'ongoing';
            } elseif ($current->greaterThan($endTime)) {
                return 'completed';
            } else {
                return 'upcoming';
            }
        } catch (\Exception $e) {
            return 'upcoming';
        }
    }

    /**
     * Get class-specific statistics
     */
    private function getClassStats(Faculty $faculty, Classes $class): array
    {
        $enrollments = $class->ClassStudents;
        $totalStudents = $enrollments->count();

        // Calculate attendance rate from actual attendance records
        $attendanceStats = $this->attendanceService->calculateClassStats($class->id);
        $attendanceRate = $attendanceStats['attendance_rate'] ?? 0;

        // Calculate percentage-based grade distribution (no letter grades)
        $gradeDistribution = [
            '90-100' => $enrollments->whereBetween('total_average', [90, 100])->count(),
            '80-89' => $enrollments->whereBetween('total_average', [80, 89.99])->count(),
            '75-79' => $enrollments->whereBetween('total_average', [75, 79.99])->count(),
            'Below 75' => $enrollments->where('total_average', '<', 75)->count(),
        ];

        // Calculate average grade (percentage)
        $averageGrade = $enrollments->whereNotNull('total_average')->avg('total_average') ?? 0;

        // Passing rate based on >= 75%
        $passingCount = $enrollments->where('total_average', '>=', 75)->count();
        $passingRate = $totalStudents > 0 ? round(($passingCount / $totalStudents) * 100, 1) : 0;

        return [
            'total_students' => $totalStudents,
            'attendance_rate' => round($attendanceRate, 1),
            'average_grade' => round($averageGrade, 2),
            'grade_distribution' => $gradeDistribution,
            'passing_rate' => $passingRate,
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

    /**
     * Get students for a class (faculty only)
     */
    public function students(Request $request, Classes $class): JsonResponse
    {
        $user = $request->user();
        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can access this.');
        }
        /** @var Faculty $faculty */
        $faculty = $user->faculty;
        if ($class->faculty_id !== $faculty->id) {
            abort(403, 'You can only access your own classes.');
        }

        $enrollments = $class->ClassStudents()->with('student')->get();
        $data = $enrollments->map(function ($enrollment) {
            $s = $enrollment->student;
            return [
                'id' => $s->id,
                'student_id' => $s->student_id ?? $s->id,
                'name' => $s->full_name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                'email' => $s->email,
                'status' => $s->status ?? ($enrollment->status ? 'active' : 'inactive'),
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Update class information (faculty only)
     */
    public function update(Request $request, Classes $class): JsonResponse
    {
        $user = $request->user();
        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can update classes.');
        }
        /** @var Faculty $faculty */
        $faculty = $user->faculty;
        if ($class->faculty_id !== $faculty->id) {
            abort(403, 'You can only update your own classes.');
        }

        $validated = $request->validate([
            'subject_code' => 'nullable|string|max:50',
            'subject_title' => 'nullable|string|max:255',
            'section' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:100',
            'units' => 'nullable|integer|min:1|max:10',
            'schedule' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // Persist simple fields available on Classes model
        $class->subject_code = $validated['subject_code'] ?? $class->subject_code;
        $class->section = $validated['section'] ?? $class->section;
        // Note: mapping room (string) to room_id and schedule editing can be added later.
        $class->save();

        return response()->json(['success' => true, 'message' => 'Class updated.']);
    }

    /**
     * Reset all attendance data for a class
     */
    public function resetAttendance(Request $request, Classes $class): JsonResponse
    {
        $user = $request->user();

        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can reset attendance.');
        }

        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        if ($class->faculty_id !== $faculty->id) {
            abort(403, 'You can only reset attendance for your own classes.');
        }

        try {
            $result = $this->attendanceManagementService->resetClassAttendance($class->id, $faculty->id);

            return response()->json([
                'success' => true,
                'message' => 'Attendance data has been reset successfully.',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            logger()->error('Failed to reset attendance data', [
                'class_id' => $class->id,
                'faculty_id' => $faculty->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset attendance data. Please try again.',
            ], 500);
        }
    }


    /**
     * Update a single student's grades for a class (percentage-based)
     */
    public function updateGrades(Request $request, Classes $class): JsonResponse
    {
        $user = $request->user();
        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can update grades.');
        }
        /** @var Faculty $faculty */
        $faculty = $user->faculty;
        if ($class->faculty_id !== $faculty->id) {
            abort(403, 'You can only update grades for your own classes.');
        }

        $validated = $request->validate([
            'student_id' => 'required',
            'prelim_grade' => 'nullable|numeric|min:0|max:100',
            'midterm_grade' => 'nullable|numeric|min:0|max:100',
            'finals_grade' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $enrollment = \App\Models\class_enrollments::where('class_id', $class->id)
                ->where('student_id', $validated['student_id'])
                ->firstOrFail();

            foreach (['prelim_grade', 'midterm_grade', 'finals_grade'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $enrollment->{$field} = $validated[$field];
                }
            }

            $components = array_filter([
                $enrollment->prelim_grade,
                $enrollment->midterm_grade,
                $enrollment->finals_grade,
            ], fn($v) => $v !== null);
            $enrollment->total_average = count($components) > 0 ? round(array_sum($components) / count($components), 2) : null;

            $enrollment->save();

            return response()->json([
                'success' => true,
                'message' => 'Grades updated successfully.',
                'data' => [
                    'student_id' => (string) $enrollment->student_id,
                    'prelim_grade' => $enrollment->prelim_grade,
                    'midterm_grade' => $enrollment->midterm_grade,
                    'finals_grade' => $enrollment->finals_grade,
                    'total_average' => $enrollment->total_average,
                ],
            ]);
        } catch (\Throwable $e) {
            logger()->error('Failed to update grades', [
                'class_id' => $class->id,
                'student_id' => $validated['student_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update grades.',
            ], 500);
        }
    }

    /**
     * Bulk update student grades for a class
     */
    public function bulkUpdateGrades(Request $request, Classes $class): JsonResponse
    {
        $user = $request->user();
        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can update grades.');
        }
        /** @var Faculty $faculty */
        $faculty = $user->faculty;
        if ($class->faculty_id !== $faculty->id) {
            abort(403, 'You can only update grades for your own classes.');
        }

        $validated = $request->validate([
            'grades_data' => 'required|array',
            'grades_data.*.student_id' => 'required',
            'grades_data.*.prelim_grade' => 'nullable|numeric|min:0|max:100',
            'grades_data.*.midterm_grade' => 'nullable|numeric|min:0|max:100',
            'grades_data.*.finals_grade' => 'nullable|numeric|min:0|max:100',
        ]);

        $updated = 0;
        $errors = [];

        \DB::transaction(function () use ($class, $validated, &$updated, &$errors) {
            foreach ($validated['grades_data'] as $row) {
                try {
                    $enrollment = \App\Models\class_enrollments::where('class_id', $class->id)
                        ->where('student_id', $row['student_id'])
                        ->first();

                    if (!$enrollment) {
                        $errors[] = [
                            'student_id' => $row['student_id'],
                            'error' => 'Enrollment not found',
                        ];
                        continue;
                    }

                    foreach (['prelim_grade', 'midterm_grade', 'finals_grade'] as $field) {
                        if (array_key_exists($field, $row)) {
                            $enrollment->{$field} = $row[$field];
                        }
                    }

                    $components = array_filter([
                        $enrollment->prelim_grade,
                        $enrollment->midterm_grade,
                        $enrollment->finals_grade,
                    ], fn($v) => $v !== null);
                    $enrollment->total_average = count($components) > 0 ? round(array_sum($components) / count($components), 2) : null;

                    $enrollment->save();
                    $updated++;
                } catch (\Throwable $e) {
                    $errors[] = [
                        'student_id' => $row['student_id'] ?? null,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Updated grades for {$updated} students." . (count($errors) ? ' Some records had errors.' : ''),
            'data' => [
                'updated_count' => $updated,
                'errors' => $errors,
            ],
        ]);
    }

    /**
     * Import grades from CSV. Expected columns: student_id, prelim|prelim_grade, midterm|midterm_grade, finals|finals_grade
     */
    public function importGrades(Request $request, Classes $class): JsonResponse
    {
        $user = $request->user();
        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can import grades.');
        }
        /** @var Faculty $faculty */
        $faculty = $user->faculty;
        if ($class->faculty_id !== $faculty->id) {
            abort(403, 'You can only import grades for your own classes.');
        }

        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return response()->json(['success' => false, 'message' => 'Unable to read uploaded file.'], 400);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            return response()->json(['success' => false, 'message' => 'CSV is empty.'], 400);
        }

        $index = [];
        foreach ($header as $i => $col) {
            $key = strtolower(trim((string) $col));
            $index[$key] = $i;
        }

        if (!array_key_exists('student_id', $index)) {
            return response()->json(['success' => false, 'message' => 'Missing required column: student_id'], 400);
        }

        $updated = 0;
        $errors = [];

        \DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $studentId = $row[$index['student_id']] ?? null;
                if (!$studentId) { continue; }

                $prelim = null; $midterm = null; $finals = null;
                foreach (['prelim', 'prelim_grade'] as $k) { if (isset($index[$k])) { $prelim = $row[$index[$k]]; break; } }
                foreach (['midterm', 'midterm_grade'] as $k) { if (isset($index[$k])) { $midterm = $row[$index[$k]]; break; } }
                foreach (['finals', 'finals_grade'] as $k) { if (isset($index[$k])) { $finals = $row[$index[$k]]; break; } }

                try {
                    $enrollment = \App\Models\class_enrollments::where('class_id', $class->id)
                        ->where('student_id', $studentId)
                        ->first();

                    if (!$enrollment) {
                        $errors[] = [
                            'student_id' => $studentId,
                            'error' => 'Enrollment not found',
                        ];
                        continue;
                    }

                    if ($prelim !== null && $prelim !== '') { $enrollment->prelim_grade = max(0, min(100, (float) $prelim)); }
                    if ($midterm !== null && $midterm !== '') { $enrollment->midterm_grade = max(0, min(100, (float) $midterm)); }
                    if ($finals !== null && $finals !== '') { $enrollment->finals_grade = max(0, min(100, (float) $finals)); }

                    $components = array_filter([
                        $enrollment->prelim_grade,
                        $enrollment->midterm_grade,
                        $enrollment->finals_grade,
                    ], fn($v) => $v !== null);
                    $enrollment->total_average = count($components) > 0 ? round(array_sum($components) / count($components), 2) : null;

                    $enrollment->save();
                    $updated++;
                } catch (\Throwable $e) {
                    $errors[] = [
                        'student_id' => $studentId,
                        'error' => $e->getMessage(),
                    ];
                }
            }
            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();
            fclose($handle);
            return response()->json([
                'success' => false,
                'message' => 'Failed to import grades.',
                'error' => $e->getMessage(),
            ], 500);
        }
        fclose($handle);

        return response()->json([
            'success' => true,
            'message' => "Imported grades for {$updated} students." . (count($errors) ? ' Some rows had errors.' : ''),
            'data' => [
                'updated_count' => $updated,
                'errors' => $errors,
            ],
        ]);
    }

    /**
     * Export grades to CSV (Student ID, Student Name, Prelim, Midterm, Finals, Total Average)
     */
    public function exportGrades(Request $request, Classes $class)
    {
        $user = $request->user();
        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can export grades.');
        }
        /** @var Faculty $faculty */
        $faculty = $user->faculty;
        if ($class->faculty_id !== $faculty->id) {
            abort(403, 'You can only export grades for your own classes.');
        }

        $enrollments = \App\Models\class_enrollments::where('class_id', $class->id)
            ->with(['student', 'ShsStudent'])
            ->get();

        $filename = 'grades_class_' . $class->id . '_' . now()->format('Ymd_His') . '.csv';

        $callback = function () use ($enrollments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Student ID', 'Student Name', 'Prelim', 'Midterm', 'Finals', 'Total Average']);
            foreach ($enrollments as $e) {
                $id = $e->ShsStudent?->student_lrn ?? $e->student?->id ?? $e->student_id;
                $name = $e->ShsStudent?->fullname ?? trim(($e->student->first_name ?? '') . ' ' . ($e->student->last_name ?? ''));
                fputcsv($handle, [
                    $id,
                    $name,
                    $e->prelim_grade,
                    $e->midterm_grade,
                    $e->finals_grade,
                    $e->total_average,
                ]);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

}
