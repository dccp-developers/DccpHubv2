<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Faculty;
use App\Models\Classes;
use App\Models\Schedule;
use App\Models\Students;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

final class FacultyDashboardController extends Controller
{
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

        // Get faculty's classes
        $classes = $this->getFacultyClasses($faculty);

        // Get faculty's schedule
        $schedule = $this->getFacultySchedule($faculty);

        // Get students taught by this faculty
        $students = $this->getFacultyStudents($faculty);

        // Calculate stats
        $stats = $this->calculateFacultyStats($faculty, $classes, $students);

        // Prepare faculty data
        $facultyData = [
            'name' => $faculty->faculty_full_name,
            'department' => $faculty->department,
            'email' => $faculty->email,
            'phone' => $faculty->phone_number,
            'office_hours' => $faculty->office_hours,
            'photo_url' => $faculty->photo_url,
        ];

        return Inertia::render('Faculty/Dashboard', [
            'faculty' => $facultyData,
            'stats' => $stats,
            'classes' => $classes,
            'schedule' => $schedule,
            'students' => $students,
            'user' => $user,
        ]);
    }

    /**
     * Get classes taught by the faculty
     */
    private function getFacultyClasses(Faculty $faculty): array
    {
        $classes = Classes::where('faculty_id', $faculty->id)
            ->with(['subject', 'course'])
            ->get();

        return $classes->map(function (Classes $class) {
            return [
                'id' => $class->id,
                'subject_code' => $class->subject->code ?? 'N/A',
                'subject_title' => $class->subject->title ?? 'N/A',
                'course_code' => $class->course->code ?? 'N/A',
                'course_title' => $class->course->title ?? 'N/A',
                'semester' => $class->semester,
                'academic_year' => $class->academic_year,
                'room' => $class->room ?? 'TBA',
                'schedule' => $class->schedule ?? 'TBA',
                'enrolled_count' => $class->classEnrollments()->count(),
                'max_capacity' => $class->max_capacity ?? 0,
            ];
        })->toArray();
    }

    /**
     * Get faculty's schedule
     */
    private function getFacultySchedule(Faculty $faculty): array
    {
        $schedules = Schedule::where('faculty_id', $faculty->id)
            ->with(['subject', 'class'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return $schedules->map(function (Schedule $schedule) {
            return [
                'id' => $schedule->id,
                'day_of_week' => $schedule->day_of_week,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'subject_code' => $schedule->subject->code ?? 'N/A',
                'subject_title' => $schedule->subject->title ?? 'N/A',
                'room' => $schedule->room ?? 'TBA',
                'class_id' => $schedule->class_id,
            ];
        })->toArray();
    }

    /**
     * Get students taught by this faculty
     */
    private function getFacultyStudents(Faculty $faculty): array
    {
        // Get all students enrolled in classes taught by this faculty
        $students = Students::whereHas('classEnrollments.class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id);
        })
        ->with(['course', 'classEnrollments.class.subject'])
        ->distinct()
        ->get();

        return $students->map(function (Students $student) use ($faculty) {
            // Get classes this student is taking with this faculty
            $studentClasses = $student->classEnrollments()
                ->whereHas('class', function ($query) use ($faculty) {
                    $query->where('faculty_id', $faculty->id);
                })
                ->with('class.subject')
                ->get();

            return [
                'id' => $student->id,
                'student_id' => $student->student_id ?? $student->id,
                'name' => $student->full_name,
                'email' => $student->email,
                'course' => $student->course->title ?? 'N/A',
                'academic_year' => $student->academic_year,
                'classes' => $studentClasses->map(function ($enrollment) {
                    return [
                        'subject_code' => $enrollment->class->subject->code ?? 'N/A',
                        'subject_title' => $enrollment->class->subject->title ?? 'N/A',
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    /**
     * Calculate stats for the faculty dashboard
     */
    private function calculateFacultyStats(Faculty $faculty, array $classes, array $students): array
    {
        $totalClasses = count($classes);
        $totalStudents = count($students);
        
        // Calculate total enrolled students across all classes
        $totalEnrolled = collect($classes)->sum('enrolled_count');
        
        // Calculate average class size
        $averageClassSize = $totalClasses > 0 ? round($totalEnrolled / $totalClasses, 1) : 0;

        return [
            [
                'label' => 'Total Classes',
                'value' => $totalClasses,
                'description' => 'Classes you are teaching this semester'
            ],
            [
                'label' => 'Total Students',
                'value' => $totalStudents,
                'description' => 'Unique students in your classes'
            ],
            [
                'label' => 'Total Enrollments',
                'value' => $totalEnrolled,
                'description' => 'Total enrollments across all classes'
            ],
            [
                'label' => 'Avg. Class Size',
                'value' => $averageClassSize,
                'description' => 'Average number of students per class'
            ],
        ];
    }
}
