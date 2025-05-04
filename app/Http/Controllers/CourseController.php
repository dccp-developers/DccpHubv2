<?php

namespace App\Http\Controllers;

use App\Models\Courses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    /**
     * Get course details including subjects and unit costs.
     *
     * @param  \App\Models\Courses $course
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetails(Courses $course)
    {
        try {
            // Eager load subjects to avoid N+1 queries
            $course->load('Subjects');

            // Prepare subjects data (selecting only needed fields)
            $subjects = $course->Subjects->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'code' => $subject->code,
                    'title' => $subject->title,
                    'units' => $subject->units,
                    'academic_year' => $subject->academic_year,
                    'semester' => $subject->semester,
                    // Add other relevant subject fields if needed
                ];
            });

            // Calculate total units
            $totalUnits = $course->Subjects->sum('units');

            // Calculate estimated tuition (assuming lec_per_unit is the primary cost factor)
            // You might have more complex calculations involving lab units, miscellaneous fees, etc.
            $estimatedTuition = $totalUnits * ($course->lec_per_unit ?? 0); // Use null coalescing operator

            return response()->json([
                'id' => $course->id,
                'code' => $course->code,
                'title' => $course->title,
                'lec_per_unit' => $course->lec_per_unit,
                'lab_per_unit' => $course->lab_per_unit, // Include if needed for calculation
                'subjects' => $subjects,
                'total_units' => $totalUnits,
                'estimated_tuition' => $estimatedTuition,
            ]);

        } catch (\Exception $e) {
            Log::error("Error fetching course details for course ID {$course->id}: " . $e->getMessage());
            return response()->json(['message' => 'Error fetching course details.'], 500);
        }
    }
}
