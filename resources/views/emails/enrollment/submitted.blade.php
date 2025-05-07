@component('mail::message')
# Enrollment Submitted!

Hello {{ $student->full_name }},

Your enrollment submission has been received. Here are your details:

**Course:** {{ $enrollment->course->title ?? 'N/A' }} ({{ $enrollment->course->code ?? '' }})

**School Year:** {{ $enrollment->school_year }}
**Semester:** {{ $enrollment->semester }}

**Status:** {{ $enrollment->status }}

We will review your submission and contact you for the next steps. If you have any questions, please reply to this email.

Thank you for choosing our institution!

Regards,
{{ config('app.name') }}
@endcomponent 