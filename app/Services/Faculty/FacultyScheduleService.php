<?php

declare(strict_types=1);

namespace App\Services\Faculty;

use App\Models\Faculty;
use App\Models\Schedule;
use App\Services\GeneralSettingsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Clip;
use HeadlessChromium\Page;

final class FacultyScheduleService
{
    public function __construct(
        private readonly GeneralSettingsService $settingsService
    ) {}
    /**
     * Get today's schedule for a faculty member
     */
    public function getTodaysSchedule(Faculty $faculty): array
    {
        $today = now()->format('l'); // Full day name (e.g., 'Monday')

        return Schedule::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->where('day_of_week', $today)
        ->with(['class.subject', 'class.ShsSubject', 'class.ClassStudents', 'room'])
        ->orderBy('start_time')
        ->get()
        ->map(function ($schedule) {
            return $this->formatScheduleItem($schedule);
        })
        ->toArray();
    }

    /**
     * Get weekly schedule for a faculty member
     */
    public function getWeeklySchedule(Faculty $faculty): array
    {
        $schedules = Schedule::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->with(['class.subject', 'class.ShsSubject', 'class.ClassStudents', 'room'])
        ->orderBy('day_of_week')
        ->orderBy('start_time')
        ->get()
        ->groupBy('day_of_week')
        ->map(function ($daySchedules) {
            return $daySchedules->map(function ($schedule) {
                return $this->formatScheduleItem($schedule);
            })->values();
        });

        // Convert to array and ensure all days are present
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $weeklySchedule = [];

        foreach ($daysOfWeek as $day) {
            $weeklySchedule[$day] = $schedules->get($day, collect())->toArray();
        }

        return $weeklySchedule;
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
        ->with(['class.subject', 'class.ShsSubject', 'class.ClassStudents', 'room'])
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
        ->with(['class.subject', 'class.ShsSubject', 'class.ClassStudents', 'room'])
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
            ->with(['class.subject', 'class.ShsSubject', 'class.ClassStudents', 'room'])
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
        ->with(['class.subject', 'class.ShsSubject', 'room'])
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
        // Get the appropriate subject based on classification
        $subject = $class->classification === 'shs' ? $class->ShsSubject : $class->subject;
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
            'student_count' => $class->ClassStudents ? $class->ClassStudents->count() : 0,
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
        ->with(['class.subject', 'class.ShsSubject', 'class.ClassStudents', 'room'])
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

    /**
     * Get all schedule data for the index page
     */
    public function getScheduleIndexData(Faculty $faculty, array $filters = []): array
    {
        // Get all schedule data
        $weeklySchedule = $this->getWeeklySchedule($faculty);
        $todaysSchedule = $this->getTodaysSchedule($faculty);
        $scheduleOverview = $this->getScheduleOverview($faculty);
        $scheduleStats = $this->getScheduleStats($faculty);

        // Get filter options
        $filterOptions = $this->getFilterOptions($faculty);

        // Process filters
        $processedFilters = [
            'view' => $filters['view'] ?? 'week',
            'date' => $filters['date'] ?? now()->format('Y-m-d'),
            'subject' => $filters['subject'] ?? '',
            'room' => $filters['room'] ?? '',
        ];

        return [
            'weeklySchedule' => $weeklySchedule,
            'todaysSchedule' => $todaysSchedule,
            'scheduleOverview' => $scheduleOverview,
            'scheduleStats' => $scheduleStats,
            'filters' => $processedFilters,
            'filterOptions' => $filterOptions,
            'currentSemester' => (string) $this->getCurrentSemester(),
            'schoolYear' => $this->getCurrentSchoolYear(),
            'availableSemesters' => $this->settingsService->getAvailableSemesters(),
            'availableSchoolYears' => $this->settingsService->getAvailableSchoolYears(),
        ];
    }

    /**
     * Get filter options for the schedule
     */
    public function getFilterOptions(Faculty $faculty): array
    {
        // Get unique subjects taught by this faculty
        $subjects = $faculty->classes()
            ->where('semester', (string) $this->getCurrentSemester())
            ->where('school_year', $this->getCurrentSchoolYear())
            ->with(['subject', 'ShsSubject'])
            ->get()
            ->map(function ($class) {
                $subject = $class->classification === 'shs' ? $class->ShsSubject : $class->subject;
                return [
                    'id' => $subject ? $subject->id : null,
                    'code' => $subject ? $subject->code : $class->subject_code,
                    'title' => $subject ? $subject->title : 'Unknown Subject',
                ];
            })
            ->filter(fn($subject) => $subject['id'] !== null)
            ->unique('id')
            ->values();

        // Get unique rooms
        $rooms = $faculty->classes()
            ->where('semester', (string) $this->getCurrentSemester())
            ->where('school_year', $this->getCurrentSchoolYear())
            ->whereHas('schedules')
            ->with(['schedules.room'])
            ->get()
            ->flatMap(fn($class) => $class->schedules)
            ->map(fn($schedule) => $schedule->room)
            ->filter()
            ->unique('id')
            ->map(fn($room) => [
                'id' => $room->id,
                'name' => $room->name,
                'class_code' => $room->class_code ?? 'N/A',
            ])
            ->values();

        return [
            'subjects' => $subjects,
            'rooms' => $rooms,
            'viewOptions' => [
                'day' => 'Day View',
                'week' => 'Week View',
                'month' => 'Month View',
            ],
        ];
    }

    /**
     * Get schedule overview statistics
     */
    public function getScheduleOverview(Faculty $faculty): array
    {
        $schedules = Schedule::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->with(['class.subject', 'class.ShsSubject', 'room'])
        ->get();

        $totalClasses = $schedules->count();
        $totalHours = $schedules->sum(function ($schedule) {
            return Carbon::parse($schedule->start_time)->diffInMinutes(Carbon::parse($schedule->end_time)) / 60;
        });

        $daysWithClasses = $schedules->pluck('day_of_week')->unique()->count();
        $averageClassesPerDay = $daysWithClasses > 0 ? round($totalClasses / $daysWithClasses, 1) : 0;

        return [
            'total_classes' => $totalClasses,
            'total_hours' => round($totalHours, 1),
            'days_with_classes' => $daysWithClasses,
            'average_classes_per_day' => $averageClassesPerDay,
            'busiest_day' => $this->getBusiestDay($schedules),
            'next_class' => $this->getNextClass($faculty),
        ];
    }

    /**
     * Get schedule statistics
     */
    public function getScheduleStats(Faculty $faculty): array
    {
        $schedules = Schedule::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->with(['class.subject', 'class.ShsSubject', 'class.ClassStudents', 'room'])
        ->get();

        $classesByDay = $schedules->groupBy('day_of_week');
        $totalStudents = $schedules->sum(function ($schedule) {
            return $schedule->class->ClassStudents->count();
        });

        return [
            'total_classes' => $schedules->count(),
            'total_students' => $totalStudents,
            'classes_by_day' => $classesByDay->map->count(),
            'peak_hours' => $this->getPeakHours($schedules),
            'room_utilization' => $this->getRoomUtilization($schedules),
        ];
    }

    /**
     * Get schedule details for a specific schedule
     */
    public function getScheduleDetails(Faculty $faculty, int $scheduleId): ?array
    {
        $schedule = Schedule::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })
        ->where('id', $scheduleId)
        ->with([
            'class.subject',
            'class.ShsSubject',
            'class.ClassStudents.student',
            'room'
        ])
        ->first();

        if (!$schedule) {
            return null;
        }

        $formattedSchedule = $this->formatScheduleItem($schedule);
        $formattedSchedule['students'] = $schedule->class->ClassStudents->map(function ($enrollment) {
            return [
                'id' => $enrollment->student->id,
                'name' => $enrollment->student->first_name . ' ' . $enrollment->student->last_name,
                'student_id' => $enrollment->student->student_id,
                'email' => $enrollment->student->email,
            ];
        });

        return $formattedSchedule;
    }

    /**
     * Export schedule data
     */
    public function exportSchedule(Faculty $faculty, string $format, string $period): array
    {
        $schedules = $this->getScheduleData($faculty, $period);
        $facultyName = $faculty->first_name . ' ' . $faculty->last_name;

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "schedule_{$facultyName}_{$period}_{$timestamp}";

        // Create directory if it doesn't exist and cleanup old files
        $exportDir = storage_path('app/public/exports');
        if (!file_exists($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        // Cleanup old export files (older than 1 hour)
        $this->cleanupOldExports($exportDir);

        switch ($format) {
            case 'pdf':
                return $this->exportToPDF($schedules, $facultyName, $period, $filename);
            case 'excel':
            case 'csv':
                return $this->exportToCSV($schedules, $facultyName, $period, $filename, $format);
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    private function getScheduleData(Faculty $faculty, string $period): array
    {
        switch ($period) {
            case 'week':
                return $this->getWeeklySchedule($faculty);
            case 'month':
                // For now, return weekly schedule - can be extended for monthly view
                return $this->getWeeklySchedule($faculty);
            case 'semester':
                return $this->getWeeklySchedule($faculty);
            default:
                return $this->getWeeklySchedule($faculty);
        }
    }

    private function exportToPDF(array $schedules, string $facultyName, string $period, string $filename): array
    {
        $html = $this->generateScheduleHTML($schedules, $facultyName, $period);
        $pdfPath = storage_path("app/public/exports/{$filename}.pdf");

        // Ensure directory exists
        $dir = dirname($pdfPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        try {
            // Validate HTML content
            if (empty($html) || strlen($html) < 100) {
                throw new \Exception("HTML content is empty or too short for PDF generation");
            }

            // Create browser factory with Chrome path and options
            $browserFactory = new BrowserFactory('/usr/bin/google-chrome-stable');

            // Start the browser with additional options for better compatibility
            $browser = $browserFactory->createBrowser([
                'headless' => true,
                'noSandbox' => true,
                'disableGpu' => true,
                'disableDevShmUsage' => true,
                'disableExtensions' => true,
                'disablePlugins' => true,
                'disableImagesLoading' => false,
                'windowSize' => [1200, 800],
                'userAgent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'customFlags' => [
                    '--disable-web-security',
                    '--disable-features=VizDisplayCompositor',
                    '--run-all-compositor-stages-before-draw',
                    '--disable-background-timer-throttling',
                    '--disable-renderer-backgrounding',
                    '--disable-backgrounding-occluded-windows',
                    '--disable-ipc-flooding-protection'
                ]
            ]);

            // Create a page
            $page = $browser->createPage();

            // Set viewport
            $page->setViewport(1200, 800);

            // Set the HTML content
            $page->setHtml($html);

            // Wait for content to render
            usleep(1000000); // 1 second for better rendering

            // Generate PDF with correct options
            $pdf = $page->pdf([
                'printBackground' => true,
                'marginTop' => 0.5,
                'marginBottom' => 0.5,
                'marginLeft' => 0.5,
                'marginRight' => 0.5,
                'displayHeaderFooter' => false,
                'preferCSSPageSize' => false,
                'landscape' => false
            ]);

            // Get PDF content and validate
            $pdfContent = $pdf->getBase64();
            if (empty($pdfContent)) {
                throw new \Exception("PDF generation returned empty content");
            }

            // Decode and save PDF to storage
            $decodedPdf = base64_decode($pdfContent);
            if ($decodedPdf === false) {
                throw new \Exception("Failed to decode PDF base64 content");
            }

            // Validate PDF content
            if (strlen($decodedPdf) < 1000) {
                throw new \Exception("Generated PDF is too small, likely corrupted");
            }

            // Check PDF signature
            if (substr($decodedPdf, 0, 4) !== '%PDF') {
                throw new \Exception("Generated file is not a valid PDF");
            }

            // Save to file
            $bytesWritten = file_put_contents($pdfPath, $decodedPdf);
            if ($bytesWritten === false) {
                throw new \Exception("Failed to write PDF file to storage");
            }

            // Close browser
            $browser->close();

            // Return storage URL
            $storageUrl = asset("storage/exports/{$filename}.pdf");

            return [
                'path' => $pdfPath,
                'filename' => "{$filename}.pdf",
                'url' => $storageUrl,
            ];

        } catch (\Exception $e) {
            // Close browser if it was opened
            if (isset($browser)) {
                try {
                    $browser->close();
                } catch (\Exception $closeException) {
                    // Ignore close errors
                }
            }
            throw new \Exception("Failed to generate PDF: " . $e->getMessage());
        }
    }

    private function exportToCSV(array $schedules, string $facultyName, string $period, string $filename, string $format): array
    {
        $csvPath = storage_path("app/public/exports/{$filename}.csv");

        // Ensure directory exists
        $dir = dirname($csvPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Create CSV file
        $output = fopen($csvPath, 'w');

        if ($output === false) {
            throw new \Exception("Failed to create CSV file");
        }

        // Add header
        fputcsv($output, [
            'Faculty Name: ' . $facultyName,
            'Period: ' . ucfirst($period),
            'Generated: ' . now()->format('Y-m-d H:i:s')
        ]);

        fputcsv($output, []); // Empty row

        // Add column headers
        fputcsv($output, [
            'Day',
            'Subject Code',
            'Subject Title',
            'Start Time',
            'End Time',
            'Duration',
            'Room',
            'Section',
            'Students'
        ]);

        // Add schedule data
        foreach ($schedules as $day => $daySchedules) {
            if (empty($daySchedules)) {
                fputcsv($output, [$day, 'No classes scheduled', '', '', '', '', '', '', '']);
                continue;
            }

            foreach ($daySchedules as $schedule) {
                fputcsv($output, [
                    $day,
                    $schedule['subject_code'] ?? '',
                    $schedule['subject_title'] ?? '',
                    $schedule['start_time'] ?? '',
                    $schedule['end_time'] ?? '',
                    $schedule['duration'] ?? '',
                    $schedule['room'] ?? '',
                    $schedule['section'] ?? '',
                    $schedule['student_count'] ?? ''
                ]);
            }
        }

        fclose($output);

        // Return storage URL
        $storageUrl = asset("storage/exports/{$filename}.csv");

        return [
            'path' => $csvPath,
            'filename' => "{$filename}.csv",
            'url' => $storageUrl,
        ];
    }

    private function generateScheduleHTML(array $schedules, string $facultyName, string $period): string
    {
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Schedule - {$facultyName}</title>
            <style>
                @page {
                    size: A4;
                    margin: 0.5in;
                }
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    margin: 0;
                    padding: 20px;
                    font-size: 12px;
                    line-height: 1.4;
                    color: #333;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 2px solid #007bff;
                    padding-bottom: 20px;
                }
                .header h1 {
                    color: #007bff;
                    margin: 0 0 10px 0;
                    font-size: 24px;
                }
                .header h2 {
                    color: #333;
                    margin: 0 0 10px 0;
                    font-size: 18px;
                    font-weight: normal;
                }
                .header p {
                    color: #666;
                    margin: 0;
                    font-size: 11px;
                }
                .schedule-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                    font-size: 11px;
                }
                .schedule-table th, .schedule-table td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                    vertical-align: top;
                }
                .schedule-table th {
                    background-color: #007bff;
                    color: white;
                    font-weight: bold;
                    font-size: 10px;
                    text-transform: uppercase;
                }
                .day-header {
                    background-color: #e3f2fd;
                    font-weight: bold;
                    color: #1976d2;
                }
                .no-classes {
                    color: #999;
                    font-style: italic;
                    text-align: center;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    color: #666;
                    font-size: 10px;
                    border-top: 1px solid #ddd;
                    padding-top: 15px;
                }
                .time-cell {
                    font-weight: bold;
                    color: #007bff;
                }
                .subject-code {
                    font-weight: bold;
                    color: #333;
                }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Faculty Schedule</h1>
                <h2>{$facultyName}</h2>
                <p>Period: " . ucfirst($period) . " | Generated: " . now()->format('F j, Y g:i A') . "</p>
            </div>

            <table class='schedule-table'>
                <thead>
                    <tr>
                        <th style='width: 12%;'>Day</th>
                        <th style='width: 15%;'>Subject Code</th>
                        <th style='width: 25%;'>Subject Title</th>
                        <th style='width: 18%;'>Time</th>
                        <th style='width: 10%;'>Duration</th>
                        <th style='width: 10%;'>Room</th>
                        <th style='width: 10%;'>Section</th>
                    </tr>
                </thead>
                <tbody>";

        foreach ($schedules as $day => $daySchedules) {
            if (empty($daySchedules)) {
                $html .= "
                    <tr>
                        <td class='day-header'>{$day}</td>
                        <td colspan='6' class='no-classes'>No classes scheduled</td>
                    </tr>";
                continue;
            }

            foreach ($daySchedules as $index => $schedule) {
                $dayCell = $index === 0 ? "<td class='day-header' rowspan='" . count($daySchedules) . "'>{$day}</td>" : '';
                $html .= "
                    <tr>
                        {$dayCell}
                        <td class='subject-code'>" . ($schedule['subject_code'] ?? '') . "</td>
                        <td>" . ($schedule['subject_title'] ?? '') . "</td>
                        <td class='time-cell'>" . ($schedule['start_time'] ?? '') . " - " . ($schedule['end_time'] ?? '') . "</td>
                        <td>" . ($schedule['duration'] ?? '') . "</td>
                        <td>" . ($schedule['room'] ?? '') . "</td>
                        <td>" . ($schedule['section'] ?? '') . "</td>
                    </tr>";
            }
        }

        $html .= "
                </tbody>
            </table>

            <div class='footer'>
                <p>Generated by Faculty Schedule System</p>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Get the busiest day of the week
     */
    private function getBusiestDay(Collection $schedules): string
    {
        $classesByDay = $schedules->groupBy('day_of_week');
        $busiestDay = $classesByDay->sortByDesc(function ($daySchedules) {
            return $daySchedules->count();
        })->keys()->first();

        return $busiestDay ?? 'Monday';
    }

    /**
     * Get peak hours when most classes occur
     */
    private function getPeakHours(Collection $schedules): array
    {
        $hourCounts = [];

        foreach ($schedules as $schedule) {
            $startHour = Carbon::parse($schedule->start_time)->hour;
            $endHour = Carbon::parse($schedule->end_time)->hour;

            for ($hour = $startHour; $hour <= $endHour; $hour++) {
                $hourCounts[$hour] = ($hourCounts[$hour] ?? 0) + 1;
            }
        }

        arsort($hourCounts);
        return array_slice($hourCounts, 0, 3, true);
    }

    /**
     * Get room utilization statistics
     */
    private function getRoomUtilization(Collection $schedules): array
    {
        $roomUsage = $schedules->groupBy('room_id')->map(function ($roomSchedules) {
            $room = $roomSchedules->first()->room;
            return [
                'room_name' => $room ? $room->name : 'TBA',
                'usage_count' => $roomSchedules->count(),
                'total_hours' => $roomSchedules->sum(function ($schedule) {
                    return Carbon::parse($schedule->start_time)->diffInMinutes(Carbon::parse($schedule->end_time)) / 60;
                }),
            ];
        });

        return $roomUsage->sortByDesc('usage_count')->values()->toArray();
    }

    /**
     * Cleanup old export files
     */
    private function cleanupOldExports(string $exportDir): void
    {
        try {
            $files = glob($exportDir . '/*');
            $oneHourAgo = time() - 3600; // 1 hour ago

            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $oneHourAgo) {
                    unlink($file);
                }
            }
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
    }
}
