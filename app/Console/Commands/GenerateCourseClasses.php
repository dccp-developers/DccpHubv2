<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Courses;
use App\Models\Subject;
use App\Models\Classes;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;
use App\Models\GeneralSettings;
use App\Models\rooms;

class GenerateCourseClasses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:course-classes {course_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate classes and schedules for all subjects of a given course for the current semester, with conflict detection and automatic rearrangement.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $courseId = (int) $this->argument('course_id');

        // Get current semester and school year
        $settings = GeneralSettings::query()->first();
        $currentSemester = $settings->semester;
        $currentSchoolYear = $settings->getSchoolYearString();

        // Fetch all subjects for the course and current semester
        $subjects = \App\Models\Subject::where('course_id', $courseId)
            ->where('semester', $currentSemester)
            ->get();

        $this->info("Found {$subjects->count()} subjects for course ID {$courseId} in semester {$currentSemester}.");

        // Fetch all available rooms and randomize
        $rooms = rooms::all()->shuffle();
        if ($rooms->isEmpty()) {
            $this->error('No rooms available. Aborting.');
            return;
        }

        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $startTimes = [];
        for ($h = 8; $h <= 16; $h++) { // 16:00 is the last start for 1.5h class
            $startTimes[] = sprintf('%02d:00', $h);
        }

        foreach ($subjects as $subject) {
            // Create a Classes record (no faculty assigned)
            $class = Classes::create([
                'subject_code' => $subject->code,
                'academic_year' => $subject->academic_year,
                'semester' => $subject->semester,
                'school_year' => $currentSchoolYear,
                'section' => 'A', // Default section, can be customized
            ]);

            // Randomly assign a room for scheduling
            $room = $rooms->random();

            // Randomly choose duration: 60 or 90 minutes
            $duration = [60, 90][array_rand([60, 90])];

            $slotFound = false;
            foreach ($daysOfWeek as $day) {
                foreach ($startTimes as $start) {
                    $startTime = \Carbon\Carbon::createFromFormat('H:i', $start);
                    $endTime = (clone $startTime)->addMinutes($duration);
                    if ($endTime->hour > 18 || ($endTime->hour == 18 && $endTime->minute > 0)) {
                        continue; // Ends after 18:00
                    }

                    // Check for conflicts in this room
                    $conflict = Schedule::where('room_id', $room->id)
                        ->where('day_of_week', $day)
                        ->where(function ($q) use ($startTime, $endTime) {
                            $q->where(function ($q2) use ($startTime, $endTime) {
                                $q2->where('start_time', '<', $endTime->format('H:i:s'))
                                   ->where('end_time', '>', $startTime->format('H:i:s'));
                            });
                        })
                        ->exists();

                    if (!$conflict) {
                        // Assign schedule
                        Schedule::create([
                            'day_of_week' => $day,
                            'start_time' => $startTime->format('H:i:s'),
                            'end_time' => $endTime->format('H:i:s'),
                            'room_id' => $room->id,
                            'class_id' => $class->id,
                        ]);
                        $this->info("Scheduled subject {$subject->code} in room {$room->name} on {$day} {$startTime->format('H:i')} - {$endTime->format('H:i')}");
                        $slotFound = true;
                        break 2;
                    }
                }
            }
            if (!$slotFound) {
                $this->warn("No available slot found for subject {$subject->code}. Consider adding more rooms or time slots.");
            }
        }

        $this->info('Class and schedule generation complete.');
    }
} 