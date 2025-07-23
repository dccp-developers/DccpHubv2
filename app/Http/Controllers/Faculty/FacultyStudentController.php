<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Courses;
use App\Services\Faculty\FacultyStudentService;
use App\Services\GeneralSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class FacultyStudentController extends Controller
{
    public function __construct(
        private readonly FacultyStudentService $studentService,
        private readonly GeneralSettingsService $settingsService
    ) {}

    /**
     * Display a listing of students taught by the faculty
     */
    public function index(Request $request): Response
    {
        try {
            $faculty = $request->user()->faculty;
            
            if (!$faculty) {
                return $this->renderErrorResponse('Faculty profile not found.');
            }

            // Get filter parameters
            $filters = [
                'search' => $request->get('search'),
                'course_id' => $request->get('course_id'),
                'academic_year' => $request->get('academic_year'),
                'class_id' => $request->get('class_id'),
            ];

            // Remove empty filters
            $filters = array_filter($filters, fn($value) => !empty($value));

            // Get paginated students
            $students = $this->studentService->getFacultyStudents($faculty, $filters, 15);

            // Get filter options
            $filterOptions = $this->getFilterOptions($faculty);

            return Inertia::render('Faculty/Students/Index', [
                'students' => $students,
                'filters' => $filters,
                'filterOptions' => $filterOptions,
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
            logger()->error('Faculty student list error', [
                'faculty_id' => $request->user()->faculty?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->renderErrorResponse('Unable to load student data. Please try again later.');
        }
    }

    /**
     * Display the specified student's details
     */
    public function show(Request $request, int $studentId): Response
    {
        try {
            $faculty = $request->user()->faculty;
            
            if (!$faculty) {
                return $this->renderErrorResponse('Faculty profile not found.');
            }

            // Get student details
            $studentDetails = $this->studentService->getStudentDetails($faculty, $studentId);
            
            if (!$studentDetails) {
                return $this->renderErrorResponse('Student not found or you do not have permission to view this student.');
            }

            // Get student's class enrollments with this faculty
            $classEnrollments = $this->studentService->getStudentClassEnrollments($faculty, $studentId);

            // Get student's performance in faculty's classes
            $performance = $this->studentService->getStudentPerformance($faculty, $studentId);

            return Inertia::render('Faculty/Students/Show', [
                'student' => $studentDetails,
                'classEnrollments' => $classEnrollments,
                'performance' => $performance,
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
            logger()->error('Faculty student view error', [
                'faculty_id' => $request->user()->faculty?->id,
                'student_id' => $studentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->renderErrorResponse('Unable to load student details. Please try again later.');
        }
    }

    /**
     * Get filter options for the student list
     */
    private function getFilterOptions(Faculty $faculty): array
    {
        // Get courses of students taught by this faculty
        $courseIds = \App\Models\Students::whereHas('classEnrollments', function ($query) use ($faculty) {
            $query->whereHas('class', function ($q) use ($faculty) {
                $q->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->settingsService->getCurrentSemester())
                  ->where('school_year', $this->settingsService->getCurrentSchoolYearString());
            });
        })->distinct()->pluck('course_id');

        $courses = Courses::whereIn('id', $courseIds)->select('id', 'title', 'code')->get();

        // Get academic years of students taught by this faculty
        $academicYears = DB::table('students')
            ->join('class_enrollments', 'students.id', '=', 'class_enrollments.student_id')
            ->join('classes', 'class_enrollments.class_id', '=', 'classes.id')
            ->where('classes.faculty_id', $faculty->id)
            ->where('classes.semester', (string) $this->settingsService->getCurrentSemester())
            ->where('classes.school_year', $this->settingsService->getCurrentSchoolYearString())
            ->distinct()
            ->pluck('students.academic_year')
            ->sort()
            ->values();

        // Get classes taught by this faculty
        $classes = $faculty->classes()
            ->where('semester', (string) $this->settingsService->getCurrentSemester())
            ->where('school_year', $this->settingsService->getCurrentSchoolYearString())
            ->with(['subject', 'ShsSubject'])
            ->get()
            ->map(function ($class) {
                $subject = $class->classification === 'shs' ? $class->ShsSubject : $class->subject;
                return [
                    'id' => $class->id,
                    'name' => ($subject ? $subject->title : 'Unknown Subject') . ' - ' . $class->section,
                    'subject_code' => $subject ? $subject->code : $class->subject_code,
                    'section' => $class->section,
                ];
            });

        return [
            'courses' => $courses,
            'academicYears' => $academicYears,
            'classes' => $classes,
        ];
    }

    /**
     * Render error response
     */
    private function renderErrorResponse(string $message): Response
    {
        return Inertia::render('Faculty/Students/Index', [
            'error' => $message,
            'students' => [
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'total' => 0,
            ],
            'filters' => [],
            'filterOptions' => [
                'courses' => [],
                'academicYears' => [],
                'classes' => [],
            ],
            'faculty' => null,
            'currentSemester' => (string) $this->settingsService->getCurrentSemester(),
            'schoolYear' => $this->settingsService->getCurrentSchoolYearString(),
            'availableSemesters' => $this->settingsService->getAvailableSemesters(),
            'availableSchoolYears' => $this->settingsService->getAvailableSchoolYears(),
        ]);
    }
}
