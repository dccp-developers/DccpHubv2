<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Courses;
use App\Models\GeneralSettings;
use App\Models\Students;
use App\Models\Subject;
use App\Models\SubjectEnrolled;
use App\Models\StudentEnrollment;
use App\Models\StudentTuition;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\Classes;

class EnrollmentController extends Controller
{
    /**
     * Show the subject enrollment page for students
     */
    public function showEnrollmentForm()
    {
        // Get current authenticated user
        $user = Auth::user();
        
        // Find associated student record
        $student = Students::where('email', $user->email)->first();
        
        if (!$student) {
            return redirect()->route('dashboard')->with('error', 'Student record not found.');
        }
        
        // Get general settings
        $generalSettings = GeneralSettings::first();
        
        // Check if enrollment is enabled
        if (!$generalSettings->online_enrollment_enabled) {
            return redirect()->route('dashboard')->with('error', 'Online enrollment is currently closed.');
        }
        
        // Get course information
        $course = Courses::with('Subjects')->find($student->course_id);
        
        if (!$course) {
            return redirect()->route('dashboard')->with('error', 'Course information not found.');
        }
        
        // Get current school year and semester from general settings
        $schoolYear = $generalSettings->getSchoolYear();
        $semester = $generalSettings->semester;
        
        // Get student's current academic year
        $academicYear = $student->academic_year;
        
        // Get all subjects for the course regardless of academic year or semester
        $subjects = Subject::where('course_id', $course->id)->get();
        
        // Get previously passed subjects
        $previouslyPassedSubjects = $this->getPreviouslyPassedSubjects($student->id);
        
        // Get currently enrolled subjects for the current semester and school year
        $currentlyEnrolledSubjects = $this->getCurrentlyEnrolledSubjects($student->id, $schoolYear, $semester);
        
        // Get all available classes for the current semester and school year
        $allAvailableClasses = Classes::where('semester', $semester)
            ->where('school_year', $schoolYear)
            ->get();
            
        // Group classes by subject code to handle multiple sections
        $availableClasses = [];
        foreach ($allAvailableClasses as $class) {
            $subjectCode = $class->subject_code;
            
            // If this is the first class for this subject, initialize array
            if (!isset($availableClasses[$subjectCode])) {
                $availableClasses[$subjectCode] = [];
            }
            
            // Add this class to the subject's array
            $availableClasses[$subjectCode][] = $class;
        }
            
        // Get all of student's enrolled subjects across all semesters
        $allEnrolledSubjects = SubjectEnrolled::where('student_id', $student->id)
            ->with('subject')
            ->get();
            
        return Inertia::render('Subjects/Enroll', [
            'student' => $student,
            'course' => $course,
            'subjects' => $subjects,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'schoolYear' => $schoolYear,
            'previouslyPassedSubjects' => $previouslyPassedSubjects,
            'currentlyEnrolledSubjects' => $currentlyEnrolledSubjects,
            'allEnrolledSubjects' => $allEnrolledSubjects,
            'availableClasses' => $availableClasses,
            'generalSettings' => $generalSettings,
        ]);
    }
    
    /**
     * Process the subject enrollment
     */
    public function processEnrollment(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'subjects' => 'required|array',
            'subjects.*' => 'required|exists:subject,id',
            'student_id' => 'required|exists:students,id',
            'course_id' => 'required|exists:courses,id',
            'academic_year' => 'required|integer|min:1',
            'semester' => 'required|integer|in:1,2',
            'school_year' => 'required|string',
            'downpayment' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'totalLectureFee' => 'required|numeric',
            'totalLabFee' => 'required|numeric',
            'totalMiscFee' => 'required|numeric',
            'overallTuition' => 'required|numeric',
            'selectedClasses' => 'required|array',
        ]);
        
        // Get student
        $student = Students::find($validated['student_id']);
        
        // Get general settings for the semester and school year
        $generalSettings = GeneralSettings::first();
        
        // Get selected subjects
        $selectedSubjects = Subject::whereIn('id', $validated['subjects'])->get();
        
        // Use the payment information from the request
        $totalLectureFee = $validated['totalLectureFee'];
        $totalLabFee = $validated['totalLabFee'];
        $totalMiscFee = $validated['totalMiscFee'];
        $overallTuition = $validated['overallTuition'];
        $downpayment = $validated['downpayment'];
        $paymentMethod = $validated['payment_method'];
        
        // Begin transaction
        return DB::transaction(function () use ($validated, $student, $selectedSubjects, $generalSettings, $totalLectureFee, $totalLabFee, $totalMiscFee, $overallTuition, $downpayment, $paymentMethod) {
            // Create student enrollment
            $enrollment = StudentEnrollment::create([
                'student_id' => $student->id,
                'course_id' => $validated['course_id'],
                'status' => 'Pending',
                'semester' => $validated['semester'],
                'academic_year' => $validated['academic_year'],
                'school_year' => $validated['school_year'],
                'downpayment' => $downpayment,
                'payment_method' => $paymentMethod,
                'remarks' => 'Self-enrolled via online portal',
            ]);
            
            // Create student tuition
            $tuition = StudentTuition::create([
                'student_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'total_tuition' => $overallTuition,
                'total_lectures' => $totalLectureFee,
                'total_laboratory' => $totalLabFee,
                'total_miscelaneous_fees' => $totalMiscFee,
                'overall_tuition' => $overallTuition,
                'downpayment' => $downpayment,
                'payment_method' => $paymentMethod,
                'total_balance' => $overallTuition - $downpayment,
                'status' => 'Pending',
                'semester' => $validated['semester'],
                'school_year' => $validated['school_year'],
                'academic_year' => $validated['academic_year'],
            ]);
            
            // Create subject enrolled records for each selected subject
            $subjectEnrollmentMaxId = SubjectEnrolled::max('id') ?? 0;
            $subjectEnrollmentNextId = $subjectEnrollmentMaxId + 1;
            
            // Get next ID for class enrollments
      
            
            foreach ($selectedSubjects as $subject) {
                // Create subject enrollment
                $subjectEnrolled = SubjectEnrolled::create([
                    'id' => $subjectEnrollmentNextId++,
                    'subject_id' => $subject->id,
                    'student_id' => $student->id,
                    'academic_year' => $validated['academic_year'],
                    'school_year' => $validated['school_year'],
                    'semester' => $validated['semester'],
                    'enrollment_id' => $enrollment->id,
                ]);
                
                
            }
            
            // Send email notification to student
            if ($student->email) {
                Mail::to($student->email)->send(new \App\Mail\StudentEnrollmentConfirmation(
                    $student,
                    $enrollment,
                     $selectedSubjects
                ));
            }
            
            return redirect()->route('dashboard')->with('success', 'Your enrollment has been submitted successfully. Please check your email for confirmation and payment instructions.');
        });
    }
    
    /**
     * Get previously passed subjects for a student
     */
    private function getPreviouslyPassedSubjects($studentId)
    {
        // Get subjects where student has a passing grade (75 or higher)
        return Subject::whereHas('subjectEnrolleds', function ($query) use ($studentId) {
            $query->where('student_id', $studentId)
                  ->where(function($q) {
                      $q->where('grade', '>=', 75)  // Passing grade
                        ->orWhere('remarks', 'Passed');
                  });
        })->get();
    }
    
    /**
     * Get currently enrolled subjects for a student
     */
    private function getCurrentlyEnrolledSubjects($studentId, $schoolYear, $semester)
    {
        return Subject::whereHas('subjectEnrolleds', function ($query) use ($studentId, $schoolYear, $semester) {
            $query->where('student_id', $studentId)
                  ->where('school_year', $schoolYear)
                  ->where('semester', $semester);
        })->get();
    }
    
    /**
     * Calculate lecture fee based on subjects
     */
    private function calculateLectureFee($subjects)
    {
        $costPerUnit = 500; // Example, should come from settings
        
        return $subjects->sum(function ($subject) use ($costPerUnit) {
            return $subject->units * $costPerUnit;
        });
    }
    
    /**
     * Calculate laboratory fee based on subjects
     */
    private function calculateLabFee($subjects)
    {
        $labFeePerHour = 300; // Example, should come from settings
        
        return $subjects->sum(function ($subject) use ($labFeePerHour) {
            return ($subject->laboratory ?? 0) * $labFeePerHour;
        });
    }
} 