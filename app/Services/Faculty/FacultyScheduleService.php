<?php

declare(strict_types=1);

namespace App\Services\Faculty;

use App\Models\Faculty;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class FacultyScheduleService
{
    /**
     * Get today's schedule for a faculty member
     */
    public function getTodaysSchedule(Faculty $faculty): Collection
    {
        $today = now()->format('l'); // Full day name (e.g., 'Monday')
        
        return Schedule::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->where('day', $today)
        ->with(['class.subject'])
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
                  ->where('semester', $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->with(['class.subject'])
        ->orderBy('day')
        ->orderBy('start_time')
        ->get()
        ->groupBy('day')
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
                  ->where('semester', $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->where('day', $day)
        ->with(['class.subject'])
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
                  ->where('semester', $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->where('day', $currentDay)
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
                      ->where('semester', $this->getCurrentSemester())
                      ->where('school_year', $this->getCurrentSchoolYear());
            })
            ->where('day', $nextDay)
            ->with(['class.subject'])
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
                  ->where('semester', $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->where('day', $day)
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
                  ->where('semester', $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->with(['class.subject'])
        ->orderBy('day')
        ->orderBy('start_time')
        ->get();

        $conflicts = collect();
        
        foreach ($schedules as $schedule) {
            $overlapping = $schedules->filter(function ($other) use ($schedule) {
                return $other->id !== $schedule->id &&
                       $other->day === $schedule->day &&
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
        
        return [
            'id' => $schedule->id,
            'class_id' => $class->id,
            'subject_code' => $subject ? $subject->code : $class->subject_code,
            'subject_title' => $subject ? $subject->title : 'Unknown Subject',
            'section' => $class->section,
            'room' => $schedule->room,
            'day' => $schedule->day,
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
    private function calculateDuration(string $startTime, string $endTime): string
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
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
        
        if ($schedule->day !== $today) {
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
     * Get current semester
     */
    private function getCurrentSemester(): string
    {
        $month = now()->month;
        
        if ($month >= 6 && $month <= 10) {
            return '1st';
        } elseif ($month >= 11 || $month <= 3) {
            return '2nd';
        } else {
            return 'Summer';
        }
    }

    /**
     * Get current school year
     */
    private function getCurrentSchoolYear(): string
    {
        $year = now()->year;
        $month = now()->month;
        
        if ($month >= 6) {
            return $year . '-' . ($year + 1);
        } else {
            return ($year - 1) . '-' . $year;
        }
    }
}
