<?php

namespace App\Mail;

use App\Models\Students;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class StudentEnrollmentConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Students $student,
        public StudentEnrollment $enrollment,
        public Collection|array $subjects
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Subject Enrollment Confirmation',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Format subjects for display
        $formattedSubjects = collect($this->subjects)->map(function (Subject $subject) {
            return [
                'code' => $subject->code,
                'title' => $subject->title,
                'units' => $subject->units,
            ];
        })->toArray();
        
        // Format semester
        $semester = match ($this->enrollment->semester) {
            1 => '1st Semester',
            2 => '2nd Semester',
            default => 'Unknown Semester',
        };
        
        // Format academic year
        $academicYear = match ($this->enrollment->academic_year) {
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            4 => '4th Year',
            5 => '5th Year',
            default => $this->enrollment->academic_year . ' Year',
        };
        
        return new Content(
            view: 'emails.enrollment.subjects-confirmation',
            with: [
                'student' => $this->student,
                'enrollment' => $this->enrollment,
                'subjects' => $formattedSubjects,
                'semester' => $semester,
                'academicYear' => $academicYear,
                'schoolYear' => $this->enrollment->school_year,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
} 