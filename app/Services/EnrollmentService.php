<?php

namespace App\Services;

use App\Models\PendingEnrollment;
use App\Models\Students;
use App\Models\StudentContact;
use App\Models\StudentEducationInfo;
use App\Models\StudentParentInfo;
use App\Models\StudentPersonal;
use App\Models\StudentTuition;
use App\Models\StudentEnrollment;
use App\Models\SubjectEnrolled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\EnrollmentSubmitted;
use Exception;

class EnrollmentService
{
    /**
     * Process a pending enrollment and create all related records.
     *
     * @param PendingEnrollment $pending
     * @param array $paymentData (downpayment, payment_method, subjects, etc)
     * @return array [Students $student, StudentEnrollment $enrollment]
     * @throws Exception
     */
    public function process(PendingEnrollment $pending, array $paymentData): array
    {
        return DB::transaction(function () use ($pending, $paymentData) {
            $data = $pending->data;

            // 1. Create StudentContact
            $contact = StudentContact::create([
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'emergency_contact_address' => $data['emergency_contact_address'] ?? null,
                'facebook_contact' => $data['facebook_contact'] ?? null,
                'personal_contact' => $data['personal_contact'] ?? null,
            ]);

            // 2. Create StudentEducationInfo
            $education = StudentEducationInfo::create([
                'elementary_school' => $data['elementary_school'] ?? null,
                'elementary_graduate_year' => $data['elementary_graduate_year'] ?? null,
                'elementary_school_address' => $data['elementary_school_address'] ?? null,
                'senior_high_name' => $data['senior_high_name'] ?? null,
                'senior_high_graduate_year' => $data['senior_high_graduate_year'] ?? null,
                'senior_high_address' => $data['senior_high_address'] ?? null,
                'junior_high_school_name' => $data['junior_high_school_name'] ?? null,
                'junior_high_school_address' => $data['junior_high_school_address'] ?? null,
                'junior_high_graduation_year' => $data['junior_high_graduation_year'] ?? null,
            ]);

            // 3. Create StudentParentInfo
            $parent = StudentParentInfo::create([
                'fathers_name' => $data['fathers_name'] ?? null,
                'mothers_name' => $data['mothers_name'] ?? null,
            ]);

            // 4. Create StudentPersonal
            $personal = StudentPersonal::create([
                'birthplace' => $data['birthplace'] ?? null,
                'civil_status' => $data['civil_status'] ?? null,
                'citizenship' => $data['citizenship'] ?? null,
                'religion' => $data['religion'] ?? null,
                'weight' => $data['weight'] ?? null,
                'height' => $data['height'] ?? null,
                'current_adress' => $data['current_adress'] ?? null,
                'permanent_address' => $data['permanent_address'] ?? null,
            ]);

            // 5. Create Students
            // Get the highest id and increment
            $maxId = \App\Models\Students::max('id') ?? 0;
            $newId = $maxId + 1;
            // Calculate age from birth_date
            $age = null;
            if (!empty($data['birth_date'])) {
                try {
                    $age = \Carbon\Carbon::parse($data['birth_date'])->age;
                } catch (\Exception $e) {
                    $age = null;
                }
            }
            $student = Students::create([
                'id' => $newId,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'middle_name' => $data['middle_name'] ?? null,
                'gender' => $data['gender'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'age' => $age,
                'address' => $data['current_adress'] ?? null,
                'contacts' => $data['personal_contact'] ?? null,
                'course_id' => $data['course_id'] ?? null,
                'academic_year' => $paymentData['academic_year'] ?? null,
                'email' => $data['email'] ?? null,
                'profile_url' => '',
                'student_contact_id' => $contact->id,
                'student_parent_info' => $parent->id,
                'student_education_id' => $education->id,
                'student_personal_id' => $personal->id,
                'remarks' => null,
            ]);

            // Format school year as 'YYYY - YYYY' if needed
            $schoolYear = $paymentData['school_year'] ?? null;
            if ($schoolYear && preg_match('/^(\d{4})-(\d{4})$/', $schoolYear, $matches)) {
                $schoolYear = $matches[1] . ' - ' . $matches[2];
            }

            // 6. Create StudentEnrollment (before SubjectEnrolled)
            $enrollment = StudentEnrollment::create([
                'student_id' => $student->id,
                'course_id' => $data['course_id'] ?? null,
                'status' => 'Pending',
                'semester' => $paymentData['semester'] ?? null,
                'academic_year' => $paymentData['academic_year'] ?? null,
                'school_year' => $schoolYear,
                'downpayment' => $paymentData['downpayment'] ?? null,
                'remarks' => null,
            ]);

            // 7. Create StudentTuition
            $tuition = StudentTuition::create([
                'student_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'total_tuition' => $paymentData['totalLectureFee'] ?? 0,
                'total_lectures' => $paymentData['totalLectureFee'] ?? 0,
                'total_laboratory' => $paymentData['totalLabFee'] ?? 0,
                'total_miscelaneous_fees' => $paymentData['totalMiscFee'] ?? 0,
                'overall_tuition' => $paymentData['overallTuition'] ?? 0,
                'downpayment' => $paymentData['downpayment'] ?? 0,
                'total_balance' => ($paymentData['overallTuition'] ?? 0) - ($paymentData['downpayment'] ?? 0),
                'status' => 'Pending',
                'semester' => $paymentData['semester'] ?? null,
                'school_year' => $schoolYear,
                'academic_year' => $paymentData['academic_year'] ?? null,
            ]);

            // 8. Create SubjectEnrolled for each subject, linking to enrollment
            $subjectsToEnroll = $paymentData['subjects'] ?? ($data['subjects'] ?? []);
            $subjectEnrollmentMaxId = \App\Models\SubjectEnrolled::max('id') ?? 0;
            $subjectEnrollmentNextId = $subjectEnrollmentMaxId + 1;
            foreach ($subjectsToEnroll as $subject) {
                SubjectEnrolled::create([
                    'id' => $subjectEnrollmentNextId++,
                    'subject_id' => $subject['id'],
                    'student_id' => $student->id,
                    'academic_year' => $paymentData['academic_year'] ?? null,
                    'school_year' => $schoolYear,
                    'semester' => $paymentData['semester'] ?? null,
                    'enrollment_id' => $enrollment->id,
                ]);
            }

            // 9. Send email to student
            if (!empty($data['email'])) {
                Mail::to($data['email'])->send(new EnrollmentSubmitted($student, $enrollment));
            }

            return [$student, $enrollment];
        });
    }
} 