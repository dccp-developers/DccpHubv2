<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Classes;
use App\Models\Schedule;
use App\Models\Students;
use App\Models\GeneralSettings;
use Illuminate\Support\Facades\Auth;

final class ScheduleController extends Controller
{
    public function index(): Response
    {
        /** @var User $user */
        $user = Auth::user();

        abort_unless($user->student, 403, 'Only students can access this page.');

        /** @var Students $student */
        $student = $user->student;

        // Get current semester and school year from settings
        $settings = GeneralSettings::query()->first();
        $currentSemester = $settings->semester;
        $currentSchoolYear = $settings->getSchoolYear();

        // Get the student's classes for the current semester and academic year
        $classes = Classes::query()
            ->whereHas('ClassStudents', function ($query) use ($student): void {
                $query->where('student_id', $student->id);
            })
            ->where('semester', $currentSemester)
            ->where('school_year', $currentSchoolYear)
            ->with([
                'Schedule.room',
                'Subject',
                'ShsSubject',
                'Faculty',
                'Room',
            ])
            ->get();

        // Flatten and enhance the schedule data
        $schedules = $classes->flatMap(fn (Classes $class) => $class->Schedule->map(function (Schedule $schedule) use ($class): array {
            // Format the time in a more readable way
            $startTime = $schedule->start_time->format('g:i A');
            $endTime = $schedule->end_time->format('g:i A');

            return [
                'id' => $schedule->id,
                'day_of_week' => mb_strtolower($schedule->day_of_week),
                'time' => "{$startTime} - {$endTime}",
                'start_time' => $startTime,
                'end_time' => $endTime,
                'subject' => $class->subject_title,
                'subject_code' => $class->formated_subject_code,
                'room' => $schedule->room?->name ?? 'N/A',
                'teacher' => $class->faculty_full_name,
                'class_id' => $class->id,
                'section' => $class->section,
                'color' => $this->generateColorForSubject($class->subject_code),
            ];
        }))->sortBy([
            ['day_of_week', 'asc'],
            ['start_time', 'asc'],
        ])->values()->all();

        return Inertia::render('Schedules/Index', [
            'schedules' => $schedules,
            'currentSemester' => $currentSemester,
            'currentSchoolYear' => $currentSchoolYear,
        ]);
    }

    /**
     * Generate a consistent color based on the subject code
     */
    private function generateColorForSubject(string $subjectCode): string
    {
        // List of pleasant, accessible colors
        $colors = [
            'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-100',
            'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-100',
            'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-100',
            'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-100',
            'bg-pink-100 dark:bg-pink-900 text-pink-800 dark:text-pink-100',
            'bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-100',
            'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-100',
            'bg-teal-100 dark:bg-teal-900 text-teal-800 dark:text-teal-100',
        ];

        // Use the hash of the subject code to pick a consistent color
        $hash = crc32($subjectCode);
        $colorIndex = abs($hash) % count($colors);

        return $colors[$colorIndex];
    }
}
