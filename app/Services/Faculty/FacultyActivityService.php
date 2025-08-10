<?php

declare(strict_types=1);

namespace App\Services\Faculty;

use App\Models\Faculty;
use App\Models\class_enrollments;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class FacultyActivityService
{
    /**
     * Get recent activities for a faculty member
     */
    public function getRecentActivities(Faculty $faculty, int $limit = 10): Collection
    {
        $activities = $this->buildActivityCollection($faculty);

        return $activities
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values()
            ->map(fn ($activity) => $this->formatActivity($activity));
    }

    /**
     * Get activities with simple offset pagination for lazy loading in UI
     */
    public function getRecentActivitiesPaginated(
        Faculty $faculty,
        int $offset = 0,
        int $limit = 20,
        ?string $type = null,
        ?int $classId = null,
    ): Collection {
        $activities = $this->buildActivityCollection($faculty)
            ->sortByDesc('timestamp')
            ->values();

        if ($type) {
            $activities = $activities->filter(fn ($a) => ($a['type'] ?? null) === $type);
        }
        if ($classId) {
            $activities = $activities->filter(function ($a) use ($classId) {
                $meta = $a['metadata'] ?? [];
                return ($meta['class_id'] ?? null) == $classId
                    || ($a['class_id'] ?? null) == $classId
                    || (($a['class'] ?? null)['id'] ?? null) == $classId;
            });
        }

        return $activities
            ->slice($offset, $limit)
            ->values()
            ->map(fn ($activity) => $this->formatActivity($activity));
    }

    /**
     * Build the unified activity collection (unformatted)
     */
    private function buildActivityCollection(Faculty $faculty): Collection
    {
        $limit = 100; // cap per source before merge to keep it efficient

        $activities = collect();
        $activities = $activities->merge($this->getRecentGradeSubmissions($faculty, $limit));
        $activities = $activities->merge($this->getRecentClassActivities($faculty, $limit));
        $activities = $activities->merge($this->getRecentEnrollments($faculty, $limit));

        // Include cached/logged activities (optional)
        $cacheKey = "faculty_activities_{$faculty->id}";
        $cached = collect(cache()->get($cacheKey, []));
        $activities = $activities->merge($cached);

        return $activities;
    }

    /**
     * Get activity summary for faculty
     */
    public function getActivitySummary(Faculty $faculty): array
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        return [
            'today' => [
                'grades_submitted' => $this->getGradesSubmittedCount($faculty, $today),
                'classes_taught' => $this->getClassesTaughtCount($faculty, $today),
                'students_interacted' => $this->getStudentsInteractedCount($faculty, $today),
            ],
            'this_week' => [
                'grades_submitted' => $this->getGradesSubmittedCount($faculty, $thisWeek),
                'classes_taught' => $this->getClassesTaughtCount($faculty, $thisWeek),
                'students_interacted' => $this->getStudentsInteractedCount($faculty, $thisWeek),
            ],
            'this_month' => [
                'grades_submitted' => $this->getGradesSubmittedCount($faculty, $thisMonth),
                'classes_taught' => $this->getClassesTaughtCount($faculty, $thisMonth),
                'students_interacted' => $this->getStudentsInteractedCount($faculty, $thisMonth),
            ],
        ];
    }

    /**
     * Log a new activity for faculty
     */
    public function logActivity(Faculty $faculty, string $type, string $description, array $metadata = []): void
    {
        // This would typically insert into an activities table
        // For now, we'll use cache or session to store recent activities
        $cacheKey = "faculty_activities_{$faculty->id}";
        $activities = cache()->get($cacheKey, []);

        $activity = [
            'id' => uniqid(),
            'faculty_id' => $faculty->id,
            'type' => $type,
            'description' => $description,
            'metadata' => $metadata,
            'timestamp' => now()->toISOString(),
            'created_at' => now(),
        ];

        array_unshift($activities, $activity);

        // Keep only the last 50 activities
        $activities = array_slice($activities, 0, 50);

        cache()->put($cacheKey, $activities, 86400); // Cache for 24 hours
    }

    /**
     * Get recent grade submissions
     */
    private function getRecentGradeSubmissions(Faculty $faculty, int $limit): Collection
    {
        return class_enrollments::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id);
        })
        ->where(function ($query) {
            $query->whereNotNull('midterm_grade')
                  ->orWhereNotNull('finals_grade')
                  ->orWhereNotNull('total_average');
        })
        ->where('updated_at', '>=', now()->subDays(30))
        ->with(['class.subject', 'student'])
        ->orderBy('updated_at', 'desc')
        ->limit($limit)
        ->get()
        ->map(function ($enrollment) {
            $class = $enrollment->class;
            $subject = $class->subject;

            $subjectCode = $subject ? $subject->code : $class->subject_code;
            $studentName = $enrollment->student->first_name . ' ' . $enrollment->student->last_name;
            $grade = $enrollment->total_average ?: ($enrollment->finals_grade ?: $enrollment->midterm_grade);

            return [
                'id' => 'grade_' . $enrollment->id,
                'type' => 'grade_submitted',
                'description' => "Grade submitted for {$studentName} in {$subjectCode}",
                'metadata' => [
                    'student_name' => $studentName,
                    'subject_code' => $subjectCode,
                    'class_id' => $class->id,
                    'grade' => $grade,
                ],
                'timestamp' => $enrollment->updated_at,
                'raw_timestamp' => $enrollment->updated_at,
            ];
        });
    }

    /**
     * Get recent class activities
     */
    private function getRecentClassActivities(Faculty $faculty, int $limit): Collection
    {
        return $faculty->classes()
            ->where('updated_at', '>=', now()->subDays(30))
            ->with('subject')
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($class) {
                $subject = $class->subject;
                $subjectCode = $subject ? $subject->code : $class->subject_code;

                return [
                    'id' => 'class_' . $class->id,
                    'type' => 'class_updated',
                    'description' => "Class information updated for {$subjectCode} - Section {$class->section}",
                    'metadata' => [
                        'subject_code' => $subjectCode,
                        'section' => $class->section,
                        'class_id' => $class->id,
                    ],
                    'timestamp' => $class->updated_at,
                    'raw_timestamp' => $class->updated_at,
                ];
            });
    }

    /**
     * Get recent enrollments
     */
    private function getRecentEnrollments(Faculty $faculty, int $limit): Collection
    {
        return class_enrollments::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id);
        })
        ->where('created_at', '>=', now()->subDays(30))
        ->with(['class.subject', 'student'])
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->get()
        ->map(function ($enrollment) {
            $class = $enrollment->class;
            $subject = $class->subject;
            $subjectCode = $subject ? $subject->code : $class->subject_code;
            $studentName = $enrollment->student->first_name . ' ' . $enrollment->student->last_name;

            return [
                'id' => 'enrollment_' . $enrollment->id,
                'type' => 'student_enrolled',
                'description' => "New student enrolled: {$studentName} in {$subjectCode}",
                'metadata' => [
                    'student_name' => $studentName,
                    'subject_code' => $subjectCode,
                    'class_id' => $class->id,
                ],
                'timestamp' => $enrollment->created_at,
                'raw_timestamp' => $enrollment->created_at,
            ];
        });
    }

    /**
     * Format activity for display
     */
    private function formatActivity(array $activity): array
    {
        $timestamp = $activity['raw_timestamp'] ?? $activity['timestamp'];
        $carbonTimestamp = $timestamp instanceof Carbon ? $timestamp : Carbon::parse($timestamp);

        return [
            'id' => $activity['id'],
            'type' => $activity['type'],
            'description' => $activity['description'],
            'metadata' => $activity['metadata'] ?? [],
            'timestamp' => $carbonTimestamp->diffForHumans(),
            'raw_timestamp' => $carbonTimestamp,
            'icon' => $this->getActivityIcon($activity['type']),
            'color' => $this->getActivityColor($activity['type']),
        ];
    }

    /**
     * Get icon for activity type
     */
    private function getActivityIcon(string $type): string
    {
        $icons = [
            'grade_submitted' => 'CheckIcon',
            'class_updated' => 'PencilIcon',
            'student_enrolled' => 'UserPlusIcon',
            'attendance_recorded' => 'ClipboardDocumentListIcon',
            'assignment_created' => 'DocumentTextIcon',
            'schedule_updated' => 'CalendarIcon',
            'announcement_sent' => 'SpeakerWaveIcon',
        ];

        return $icons[$type] ?? 'InformationCircleIcon';
    }

    /**
     * Get color for activity type
     */
    private function getActivityColor(string $type): string
    {
        $colors = [
            'grade_submitted' => 'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400',
            'class_updated' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400',
            'student_enrolled' => 'bg-purple-100 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400',
            'attendance_recorded' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400',
            'assignment_created' => 'bg-pink-100 text-pink-600 dark:bg-pink-900/20 dark:text-pink-400',
            'schedule_updated' => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400',
            'announcement_sent' => 'bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400',
        ];

        return $colors[$type] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-900/20 dark:text-gray-400';
    }

    /**
     * Get grades submitted count for a period
     */
    private function getGradesSubmittedCount(Faculty $faculty, Carbon $since): int
    {
        return class_enrollments::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id);
        })
        ->where(function ($query) {
            $query->whereNotNull('midterm_grade')
                  ->orWhereNotNull('finals_grade')
                  ->orWhereNotNull('total_average');
        })
        ->where('updated_at', '>=', $since)
        ->count();
    }

    /**
     * Get classes taught count for a period
     */
    private function getClassesTaughtCount(Faculty $faculty, Carbon $since): int
    {
        // This would typically come from attendance or session records
        // For now, we'll estimate based on schedule and the since parameter
        return $faculty->classes()
            ->where('semester', $this->getCurrentSemester())
            ->where('school_year', $this->getCurrentSchoolYear())
            ->where('updated_at', '>=', $since)
            ->count();
    }

    /**
     * Get students interacted count for a period
     */
    private function getStudentsInteractedCount(Faculty $faculty, Carbon $since): int
    {
        return class_enrollments::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id);
        })
        ->where('updated_at', '>=', $since)
        ->distinct('student_id')
        ->count('student_id');
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
