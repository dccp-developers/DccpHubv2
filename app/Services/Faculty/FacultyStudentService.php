<?php

declare(strict_types=1);

namespace App\Services\Faculty;

use App\Models\Faculty;
use App\Models\Students;
use App\Models\class_enrollments;
use App\Services\GeneralSettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class FacultyStudentService
{
    public function __construct(
        private readonly GeneralSettingsService $settingsService
    ) {}

    /**
     * Get paginated list of students taught by faculty
     */
    public function getFacultyStudents(
        Faculty $faculty,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->buildStudentsQuery($faculty, $filters);
        
        return $query->paginate($perPage);
    }

    /**
     * Get detailed information about a specific student
     */
    public function getStudentDetails(Faculty $faculty, int $studentId): ?array
    {
        // Verify faculty teaches this student
        if (!$this->facultyTeachesStudent($faculty, $studentId)) {
            return null;
        }

        $student = Students::with([
            'Course',
            'personalInfo',
            'studentEducationInfo', 
            'studentContactsInfo',
            'studentParentInfo',
            'DocumentLocation',
            'classEnrollments' => function ($query) use ($faculty) {
                $query->whereHas('class', function ($q) use ($faculty) {
                    $q->where('faculty_id', $faculty->id)
                      ->where('semester', (string) $this->getCurrentSemester())
                      ->where('school_year', $this->getCurrentSchoolYear());
                })->with(['class.subject', 'class.ShsSubject']);
            }
        ])->find($studentId);

        if (!$student) {
            return null;
        }

        return $this->formatStudentDetails($student);
    }

    /**
     * Get student's class enrollments with faculty
     */
    public function getStudentClassEnrollments(Faculty $faculty, int $studentId): Collection
    {
        return class_enrollments::where('student_id', $studentId)
            ->whereHas('class', function ($query) use ($faculty) {
                $query->where('faculty_id', $faculty->id)
                      ->where('semester', (string) $this->getCurrentSemester())
                      ->where('school_year', $this->getCurrentSchoolYear());
            })
            ->with(['class.subject', 'class.ShsSubject', 'class.room'])
            ->get()
            ->map(function ($enrollment) {
                return $this->formatEnrollmentItem($enrollment);
            });
    }

    /**
     * Get student's academic performance in faculty's classes
     */
    public function getStudentPerformance(Faculty $faculty, int $studentId): array
    {
        $enrollments = class_enrollments::where('student_id', $studentId)
            ->whereHas('class', function ($query) use ($faculty) {
                $query->where('faculty_id', $faculty->id)
                      ->where('semester', (string) $this->getCurrentSemester())
                      ->where('school_year', $this->getCurrentSchoolYear());
            })
            ->whereNotNull('total_average')
            ->get();

        $totalClasses = $enrollments->count();
        $averageGrade = $enrollments->avg('total_average');
        $passingGrades = $enrollments->where('total_average', '>=', 75)->count();
        $failingGrades = $enrollments->where('total_average', '<', 75)->count();

        return [
            'total_classes' => $totalClasses,
            'average_grade' => $averageGrade ? round($averageGrade, 2) : 0,
            'passing_classes' => $passingGrades,
            'failing_classes' => $failingGrades,
            'passing_rate' => $totalClasses > 0 ? round(($passingGrades / $totalClasses) * 100, 2) : 0,
            'grade_distribution' => $this->getGradeDistribution($enrollments),
        ];
    }

    /**
     * Build the base query for students taught by faculty
     */
    private function buildStudentsQuery(Faculty $faculty, array $filters = []): Builder
    {
        $query = Students::whereHas('classEnrollments', function ($query) use ($faculty) {
            $query->whereHas('class', function ($q) use ($faculty) {
                $q->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
            });
        })
        ->with([
            'Course',
            'classEnrollments' => function ($query) use ($faculty) {
                $query->whereHas('class', function ($q) use ($faculty) {
                    $q->where('faculty_id', $faculty->id)
                      ->where('semester', (string) $this->getCurrentSemester())
                      ->where('school_year', $this->getCurrentSchoolYear());
                })->with(['class.subject', 'class.ShsSubject']);
            }
        ])
        ->distinct();

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        if (!empty($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }

        if (!empty($filters['class_id'])) {
            $query->whereHas('classEnrollments', function ($q) use ($filters) {
                $q->where('class_id', $filters['class_id']);
            });
        }

        // Default ordering
        $query->orderBy('last_name')->orderBy('first_name');

        return $query;
    }

    /**
     * Check if faculty teaches a specific student
     */
    private function facultyTeachesStudent(Faculty $faculty, int $studentId): bool
    {
        return class_enrollments::where('student_id', $studentId)
            ->whereHas('class', function ($query) use ($faculty) {
                $query->where('faculty_id', $faculty->id)
                      ->where('semester', (string) $this->getCurrentSemester())
                      ->where('school_year', $this->getCurrentSchoolYear());
            })
            ->exists();
    }

    /**
     * Format student details for display
     */
    private function formatStudentDetails(Students $student): array
    {
        return [
            'id' => $student->id,
            'student_id' => $student->student_id,
            'full_name' => $student->full_name,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'middle_name' => $student->middle_name,
            'email' => $student->email,
            'gender' => $student->gender,
            'birth_date' => $student->birth_date,
            'age' => $student->age,
            'address' => $student->address,
            'contacts' => $student->contacts,
            'academic_year' => $student->academic_year,
            'profile_photo' => $student->picture_1x1,
            'course' => $student->Course ? [
                'id' => $student->Course->id,
                'name' => $student->Course->title,
                'code' => $student->Course->code,
                'description' => $student->Course->description,
            ] : null,
            'personal_info' => $student->personalInfo ? [
                'nationality' => $student->personalInfo->citizenship,
                'religion' => $student->personalInfo->religion,
                'civil_status' => $student->personalInfo->civil_status,
                'place_of_birth' => $student->personalInfo->birthplace,
                'weight' => $student->personalInfo->weight,
                'height' => $student->personalInfo->height,
                'current_address' => $student->personalInfo->current_adress,
                'permanent_address' => $student->personalInfo->permanent_address,
            ] : null,
            'education_info' => $student->studentEducationInfo ? [
                'elementary_school' => $student->studentEducationInfo->elementary_school,
                'elementary_year_graduated' => $student->studentEducationInfo->elementary_graduate_year,
                'elementary_school_address' => $student->studentEducationInfo->elementary_school_address,
                'junior_high_school' => $student->studentEducationInfo->junior_high_school_name,
                'junior_high_year_graduated' => $student->studentEducationInfo->junior_high_graduation_year,
                'junior_high_school_address' => $student->studentEducationInfo->junior_high_school_address,
                'senior_high_school' => $student->studentEducationInfo->senior_high_name,
                'senior_high_year_graduated' => $student->studentEducationInfo->senior_high_graduate_year,
                'senior_high_school_address' => $student->studentEducationInfo->senior_high_address,
            ] : null,
            'contact_info' => $student->studentContactsInfo ? [
                'personal_contact' => $student->studentContactsInfo->personal_contact,
                'facebook_contact' => $student->studentContactsInfo->facebook_contact,
                'emergency_contact_name' => $student->studentContactsInfo->emergency_contact_name,
                'emergency_contact_phone' => $student->studentContactsInfo->emergency_contact_phone,
                'emergency_contact_address' => $student->studentContactsInfo->emergency_contact_address,
            ] : null,
            'parent_info' => $student->studentParentInfo ? [
                'father_name' => $student->studentParentInfo->fathers_name,
                'mother_name' => $student->studentParentInfo->mothers_name,
            ] : null,
            'class_enrollments' => $student->classEnrollments->map(function ($enrollment) {
                return $this->formatEnrollmentItem($enrollment);
            }),
            'created_at' => $student->created_at,
            'updated_at' => $student->updated_at,
        ];
    }

    /**
     * Format enrollment item for display
     */
    private function formatEnrollmentItem($enrollment): array
    {
        $class = $enrollment->class;
        $subject = $class->classification === 'shs' ? $class->ShsSubject : $class->subject;

        return [
            'id' => $enrollment->id,
            'class_id' => $class->id,
            'subject_code' => $subject ? $subject->code : $class->subject_code,
            'subject_title' => $subject ? $subject->title : 'Unknown Subject',
            'section' => $class->section,
            'classification' => $class->classification,
            'room' => $class->room ? $class->room->name : 'TBA',
            'prelim_grade' => $enrollment->prelim_grade,
            'midterm_grade' => $enrollment->midterm_grade,
            'finals_grade' => $enrollment->finals_grade,
            'total_average' => $enrollment->total_average,
            'grade_status' => $enrollment->grade_status,
            'status' => $enrollment->status,
            'is_completed' => $enrollment->is_completed,
        ];
    }

    /**
     * Get grade distribution for performance analysis
     */
    private function getGradeDistribution(Collection $enrollments): array
    {
        $distribution = [
            'A' => 0, // 90-100
            'B' => 0, // 80-89
            'C' => 0, // 75-79
            'D' => 0, // 65-74
            'F' => 0, // Below 65
        ];

        foreach ($enrollments as $enrollment) {
            $grade = $enrollment->total_average;
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
     * Get current semester from settings
     */
    private function getCurrentSemester(): int
    {
        return $this->settingsService->getCurrentSemester();
    }

    /**
     * Get current school year from settings
     */
    private function getCurrentSchoolYear(): string
    {
        return $this->settingsService->getCurrentSchoolYearString();
    }
}
