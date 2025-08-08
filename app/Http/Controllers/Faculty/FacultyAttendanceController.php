<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Services\Faculty\FacultyAttendanceService;
use App\Services\AttendanceService;
use App\Models\Classes;
use App\Models\Faculty;
use App\Models\User;
use App\Enums\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Faculty Attendance Controller
 * 
 * Handles all faculty attendance management operations
 */
final class FacultyAttendanceController extends Controller
{
    public function __construct(
        private readonly FacultyAttendanceService $facultyAttendanceService,
        private readonly AttendanceService $attendanceService
    ) {}

    /**
     * Display the main attendance dashboard for faculty
     */
    public function index(): Response|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can access attendance management.');
        }

        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        if (!$faculty) {
            abort(404, 'Faculty record not found.');
        }

        try {
            // Get faculty classes with attendance summary
            $classesData = $this->facultyAttendanceService->getFacultyClassesWithAttendance($faculty->id);
            
            // Get dashboard summary
            $dashboardSummary = $this->facultyAttendanceService->getFacultyDashboardSummary($faculty->id);

            return Inertia::render('Faculty/Attendance/Index', [
                'faculty' => [
                    'id' => $faculty->id,
                    'name' => $faculty->first_name . ' ' . $faculty->last_name,
                    'email' => $faculty->email,
                ],
                'classes' => $classesData,
                'summary' => $dashboardSummary,
                'attendance_statuses' => AttendanceStatus::options(),
            ]);
        } catch (\Exception $e) {
            logger()->error('Faculty attendance dashboard error', [
                'faculty_id' => $faculty->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Unable to load attendance dashboard. Please try again later.');
        }
    }

    /**
     * Show attendance management for a specific class
     */
    public function showClass(Request $request, Classes $class): Response|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        // Verify faculty owns this class
        if ($class->faculty_id !== $faculty->id) {
            abort(403, 'You can only manage attendance for your own classes.');
        }

        $date = $request->get('date') ? Carbon::parse($request->get('date')) : now();

        try {
            // Get class roster with attendance data
            $rosterData = $this->facultyAttendanceService->getClassRosterWithAttendance(
                $class->id,
                $date
            );

            // Get recent attendance sessions
            $recentSessions = $this->facultyAttendanceService->getClassAttendanceSessions($class->id, 10);

            return Inertia::render('Faculty/Attendance/ClassAttendance', [
                'class' => $rosterData['class'],
                'roster' => $rosterData['roster'],
                'session_stats' => $rosterData['session_stats'],
                'selected_date' => $date->format('Y-m-d'),
                'recent_sessions' => $recentSessions,
                'attendance_statuses' => AttendanceStatus::options(),
                'faculty' => [
                    'id' => $faculty->id,
                    'name' => $faculty->first_name . ' ' . $faculty->last_name,
                ],
            ]);
        } catch (\Exception $e) {
            logger()->error('Faculty class attendance error', [
                'faculty_id' => $faculty->id,
                'class_id' => $class->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to load class attendance. Please try again later.');
        }
    }

    /**
     * Mark attendance for a class session
     */
    public function markAttendance(Request $request, Classes $class): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        // Verify faculty owns this class
        if ($class->faculty_id !== $faculty->id) {
            abort(403, 'You can only mark attendance for your own classes.');
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|string',
            'attendance.*.status' => 'required|in:present,absent,late,excused,partial',
            'attendance.*.remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $date = Carbon::parse($request->input('date'));
            $attendanceData = $request->input('attendance');

            $results = $this->facultyAttendanceService->markClassSessionAttendance(
                $class->id,
                $date,
                $attendanceData,
                $faculty->id
            );

            if (!empty($results['errors'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some attendance records could not be saved',
                    'errors' => $results['errors'],
                    'marked' => count($results['marked']),
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance marked successfully',
                'marked' => count($results['marked']),
                'session_stats' => $this->facultyAttendanceService->getSessionStats($class->id, $date),
            ]);
        } catch (\Exception $e) {
            logger()->error('Mark attendance error', [
                'faculty_id' => $faculty->id,
                'class_id' => $class->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark attendance. Please try again.',
            ], 500);
        }
    }

    /**
     * Update a specific attendance record
     */
    public function updateAttendance(Request $request, int $attendanceId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:present,absent,late,excused,partial',
            'remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            /** @var User $user */
            $user = Auth::user();
            /** @var Faculty $faculty */
            $faculty = $user->faculty;

            $attendance = \App\Models\Attendance::findOrFail($attendanceId);
            
            // Verify faculty owns the class
            if ($attendance->class->faculty_id !== $faculty->id) {
                abort(403, 'You can only update attendance for your own classes.');
            }

            $attendance->update([
                'status' => $request->input('status'),
                'remarks' => $request->input('remarks'),
                'marked_at' => now(),
                'marked_by' => $faculty->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully',
                'attendance' => $attendance->fresh(),
            ]);
        } catch (\Exception $e) {
            logger()->error('Update attendance error', [
                'attendance_id' => $attendanceId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update attendance. Please try again.',
            ], 500);
        }
    }

    /**
     * Export attendance data for a class
     */
    public function exportAttendance(Request $request, Classes $class): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        // Verify faculty owns this class
        if ($class->faculty_id !== $faculty->id) {
            abort(403, 'You can only export attendance for your own classes.');
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
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
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
            $format = $request->input('format', 'csv');

            $report = $this->attendanceService->generateClassReport(
                $class->id,
                $startDate,
                $endDate
            );

            // Here you would implement the actual export logic
            // For now, return the data structure
            return response()->json([
                'success' => true,
                'message' => 'Export prepared successfully',
                'download_url' => route('faculty.attendance.class.export', [
                    'class' => $class->id,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'format' => $format,
                ]),
                'report_summary' => [
                    'total_students' => count($report['students']),
                    'date_range' => [
                        'start' => $startDate->format('M j, Y'),
                        'end' => $endDate->format('M j, Y'),
                    ],
                    'overall_stats' => $report['summary'],
                ],
            ]);
        } catch (\Exception $e) {
            logger()->error('Export attendance error', [
                'faculty_id' => $faculty->id,
                'class_id' => $class->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to prepare export. Please try again.',
            ], 500);
        }
    }

    /**
     * Show attendance reports page
     */
    public function reports(): Response
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        $classesData = $this->facultyAttendanceService->getFacultyClassesWithAttendance($faculty->id);
        $dashboardSummary = $this->facultyAttendanceService->getFacultyDashboardSummary($faculty->id);

        return Inertia::render('Faculty/Attendance/Reports', [
            'faculty' => [
                'id' => $faculty->id,
                'name' => $faculty->first_name . ' ' . $faculty->last_name,
            ],
            'classes' => $classesData,
            'summary' => $dashboardSummary,
        ]);
    }

    /**
     * Show attendance analytics page
     */
    public function analytics(): Response
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        $classesData = $this->facultyAttendanceService->getFacultyClassesWithAttendance($faculty->id);
        $dashboardSummary = $this->facultyAttendanceService->getFacultyDashboardSummary($faculty->id);

        return Inertia::render('Faculty/Attendance/Analytics', [
            'faculty' => [
                'id' => $faculty->id,
                'name' => $faculty->first_name . ' ' . $faculty->last_name,
            ],
            'classes' => $classesData,
            'summary' => $dashboardSummary,
        ]);
    }
}
