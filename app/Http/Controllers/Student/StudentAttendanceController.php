<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\StudentAttendanceService;
use App\Services\AttendanceService;
use App\Models\Classes;
use App\Models\Students;
use App\Models\ShsStudents;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Student Attendance Controller
 * 
 * Handles all student attendance viewing and tracking operations
 */
final class StudentAttendanceController extends Controller
{
    public function __construct(
        private readonly StudentAttendanceService $studentAttendanceService,
        private readonly AttendanceService $attendanceService
    ) {}

    /**
     * Display the main attendance dashboard for students
     */
    public function index(): Response|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isStudent()) {
            abort(403, 'Only students can access attendance records.');
        }

        // Get the student record
        $student = $user->student ?? $user->shsStudent;
        if (!$student) {
            abort(404, 'Student record not found.');
        }

        // Get the correct student identifier based on student type
        if ($student instanceof \App\Models\Students) {
            // College student - use the id as student_id for attendance records
            $studentId = (string) $student->id;
        } else {
            // SHS student - use student_lrn
            $studentId = $student->student_lrn;
        }

        try {
            // Get comprehensive dashboard data
            $dashboardData = $this->studentAttendanceService->getStudentDashboardData($studentId);

            return Inertia::render('Student/Attendance/Index', [
                'student' => [
                    'id' => $studentId,
                    'name' => $student->fullname ?? ($student->first_name . ' ' . $student->last_name),
                    'email' => $student->email,
                    'type' => $user->person_type === \App\Models\ShsStudents::class ? 'shs' : 'college',
                ],
                'classes' => $dashboardData['classes'],
                'overall_stats' => $dashboardData['overall_stats'],
                'attendance_trend' => $dashboardData['attendance_trend'],
                'upcoming_classes' => $dashboardData['upcoming_classes'],
                'attendance_alerts' => $dashboardData['attendance_alerts'],
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            logger()->error('Student attendance dashboard error', [
                'user_id' => $user->id,
                'student_id' => $studentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Unable to load attendance dashboard. Please try again later.');
        }
    }

    /**
     * Show detailed attendance for a specific class
     */
    public function showClass(Request $request, Classes $class): Response|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $student = $user->student ?? $user->shsStudent;

        // Get the correct student identifier based on student type
        if ($student instanceof \App\Models\Students) {
            // College student - use the id as student_id for attendance records
            $studentId = (string) $student->id;
        } else {
            // SHS student - use student_lrn
            $studentId = $student->student_lrn;
        }

        // Verify student is enrolled in this class
        $enrollment = \App\Models\class_enrollments::where('student_id', $studentId)
            ->where('class_id', $class->id)
            ->first();

        if (!$enrollment) {
            abort(403, 'You are not enrolled in this class.');
        }

        try {
            // Get detailed class attendance data
            $classData = $this->studentAttendanceService->getClassAttendanceDetails($studentId, $class->id);

            return Inertia::render('Student/Attendance/ClassDetails', [
                'student' => [
                    'id' => $studentId,
                    'name' => $student->fullname ?? ($student->first_name . ' ' . $student->last_name),
                ],
                'class' => $classData['class'],
                'enrollment' => $classData['enrollment'],
                'stats' => $classData['stats'],
                'monthly_data' => $classData['monthly_data'],
                'recent_sessions' => $classData['recent_sessions'],
                'attendance_pattern' => $classData['attendance_pattern'],
                'attendances' => $classData['attendances']->take(50), // Limit for performance
            ]);
        } catch (\Exception $e) {
            logger()->error('Student class attendance error', [
                'user_id' => $user->id,
                'student_id' => $studentId,
                'class_id' => $class->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to load class attendance details. Please try again later.');
        }
    }

    /**
     * Show attendance statistics page
     */
    public function statistics(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $student = $user->student ?? $user->shsStudent;

        // Get the correct student identifier based on student type
        if ($student instanceof \App\Models\Students) {
            // College student - use the id as student_id for attendance records
            $studentId = (string) $student->id;
        } else {
            // SHS student - use student_lrn
            $studentId = $student->student_lrn;
        }

        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : now()->subMonths(3);
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : now();

        try {
            // Get overall statistics
            $overallStats = $this->attendanceService->calculateStudentOverallStats($studentId);
            
            // Get attendance by class
            $enrollments = \App\Models\class_enrollments::with(['class.Subject', 'class.ShsSubject'])
                ->where('student_id', $studentId)
                ->get();

            $classeStats = [];
            foreach ($enrollments as $enrollment) {
                $classStats = $this->attendanceService->calculateStudentClassStats($studentId, $enrollment->class_id);
                $classeStats[] = [
                    'class' => $enrollment->class,
                    'stats' => $classStats,
                ];
            }

            // Get attendance trends
            $trends = $this->attendanceService->getAttendanceTrends(
                null, // All classes for student
                $startDate,
                $endDate,
                'week'
            );

            return Inertia::render('Student/Attendance/Statistics', [
                'student' => [
                    'id' => $studentId,
                    'name' => $student->fullname ?? ($student->first_name . ' ' . $student->last_name),
                ],
                'overall_stats' => $overallStats,
                'classes_stats' => $classeStats,
                'trends' => $trends,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            logger()->error('Student attendance statistics error', [
                'user_id' => $user->id,
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to load attendance statistics. Please try again later.');
        }
    }

    /**
     * Show attendance history page
     */
    public function history(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $student = $user->student ?? $user->shsStudent;

        // Get the correct student identifier based on student type
        if ($student instanceof \App\Models\Students) {
            // College student - use the id as student_id for attendance records
            $studentId = (string) $student->id;
        } else {
            // SHS student - use student_lrn
            $studentId = $student->student_lrn;
        }

        $classId = $request->get('class_id') ? (int) $request->get('class_id') : null;
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : null;
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : null;

        try {
            // Get attendance history
            $historyData = $this->studentAttendanceService->getStudentAttendanceHistory(
                $studentId,
                $classId,
                $startDate,
                $endDate
            );

            // Get available classes for filtering
            $availableClasses = \App\Models\class_enrollments::with(['class.Subject', 'class.ShsSubject'])
                ->where('student_id', $studentId)
                ->get()
                ->map(function ($enrollment) {
                    return [
                        'id' => $enrollment->class_id,
                        'name' => $enrollment->class->subject_code,
                        'title' => $enrollment->class->Subject?->title ?? $enrollment->class->ShsSubject?->title,
                    ];
                });

            return Inertia::render('Student/Attendance/History', [
                'student' => [
                    'id' => $studentId,
                    'name' => $student->fullname ?? ($student->first_name . ' ' . $student->last_name),
                ],
                'history' => $historyData['history'],
                'total_records' => $historyData['total_records'],
                'overall_stats' => $historyData['overall_stats'],
                'available_classes' => $availableClasses,
                'filters' => [
                    'class_id' => $classId,
                    'start_date' => $startDate?->format('Y-m-d'),
                    'end_date' => $endDate?->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            logger()->error('Student attendance history error', [
                'user_id' => $user->id,
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to load attendance history. Please try again later.');
        }
    }

    /**
     * Export student attendance data
     */
    public function export(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $student = $user->student ?? $user->shsStudent;

        // Get the correct student identifier based on student type
        if ($student instanceof \App\Models\Students) {
            // College student - use the id as student_id for attendance records
            $studentId = (string) $student->id;
        } else {
            // SHS student - use student_lrn
            $studentId = $student->student_lrn;
        }

        $validator = Validator::make($request->all(), [
            'class_id' => 'nullable|integer|exists:classes,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'in:csv,excel,pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $classId = $request->get('class_id') ? (int) $request->get('class_id') : null;
            $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : null;
            $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : null;
            $format = $request->get('format', 'csv');

            // Verify student is enrolled in the class if specified
            if ($classId) {
                $enrollment = \App\Models\class_enrollments::where('student_id', $studentId)
                    ->where('class_id', $classId)
                    ->first();

                if (!$enrollment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not enrolled in the specified class.',
                    ], 403);
                }
            }

            $exportData = $this->studentAttendanceService->exportStudentAttendance(
                $studentId,
                $classId,
                $startDate,
                $endDate,
                $format
            );

            return response()->json([
                'success' => true,
                'message' => 'Export prepared successfully',
                'download_url' => route('student.attendance.export', [
                    'class_id' => $classId,
                    'start_date' => $startDate?->format('Y-m-d'),
                    'end_date' => $endDate?->format('Y-m-d'),
                    'format' => $format,
                ]),
                'export_summary' => [
                    'total_records' => count($exportData['data']),
                    'filename' => $exportData['filename'],
                    'stats' => $exportData['stats'],
                ],
            ]);
        } catch (\Exception $e) {
            logger()->error('Student attendance export error', [
                'user_id' => $user->id,
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to prepare export. Please try again.',
            ], 500);
        }
    }

    /**
     * Get attendance data for API/AJAX requests
     */
    public function getAttendanceData(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $student = $user->student ?? $user->shsStudent;

        // Get the correct student identifier based on student type
        if ($student instanceof \App\Models\Students) {
            // College student - use the id as student_id for attendance records
            $studentId = (string) $student->id;
        } else {
            // SHS student - use student_lrn
            $studentId = $student->student_lrn;
        }

        $validator = Validator::make($request->all(), [
            'class_id' => 'nullable|integer|exists:classes,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $classId = $request->get('class_id') ? (int) $request->get('class_id') : null;
            $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : null;
            $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : null;
            $limit = $request->get('limit', 20);

            $attendances = $this->attendanceService->getStudentAttendance(
                $studentId,
                $classId,
                $startDate,
                $endDate
            )->take($limit);

            $stats = $this->attendanceService->calculateAttendanceStats($attendances);

            return response()->json([
                'success' => true,
                'data' => $attendances,
                'stats' => $stats,
                'total' => $attendances->count(),
            ]);
        } catch (\Exception $e) {
            logger()->error('Get attendance data error', [
                'user_id' => $user->id,
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load attendance data.',
            ], 500);
        }
    }
}
