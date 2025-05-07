<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Models\GeneralSettings;
use App\Models\GuestEnrollment;
use App\Models\Courses;
use App\Models\Subject;
use App\Models\GuestPersonalInfo;
use App\Models\PendingEnrollment;

final class GuestDashboardController extends Controller
{
   
    public function __invoke(): Response
    {
        // Get the first approved guest enrollment
        $guestEnrollment = PendingEnrollment::query()->where('status', 'approved')->orWhere('status', 'processed')->first();
        if (! $guestEnrollment) {
            abort(403, 'No approved guest enrollment found.');
        }

        // Use accessors to get guest info and course
        $guestInfo = $guestEnrollment->data; // All guest info is in the data array
        $course = Courses::find($guestEnrollment->course_id); // Use accessor for course_id
        $settings = GeneralSettings::query()->first();
        $semester = $settings->semester; // integer
        $schoolYear = $settings->getSchoolYear();

        // Get subjects for this course, filtering for 1st Year, 1st Semester
        $filteredSubjects = Subject::query()
            ->where('course_id', $course->id)
            ->where('academic_year', 1) // Filter for 1st year
            ->where('semester', 1)      // Filter for 1st semester
            ->get();

        $subjectsData = $filteredSubjects->map(function ($subject) use ($course) {
            // Calculate subject fee (using both lecture and lab units/costs if available)
            $lecFee = ($subject->lecture ?? $subject->units) * ($course->lec_per_unit ?? 0); // Assume units are lecture if lecture field is null
            $labFee = ($subject->laboratory ?? 0) * ($course->lab_per_unit ?? 0);
            $subjectFee = $lecFee + $labFee;
            return [
                'id' => $subject->id,
                'code' => $subject->code,
                'title' => $subject->title,
                'units' => $subject->units,
                'lecture_units' => $subject->lecture ?? $subject->units, // Pass detailed units
                'lab_units' => $subject->laboratory ?? 0,        // Pass detailed units
                'academic_year' => $subject->academic_year,
                'semester' => $subject->semester,
                'fee' => $subjectFee, // Added per-subject fee
            ];
        });

        // Calculate total estimated tuition for the filtered subjects
        $totalEstimatedTuition = $subjectsData->sum('fee');

        return Inertia::render('DashboardGuest', [
            'guestInfo' => $guestInfo,
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'code' => $course->code,
                'description' => $course->description,
                'department' => $course->department,
                'lec_per_unit' => $course->lec_per_unit, // Pass per-unit cost
                'lab_per_unit' => $course->lab_per_unit, // Pass per-unit cost
            ],
            'enrollmentStatus' => $guestEnrollment->status,
            'semester' => $semester,
            'schoolYear' => $schoolYear,
            'subjects' => $subjectsData->toArray(), // Pass filtered subjects with fees
            'totalEstimatedTuition' => $totalEstimatedTuition, // Pass total estimate
        ]);
    }
} 