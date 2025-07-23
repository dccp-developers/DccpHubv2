<?php

declare(strict_types=1);

namespace App\Services\Faculty;

use App\Models\Faculty;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\class_enrollments;
use App\Services\GeneralSettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class FacultyClassService
{
    public function __construct(
        private readonly GeneralSettingsService $settingsService
    ) {}
    /**
     * Get all classes for a faculty member
     */
    public function getFacultyClasses(Faculty $faculty): Collection
    {
        return $faculty->classes()
            ->where('semester', (string) $this->getCurrentSemester())
            ->where('school_year', $this->getCurrentSchoolYear())
            ->with(['subject', 'ShsSubject', 'ClassStudents.student', 'room'])
            ->withCount('ClassStudents')
            ->get()
            ->map(function ($class) {
                return $this->formatClassItem($class);
            });
    }

    /**
     * Get a specific class with detailed information
     */
    public function getClassDetails(Faculty $faculty, int $classId): ?array
    {
        $class = $faculty->classes()
            ->where('id', $classId)
            ->with(['subject', 'ShsSubject', 'ClassStudents.student', 'Schedule.room'])
            ->withCount('ClassStudents')
            ->first();

        if (!$class) {
            return null;
        }

        return $this->formatClassItemDetailed($class);
    }

    /**
     * Get class statistics for a faculty member
     */
    public function getClassStatistics(Faculty $faculty): array
    {
        $classes = $faculty->classes()
            ->where('semester', (string) $this->getCurrentSemester())
            ->where('school_year', $this->getCurrentSchoolYear())
            ->withCount('ClassStudents')
            ->get();

        $totalClasses = $classes->count();
        $totalStudents = $classes->sum('class_students_count');
        $averageClassSize = $totalClasses > 0 ? round($totalStudents / $totalClasses, 1) : 0;

        // Get grade distribution
        $gradeDistribution = $this->getGradeDistribution($faculty);

        return [
            'total_classes' => $totalClasses,
            'total_students' => $totalStudents,
            'average_class_size' => $averageClassSize,
            'largest_class' => $classes->max('class_students_count') ?? 0,
            'smallest_class' => $classes->min('class_students_count') ?? 0,
            'grade_distribution' => $gradeDistribution,
            'subjects_taught' => $this->getSubjectsTaught($faculty),
        ];
    }

    /**
     * Get students for a specific class
     */
    public function getClassStudents(Faculty $faculty, int $classId): Collection
    {
        $class = $faculty->classes()->where('id', $classId)->first();
        
        if (!$class) {
            return collect();
        }

        return $class->ClassStudents()
            ->with('student')
            ->get()
            ->map(function ($enrollment) {
                return [
                    'enrollment_id' => $enrollment->id,
                    'student_id' => $enrollment->student->id,
                    'student_number' => $enrollment->student->student_number,
                    'name' => $enrollment->student->first_name . ' ' . $enrollment->student->last_name,
                    'email' => $enrollment->student->email,
                    'course' => $enrollment->student->course ?? 'N/A',
                    'year_level' => $enrollment->student->year_level ?? 'N/A',
                    'status' => $enrollment->status ?? 'enrolled',
                    'midterm_grade' => $enrollment->midterm_grade,
                    'final_grade' => $enrollment->final_grade,
                    'total_average' => $enrollment->total_average,
                    'remarks' => $enrollment->remarks,
                ];
            });
    }

    /**
     * Get class performance metrics
     */
    public function getClassPerformance(Faculty $faculty, int $classId): array
    {
        $enrollments = class_enrollments::where('class_id', $classId)
            ->whereNotNull('total_average')
            ->get();

        if ($enrollments->isEmpty()) {
            return [
                'average_grade' => 0,
                'passing_rate' => 0,
                'highest_grade' => 0,
                'lowest_grade' => 0,
                'grade_distribution' => [],
                'total_students' => 0,
            ];
        }

        $grades = $enrollments->pluck('total_average')->filter();
        $passingGrades = $grades->filter(fn($grade) => $grade >= 75);
        $gradesCount = $grades->count();

        return [
            'average_grade' => $gradesCount > 0 ? round($grades->avg(), 2) : 0,
            'passing_rate' => $gradesCount > 0 ? round(($passingGrades->count() / $gradesCount) * 100, 1) : 0,
            'highest_grade' => $grades->max() ?? 0,
            'lowest_grade' => $grades->min() ?? 0,
            'grade_distribution' => $this->calculateGradeDistribution($grades),
            'total_students' => $enrollments->count(),
        ];
    }

    /**
     * Format class item for display
     */
    private function formatClassItem(Classes $class): array
    {
        // Get the appropriate subject based on classification
        $subject = $class->classification === 'shs' ? $class->ShsSubject : $class->subject;
        $room = $class->room;

        // Get subject code and title
        $subjectCode = $subject ? $subject->code : $class->subject_code;
        $subjectTitle = $subject ? $subject->title : 'Unknown Subject';

        return [
            'id' => $class->id,
            'subject_code' => $subjectCode,
            'subject_title' => $subjectTitle,
            'section' => $class->section,
            'room' => $room ? $room->name : 'TBA',
            'room_code' => $room ? $room->class_code : null,
            'semester' => $class->semester,
            'school_year' => $class->school_year,
            'student_count' => $class->class_students_count ?? $class->ClassStudents->count(),
            'max_students' => $class->maximum_slots ?? 40,
            'classification' => $class->classification,
            'color' => $this->getSubjectColor($subjectCode),
            'units' => $subject ? $subject->units ?? 3 : 3,
            'lecture_hours' => $subject ? $subject->lecture ?? 3 : 3,
            'lab_hours' => $subject ? $subject->laboratory ?? 0 : 0,
            'status' => $class->status ?? 'active',
            'description' => $subjectTitle,
        ];
    }

    /**
     * Format class item with detailed information
     */
    private function formatClassItemDetailed(Classes $class): array
    {
        $basicInfo = $this->formatClassItem($class);
        
        return array_merge($basicInfo, [
            'students' => $class->ClassStudents->map(function ($enrollment) {
                return [
                    'id' => $enrollment->student->id,
                    'name' => $enrollment->student->first_name . ' ' . $enrollment->student->last_name,
                    'student_number' => $enrollment->student->student_id,
                    'email' => $enrollment->student->email,
                    'status' => $enrollment->status,
                ];
            }),
            'schedules' => $class->Schedule->map(function ($schedule) {
                return [
                    'day_of_week' => $schedule->day_of_week,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'room' => $schedule->room ? $schedule->room->name : 'TBA',
                ];
            }),
            'performance' => $this->getClassPerformance($class->faculty, $class->id),
        ]);
    }

    /**
     * Get grade distribution for faculty's classes
     */
    private function getGradeDistribution(Faculty $faculty): array
    {
        $enrollments = class_enrollments::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->whereNotNull('total_average')
        ->get();

        if ($enrollments->isEmpty()) {
            return [];
        }

        $grades = $enrollments->pluck('total_average');
        return $this->calculateGradeDistribution($grades);
    }

    /**
     * Calculate grade distribution from grades collection
     */
    private function calculateGradeDistribution(Collection $grades): array
    {
        $distribution = [
            'A' => 0, // 90-100
            'B' => 0, // 80-89
            'C' => 0, // 75-79
            'D' => 0, // 65-74
            'F' => 0, // Below 65
        ];

        foreach ($grades as $grade) {
            if ($grade >= 90) {
                $distribution['A']++;
            } elseif ($grade >= 80) {
                $distribution['B']++;
            } elseif ($grade >= 75) {
                $distribution['C']++;
            } elseif ($grade >= 65) {
                $distribution['D']++;
            } else {
                $distribution['F']++;
            }
        }

        return $distribution;
    }

    /**
     * Get subjects taught by faculty
     */
    public function getSubjectsTaught(Faculty $faculty): Collection
    {
        return Subject::whereHas('classes', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->distinct()
        ->get()
        ->map(function ($subject) {
            return [
                'code' => $subject->code,
                'title' => $subject->title,
                'units' => $subject->units,
                'lecture_hours' => $subject->lecture,
                'lab_hours' => $subject->laboratory,
            ];
        });
    }

    /**
     * Get color for subject based on subject code
     */
    private function getSubjectColor(string $subjectCode): string
    {
        $colors = [
            'CS' => 'blue-500',
            'IT' => 'green-500',
            'MATH' => 'purple-500',
            'ENG' => 'red-500',
            'SCI' => 'yellow-500',
            'PE' => 'pink-500',
            'NSTP' => 'indigo-500',
        ];

        $prefix = substr($subjectCode, 0, 2);
        return $colors[$prefix] ?? 'gray-500';
    }

    /**
     * Get class attendance summary
     */
    public function getClassAttendance(Faculty $faculty, int $classId): array
    {
        // Placeholder for attendance functionality
        // This would integrate with an attendance system
        return [
            'total_sessions' => 20,
            'sessions_held' => 15,
            'average_attendance' => 85.5,
            'attendance_trend' => 'stable',
            'recent_sessions' => []
        ];
    }

    /**
     * Get class announcements
     */
    public function getClassAnnouncements(Faculty $faculty, int $classId): Collection
    {
        // Placeholder for announcements functionality
        // This would integrate with an announcements system
        return collect([]);
    }

    /**
     * Get current semester using GeneralSettingsService
     */
    private function getCurrentSemester(): int
    {
        return $this->settingsService->getCurrentSemester();
    }

    /**
     * Get current school year using GeneralSettingsService
     */
    private function getCurrentSchoolYear(): string
    {
        return $this->settingsService->getCurrentSchoolYearString();
    }
}
