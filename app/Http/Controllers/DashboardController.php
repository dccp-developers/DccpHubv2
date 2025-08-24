<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Students;
use App\Models\GeneralSettings;
use App\Models\SubjectEnrolled;
use App\Services\GeneralSettingsService;
use App\Services\Student\StudentAttendanceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

final class DashboardController extends Controller
{
    // Define constant for placeholder text
    private const COMING_SOON = 'Coming Soon';

    public function __construct(
        private readonly GeneralSettingsService $settingsService,
        private readonly StudentAttendanceService $studentAttendanceService
    ) {}

    /**
     * Main dashboard action
     */
    public function __invoke(): Response|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Load user relationships if needed
        // Note: Team functionality has been removed

        // Redirect guest users to the guest dashboard
        if (isset($user->role) && $user->role === 'guest') {
            return redirect()->route('enrolee.dashboard');
        }

        // Redirect faculty users to the faculty dashboard
        if ($user->isFaculty()) {
            return redirect()->route('faculty.dashboard');
        }

        // Allow students and admins to access the dashboard
        if (!$user->isStudent() && !$user->isAdmin()) {
            abort(403, 'Only students and admins can access the dashboard.');
        }

        // Handle admin users with a simplified dashboard
        if ($user->isAdmin()) {
            return $this->getAdminDashboard($user);
        }

        /** @var Students $student */
        $student = $user->student;

        // Get general settings
        $generalSettings = GeneralSettings::first();

        // Get current semester and school year using the service
        $currentSemester = $this->settingsService->getCurrentSemester();
        $currentSchoolYear = $this->settingsService->getCurrentSchoolYearString();

        // Get enrollments and calculate stats
        $enrollmentData = $this->getEnrollmentData($student, $currentSemester, $currentSchoolYear);
        $enrollments = $enrollmentData['enrollments'];
        $classEnrollments = $enrollmentData['classEnrollments'];

        // Calculate stats for the dashboard
        $statsData = $this->calculateStats($student, $enrollments, $classEnrollments);

        // Prepare student data
        $studentData = [
            'name' => $student->full_name,
            'grade' => $student->academic_year,
            'avatarUrl' => $student->profile_url,
            'streak' => 5, // Example value
        ];

        // Get formatted today's classes using the helper method
        $todaysClassesData = $student->getFormattedTodaysClasses();

        // Add color to each class
        foreach ($todaysClassesData as &$classData) {
            $classData['color'] = $this->generateColorForSubject($classData['subject_code']);
        }

        // Get current class data
        $currentClassData = $student->getFormattedCurrentClass();

        // Get recent grades
        $recentGradesData = $this->getRecentGrades($enrollments);

        // Get weekly schedule data
        $weeklyScheduleData = $this->getWeeklySchedule($student);

        // Get course information
        $courseInfo = $this->getCourseInfo($student);

        // Fetch the current student enrollment for this academic period using the settings service
        $studentEnrollment = \App\Models\StudentEnrollment::query()
            ->where('student_id', $student->id)
            ->where('semester', $currentSemester)
            ->where('school_year', $currentSchoolYear)
            ->latest('created_at')
            ->withTrashed()
            ->first();

        // Get attendance data for dashboard widget
        $attendanceData = $this->getAttendanceData($student);

        return Inertia::render('Dashboard', [
            'student' => $studentData,
            'stats' => $statsData,
            'todaysClasses' => $todaysClassesData,
            'currentClass' => $currentClassData,
            'recentGrades' => $recentGradesData,
            'weeklySchedule' => $weeklyScheduleData,
            'assignments' => self::COMING_SOON, // Placeholder
            'exams' => self::COMING_SOON,       // Placeholder
            'announcements' => self::COMING_SOON, // Placeholder
            'resources' => self::COMING_SOON,
            'user' => $user,
            'courseInfo' => $courseInfo,
            'semester' => $currentSemester,
            'schoolYear' => $currentSchoolYear,
            'generalSettings' => $generalSettings,
            'studentEnrollment' => $studentEnrollment,
            'attendanceData' => $attendanceData,
        ]);
    }



    /**
     * Get enrollment data for the student
     */
    private function getEnrollmentData(Students $student, int $currentSemester, string $currentSchoolYear): array
    {
        // Get subject enrollments
        $enrollments = SubjectEnrolled::query()
            ->where('student_id', $student->id)
            ->whereHas('class', function ($query) use ($currentSemester): void {
                $query->where('semester', $currentSemester);
            })
            ->where('school_year', $currentSchoolYear)
            ->with([
                'subject.classes.schedules.room',
                'subject.classes.Faculty',
                'class'
            ]) // Eager load related data including schedules
            ->get();

        // Get class enrollments with attendance data
        $classEnrollments = $student->classEnrollments()
            ->whereHas('class', function ($query) use ($currentSemester): void {
                $query->where('semester', $currentSemester);
            })
            ->with('Attendances') // Eager load the Attendances relationship
            ->get();

        return [
            'enrollments' => $enrollments,
            'classEnrollments' => $classEnrollments,
        ];
    }

    /**
     * Calculate stats for the dashboard
     */
    private function calculateStats(Students $student, $enrollments, $classEnrollments): array
    {
        // Calculate GPA and units
        $gpa = SubjectEnrolled::calculateOverallGPA($student->id);
        $totalUnits = $enrollments->sum(fn (SubjectEnrolled $enrollment) => $enrollment->subject->units);

        // Calculate attendance percentage
        $attendanceRecords = 0;
        $attendancePresent = 0;

        foreach ($classEnrollments as $enrollment) {
            $attendances = $enrollment->Attendances;
            $attendanceRecords += $attendances->count();
            $attendancePresent += $attendances->where('status', 'present')->count();
        }

        $attendancePercentage = $attendanceRecords > 0
            ? round(($attendancePresent / $attendanceRecords) * 100)
            : 100; // Default to 100% if no records

        return [
            ['label' => 'GPA', 'value' => $gpa !== null ? number_format($gpa, 2) : 'N/A', 'description' => 'Overall Grade Point Average'],
            ['label' => 'Attendance', 'value' => $attendancePercentage.'%', 'description' => 'Your attendance rate this semester'],
            ['label' => 'Enrolled Units', 'value' => $totalUnits, 'description' => 'Total units this semester'],
            ['label' => 'Enrolled Classes', 'value' => $classEnrollments->count(), 'description' => 'Number of classes this semester'],
        ];
    }

    /**
     * Get recent grades for the student
     */
    private function getRecentGrades($enrollments): array
    {
        $recentGrades = $enrollments->sortByDesc('created_at')->take(3);

        return $recentGrades->map(function (SubjectEnrolled $enrollment): array {
            // Calculate letter grade based on numeric grade
            $letterGrade = 'N/A';
            if ($enrollment->grade !== null) {
                if ($enrollment->grade >= 90) {
                    $letterGrade = 'A';
                } elseif ($enrollment->grade >= 80) {
                    $letterGrade = 'B';
                } elseif ($enrollment->grade >= 70) {
                    $letterGrade = 'C';
                } elseif ($enrollment->grade >= 60) {
                    $letterGrade = 'D';
                } else {
                    $letterGrade = 'F';
                }
            }

            return [
                'id' => $enrollment->id,
                'subject' => $enrollment->subject->title,
                'subject_code' => $enrollment->subject->code,
                'assignment' => $enrollment->subject->title, // Using subject title as placeholder
                'grade' => $letterGrade,
                'numeric_grade' => $enrollment->grade,
                'score' => $enrollment->grade ? $enrollment->grade . '/100' : 'N/A',
                'date' => $enrollment->updated_at->format('M d, Y'),
                'status' => $this->getGradeStatus($enrollment->grade),
            ];
        })->toArray();
    }

    /**
     * Get course information for the student
     */
    private function getCourseInfo(Students $student): ?array
    {
        return $student->course ? [
            'code' => $student->course->code,
            'title' => $student->course->title,
            'department' => $student->course->department,
        ] : null;
    }

    /**
     * Generate a consistent color for a subject based on its code
     */
    private function generateColorForSubject(string $subjectCode): string
    {
        // Create a hash of the subject code
        $hash = crc32($subjectCode);

        // Define a set of pleasant colors
        $colors = [
            'blue-500', 'green-500', 'purple-500', 'amber-500', 'rose-500',
            'indigo-500', 'emerald-500', 'violet-500', 'orange-500', 'pink-500',
            'cyan-500', 'teal-500', 'fuchsia-500', 'yellow-500', 'red-500',
        ];

        // Use the hash to select a color
        $colorIndex = abs($hash) % count($colors);

        return $colors[$colorIndex];
    }

    /**
     * Determine the grade status based on the numeric grade
     */
    private function getGradeStatus(?int $grade): string
    {
        if ($grade === null) {
            return 'Pending';
        }

        return $grade >= 75 ? 'Passing' : 'Failing';
    }

    /**
     * Get weekly schedule for the student (using same approach as ScheduleController)
     */
    private function getWeeklySchedule(Students $student): array
    {
        // Get current semester and school year
        $currentSemester = $this->settingsService->getCurrentSemester();
        $currentSchoolYear = $this->settingsService->getCurrentSchoolYearString();

        // Get the student's classes for the current semester and academic year (same as ScheduleController)
        $classes = \App\Models\Classes::query()
            ->whereHas('ClassStudents', function ($query) use ($student): void {
                $query->where('student_id', $student->id);
            })
            ->where('semester', $currentSemester)
            ->where('school_year', $currentSchoolYear)
            ->with([
                'Schedule.room',
                'Subject',
                'ShsSubject',
                'Faculty',
                'Room',
            ])
            ->get();

        // Flatten and enhance the schedule data (same as ScheduleController)
        $weeklySchedule = $classes->flatMap(fn (\App\Models\Classes $class) => $class->Schedule->map(function (\App\Models\Schedule $schedule) use ($class): array {
            // Format the time in a more readable way
            $startTime = $schedule->start_time->format('g:i A');
            $endTime = $schedule->end_time->format('g:i A');

            return [
                'id' => $schedule->id,
                'day' => $schedule->day_of_week,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'subject' => $class->subject_title,
                'subject_code' => $class->subject_code ?? 'N/A',
                'room' => $schedule->room?->name ?? 'N/A',
                'teacher' => $class->faculty_full_name ?? 'TBA',
                'class_id' => $class->id,
                'section' => $class->section ?? 'N/A',
                'color' => $this->generateColorForSubject($class->subject_code ?? $class->subject_title),
            ];
        }))->sortBy([
            ['day', 'asc'],
            ['start_time', 'asc'],
        ])->values()->all();

        return $weeklySchedule;
    }

    /**
     * Get attendance data for dashboard widget
     */
    private function getAttendanceData($student): array
    {
        try {
            // Get the correct student identifier based on student type
            if ($student instanceof \App\Models\Students) {
                // College student - use the id as student_id for attendance records
                $studentId = $student->id;
            } elseif ($student instanceof \App\Models\ShsStudents) {
                // SHS student - use student_lrn
                $studentId = $student->student_lrn;
            } else {
                $studentId = null;
            }

            if (!$studentId) {
                return $this->getDefaultAttendanceData();
            }

            // Get basic attendance stats
            $dashboardData = $this->studentAttendanceService->getStudentDashboardData($studentId);

            return [
                'stats' => $dashboardData['overall_stats'],
                'alerts' => collect($dashboardData['attendance_alerts'])->take(3)->toArray(), // Limit to 3 alerts
                'recentClasses' => array_map(function ($classData) {
                    return [
                        'class' => $classData['class'],
                        'last_attendance' => $classData['recent_attendances'][0] ?? null,
                    ];
                }, collect($dashboardData['classes'])->take(5)->toArray()) // Limit to 5 recent classes
            ];
        } catch (\Exception $e) {
            // Log error and return default data
            logger()->error('Failed to get attendance data for dashboard', [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);

            return $this->getDefaultAttendanceData();
        }
    }

    /**
     * Get default attendance data when service fails
     */
    private function getDefaultAttendanceData(): array
    {
        return [
            'stats' => [
                'total' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0,
                'partial' => 0,
                'present_count' => 0,
                'attendance_rate' => 0,
            ],
            'alerts' => [],
            'recentClasses' => []
        ];
    }

    /**
     * Get admin dashboard with basic admin information
     */
    private function getAdminDashboard(User $user): Response
    {
        // Get general settings
        $generalSettings = GeneralSettings::first();

        // Get current semester and school year using the service
        $currentSemester = $this->settingsService->getCurrentSemester();
        $currentSchoolYear = $this->settingsService->getCurrentSchoolYearString();

        return Inertia::render('Dashboard', [
            'user' => [
                'name' => $user->name,
                'role' => $user->role,
                'email' => $user->email,
            ],
            'generalSettings' => $generalSettings,
            'currentSemester' => $currentSemester,
            'currentSchoolYear' => $currentSchoolYear,
            'isAdmin' => true,
            'stats' => [
                'total' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0,
                'partial' => 0,
                'present_count' => 0,
                'attendance_rate' => 0,
            ],
            'alerts' => [],
            'recentClasses' => [],
            'todaysClasses' => [],
            'attendanceData' => $this->getDefaultAttendanceData(),
        ]);
    }
}
