<?php

declare(strict_types=1);

namespace App\Services\Faculty;

use App\Models\Faculty;
use App\Models\Schedule;
use App\Services\GeneralSettingsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class FacultyScheduleService
{
    public function __construct(
        private readonly GeneralSettingsService $settingsService
    ) {}
    /**
     * Get today's schedule for a faculty member
     */
    public function getTodaysSchedule(Faculty $faculty): Collection
    {
        $today = now()->format('l'); // Full day name (e.g., 'Monday')

        return Schedule::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->where('day_of_week', $today)
        ->with(['class.subject', 'room'])
        ->orderBy('start_time')
        ->get()
        ->map(function ($schedule) {
            return $this->formatScheduleItem($schedule);
        });
    }

    /**
     * Get weekly schedule for a faculty member
     */
    public function getWeeklySchedule(Faculty $faculty): Collection
    {
        return Schedule::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->with(['class.subject', 'room'])
        ->orderBy('day_of_week')
        ->orderBy('start_time')
        ->get()
        ->groupBy('day_of_week')
        ->map(function ($daySchedules) {
            return $daySchedules->map(function ($schedule) {
                return $this->formatScheduleItem($schedule);
            });
        });
    }

    /**
     * Get schedule for a specific day
     */
    public function getScheduleForDay(Faculty $faculty, string $day): Collection
    {
        return Schedule::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->where('day_of_week', $day)
        ->with(['class.subject', 'room'])
        ->orderBy('start_time')
        ->get()
        ->map(function ($schedule) {
            return $this->formatScheduleItem($schedule);
        });
    }

    /**
     * Get next upcoming class for faculty
     */
    public function getNextClass(Faculty $faculty): ?array
    {
        $now = now();
        $currentTime = $now->format('H:i:s');
        $currentDay = $now->format('l');
        
        // First, try to find a class later today
        $nextClass = Schedule::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->where('day_of_week', $currentDay)
        ->where('start_time', '>', $currentTime)
        ->with(['class.subject'])
        ->orderBy('start_time')
        ->first();

        if ($nextClass) {
            return $this->formatScheduleItem($nextClass);
        }

        // If no class today, find the next class in the week
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $currentDayIndex = array_search($currentDay, $daysOfWeek);
        
        for ($i = 1; $i <= 7; $i++) {
            $nextDayIndex = ($currentDayIndex + $i) % 7;
            $nextDay = $daysOfWeek[$nextDayIndex];
            
            $nextClass = Schedule::whereHas('class', function ($query) use ($faculty) {
                $query->where('faculty_id', $faculty->id)
                      ->where('semester', (string) $this->getCurrentSemester())
                      ->where('school_year', $this->getCurrentSchoolYear());
            })
            ->where('day_of_week', $nextDay)
            ->with(['class.subject', 'room'])
            ->orderBy('start_time')
            ->first();

            if ($nextClass) {
                return $this->formatScheduleItem($nextClass);
            }
        }

        return null;
    }

    /**
     * Check if faculty has a class at a specific time
     */
    public function hasClassAt(Faculty $faculty, string $day, string $time): bool
    {
        return Schedule::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->where('day_of_week', $day)
        ->where('start_time', '<=', $time)
        ->where('end_time', '>=', $time)
        ->exists();
    }

    /**
     * Get schedule conflicts for faculty
     */
    public function getScheduleConflicts(Faculty $faculty): Collection
    {
        $schedules = Schedule::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->with(['class.subject', 'room'])
        ->orderBy('day_of_week')
        ->orderBy('start_time')
        ->get();

        $conflicts = collect();
        
        foreach ($schedules as $schedule) {
            $overlapping = $schedules->filter(function ($other) use ($schedule) {
                return $other->id !== $schedule->id &&
                       $other->day_of_week === $schedule->day_of_week &&
                       $this->timesOverlap(
                           $schedule->start_time, $schedule->end_time,
                           $other->start_time, $other->end_time
                       );
            });

            if ($overlapping->isNotEmpty()) {
                $conflicts->push([
                    'schedule' => $this->formatScheduleItem($schedule),
                    'conflicts_with' => $overlapping->map(function ($conflict) {
                        return $this->formatScheduleItem($conflict);
                    })->toArray()
                ]);
            }
        }

        return $conflicts;
    }

    /**
     * Format schedule item for display
     */
    private function formatScheduleItem(Schedule $schedule): array
    {
        $class = $schedule->class;
        $subject = $class->subject ?? null;
        $room = $schedule->room;

        return [
            'id' => $schedule->id,
            'class_id' => $class->id,
            'subject_code' => $subject ? $subject->code : $class->subject_code,
            'subject_title' => $subject ? $subject->title : 'Unknown Subject',
            'section' => $class->section,
            'room' => $room ? $room->name : 'TBA',
            'room_id' => $schedule->room_id,
            'day' => $schedule->day_of_week,
            'day_of_week' => $schedule->day_of_week,
            'start_time' => Carbon::parse($schedule->start_time)->format('g:i A'),
            'end_time' => Carbon::parse($schedule->end_time)->format('g:i A'),
            'raw_start_time' => $schedule->start_time,
            'raw_end_time' => $schedule->end_time,
            'duration' => $this->calculateDuration($schedule->start_time, $schedule->end_time),
            'color' => $this->getSubjectColor($subject ? $subject->code : $class->subject_code),
            'status' => $this->getClassStatus($schedule),
            'student_count' => $class->ClassStudents()->count(),
        ];
    }

    /**
     * Calculate duration between two times
     */
    private function calculateDuration($startTime, $endTime): string
    {
        $start = $startTime instanceof Carbon ? $startTime : Carbon::parse($startTime);
        $end = $endTime instanceof Carbon ? $endTime : Carbon::parse($endTime);
        $duration = $start->diffInMinutes($end);
        
        $hours = intval($duration / 60);
        $minutes = $duration % 60;
        
        if ($hours > 0) {
            return $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'm' : '');
        }
        
        return $minutes . 'm';
    }

    /**
     * Get color for subject based on subject code
     */
    private function getSubjectColor(string $subjectCode): string
    {
        $colors = [
            'CS' => 'blue-500',
            'IT' => 'green-500',
            'MATH' => 'purple-500',
            'ENG' => 'red-500',
            'SCI' => 'yellow-500',
            'PE' => 'pink-500',
            'NSTP' => 'indigo-500',
        ];

        $prefix = substr($subjectCode, 0, 2);
        return $colors[$prefix] ?? 'gray-500';
    }

    /**
     * Get current status of a class
     */
    private function getClassStatus(Schedule $schedule): string
    {
        $now = now();
        $today = $now->format('l');
        $currentTime = $now->format('H:i:s');
        
        if ($schedule->day_of_week !== $today) {
            return 'scheduled';
        }
        
        if ($currentTime < $schedule->start_time) {
            return 'upcoming';
        } elseif ($currentTime >= $schedule->start_time && $currentTime <= $schedule->end_time) {
            return 'ongoing';
        } else {
            return 'completed';
        }
    }

    /**
     * Check if two time ranges overlap
     */
    private function timesOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        return $start1 < $end2 && $start2 < $end1;
    }

    /**
     * Get schedules for a specific class
     */
    public function getClassSchedules(Faculty $faculty, int $classId): Collection
    {
        return Schedule::whereHas('class', function ($query) use ($faculty, $classId) {
            $query->where('faculty_id', $faculty->id)
                  ->where('id', $classId)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->with(['class.subject', 'room'])
        ->orderBy('day_of_week')
        ->orderBy('start_time')
        ->get()
        ->map(function ($schedule) {
            return $this->formatScheduleItem($schedule);
        });
    }

    /**
     * Get current semester using GeneralSettingsService
     */
    private function getCurrentSemester(): int
    {
        return $this->settingsService->getCurrentSemester();
    }

    /**
     * Get current school year using GeneralSettingsService
     */
    private function getCurrentSchoolYear(): string
    {
        return $this->settingsService->getCurrentSchoolYearString();
    }
}
