<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Students;
use App\Models\GeneralSettings;
use App\Models\SubjectEnrolled;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

final class DashboardController extends Controller
{
    // Define constant for placeholder text
    private const COMING_SOON = 'Coming Soon';

    /**
     * Main dashboard action
     */
    public function __invoke(): Response|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Redirect guest users to the guest dashboard
        if (isset($user->role) && $user->role === 'guest') {
            return redirect()->route('enrolee.dashboard');
        }

        // Ensure the user is a student
        if (! $user->student) {
            abort(403, 'Only students can access the dashboard.');
        }

        /** @var Students $student */
        $student = $user->student;

        // Get general settings
        $generalSettings = GeneralSettings::first();
        
        // Get current semester and school year
        $settings = $this->getSettings();
        $currentSemester = $settings['semester'];
        $currentSchoolYear = $settings['schoolYear'];

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

        // Get course information
        $courseInfo = $this->getCourseInfo($student);

        // Fetch the current student enrollment for this academic period
        $studentEnrollment = \App\Models\StudentEnrollment::query()
            ->where('student_id', $student->id)
            ->currentAcademicPeriod()
            ->latest('created_at')
            ->first();

        return Inertia::render('Dashboard', [
            'student' => $studentData,
            'stats' => $statsData,
            'todaysClasses' => $todaysClassesData,
            'currentClass' => $currentClassData,
            'recentGrades' => $recentGradesData,
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
        ]);
    }

    /**
     * Get application settings
     */
    private function getSettings(): array
    {
        $settings = GeneralSettings::query()->first();
        return [
            'semester' => $settings->semester,
            'schoolYear' => $settings->getSchoolYear(),
        ];
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
            ->with(['subject', 'class']) // Eager load related data
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
}
