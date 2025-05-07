<?php

namespace App\Mail;

use App\Models\Students;
use App\Models\StudentEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EnrollmentSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public $student;
    public $enrollment;

    /**
     * Create a new message instance.
     *
     * @param Students $student
     * @param StudentEnrollment $enrollment
     */
    public function __construct(Students $student, StudentEnrollment $enrollment)
    {
        $this->student = $student;
        $this->enrollment = $enrollment;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Enrollment Submission')
            ->markdown('emails.enrollment.submitted')
            ->with([
                'student' => $this->student,
                'enrollment' => $this->enrollment,
            ]);
    }
} 