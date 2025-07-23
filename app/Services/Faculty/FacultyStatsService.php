<?php

declare(strict_types=1);

namespace App\Services\Faculty;

use App\Models\Faculty;
use App\Models\Classes;
use App\Models\Schedule;
use App\Models\class_enrollments;
use App\Services\GeneralSettingsService;
use Illuminate\Support\Facades\DB;

final class FacultyStatsService
{
    public function __construct(
        private readonly GeneralSettingsService $settingsService
    ) {}
    /**
     * Get comprehensive statistics for a faculty member
     */
    public function getStats(Faculty $faculty): array
    {
        return [
            [
                'label' => 'Total Classes',
                'value' => $this->getTotalClasses($faculty),
                'description' => 'Classes you are teaching this semester',
                'color' => 'blue',
                'trend' => $this->getClassesTrend($faculty),
                'icon' => 'AcademicCapIcon'
            ],
            [
                'label' => 'Total Students',
                'value' => $this->getTotalStudents($faculty),
                'description' => 'Students enrolled in your classes',
                'color' => 'green',
                'trend' => $this->getStudentsTrend($faculty),
                'icon' => 'UsersIcon'
            ],
            [
                'label' => 'Weekly Schedules',
                'value' => $this->getWeeklySchedules($faculty),
                'description' => 'Your weekly class sessions',
                'color' => 'purple',
                'trend' => $this->getSchedulesTrend($faculty),
                'icon' => 'CalendarIcon'
            ],
            [
                'label' => 'Avg. Class Size',
                'value' => $this->getAverageClassSize($faculty),
                'description' => 'Average students per class',
                'color' => 'amber',
                'trend' => $this->getClassSizeTrend($faculty),
                'icon' => 'ChartBarIcon'
            ]
        ];
    }

    /**
     * Get total number of classes for faculty
     */
    public function getTotalClasses(Faculty $faculty): int
    {
        return $faculty->classes()
            ->where('semester', (string) $this->getCurrentSemester())
            ->where('school_year', $this->getCurrentSchoolYear())
            ->count();
    }

    /**
     * Get total number of students across all faculty classes
     */
    public function getTotalStudents(Faculty $faculty): int
    {
        return class_enrollments::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })->count();
    }

    /**
     * Get total weekly schedule sessions
     */
    public function getWeeklySchedules(Faculty $faculty): int
    {
        return Schedule::whereHas('class', function ($query) use ($faculty) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', (string) $this->getCurrentSemester())
                  ->where('school_year', $this->getCurrentSchoolYear());
        })->count();
    }

    /**
     * Get average class size
     */
    public function getAverageClassSize(Faculty $faculty): int
    {
        $classes = $faculty->classes()
            ->where('semester', (string) $this->getCurrentSemester())
            ->where('school_year', $this->getCurrentSchoolYear())
            ->withCount('ClassStudents')
            ->get();

        if ($classes->isEmpty()) {
            return 0;
        }

        $totalStudents = $classes->sum('class_students_count');
        return (int) round($totalStudents / $classes->count());
    }

    /**
     * Get trend for classes compared to previous semester
     */
    private function getClassesTrend(Faculty $faculty): string
    {
        $currentClasses = $this->getTotalClasses($faculty);
        $previousClasses = $this->getPreviousSemesterClasses($faculty);
        
        if ($previousClasses === 0) {
            return $currentClasses > 0 ? '+100%' : '0%';
        }
        
        $change = (($currentClasses - $previousClasses) / $previousClasses) * 100;
        return ($change >= 0 ? '+' : '') . round($change, 1) . '%';
    }

    /**
     * Get trend for students compared to previous semester
     */
    private function getStudentsTrend(Faculty $faculty): string
    {
        $currentStudents = $this->getTotalStudents($faculty);
        $previousStudents = $this->getPreviousSemesterStudents($faculty);
        
        if ($previousStudents === 0) {
            return $currentStudents > 0 ? '+100%' : '0%';
        }
        
        $change = (($currentStudents - $previousStudents) / $previousStudents) * 100;
        return ($change >= 0 ? '+' : '') . round($change, 1) . '%';
    }

    /**
     * Get trend for schedules compared to previous semester
     */
    private function getSchedulesTrend(Faculty $faculty): string
    {
        $currentSchedules = $this->getWeeklySchedules($faculty);
        $previousSchedules = $this->getPreviousSemesterSchedules($faculty);
        
        if ($previousSchedules === 0) {
            return $currentSchedules > 0 ? '+100%' : '0%';
        }
        
        $change = (($currentSchedules - $previousSchedules) / $previousSchedules) * 100;
        return ($change >= 0 ? '+' : '') . round($change, 1) . '%';
    }

    /**
     * Get trend for class size compared to previous semester
     */
    private function getClassSizeTrend(Faculty $faculty): string
    {
        $currentSize = $this->getAverageClassSize($faculty);
        $previousSize = $this->getPreviousSemesterAverageClassSize($faculty);
        
        if ($previousSize === 0) {
            return $currentSize > 0 ? '+100%' : '0%';
        }
        
        $change = (($currentSize - $previousSize) / $previousSize) * 100;
        return ($change >= 0 ? '+' : '') . round($change, 1) . '%';
    }

    /**
     * Get previous semester classes count
     */
    private function getPreviousSemesterClasses(Faculty $faculty): int
    {
        [$prevSemester, $prevYear] = $this->getPreviousSemesterInfo();
        
        return $faculty->classes()
            ->where('semester', $prevSemester)
            ->where('school_year', $prevYear)
            ->count();
    }

    /**
     * Get previous semester students count
     */
    private function getPreviousSemesterStudents(Faculty $faculty): int
    {
        [$prevSemester, $prevYear] = $this->getPreviousSemesterInfo();
        
        return class_enrollments::whereHas('class', function ($query) use ($faculty, $prevSemester, $prevYear) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', $prevSemester)
                  ->where('school_year', $prevYear);
        })->count();
    }

    /**
     * Get previous semester schedules count
     */
    private function getPreviousSemesterSchedules(Faculty $faculty): int
    {
        [$prevSemester, $prevYear] = $this->getPreviousSemesterInfo();
        
        return Schedule::whereHas('class', function ($query) use ($faculty, $prevSemester, $prevYear) {
            $query->where('faculty_id', $faculty->id)
                  ->where('semester', $prevSemester)
                  ->where('school_year', $prevYear);
        })->count();
    }

    /**
     * Get previous semester average class size
     */
    private function getPreviousSemesterAverageClassSize(Faculty $faculty): int
    {
        [$prevSemester, $prevYear] = $this->getPreviousSemesterInfo();
        
        $classes = $faculty->classes()
            ->where('semester', $prevSemester)
            ->where('school_year', $prevYear)
            ->withCount('ClassStudents')
            ->get();

        if ($classes->isEmpty()) {
            return 0;
        }

        $totalStudents = $classes->sum('class_students_count');
        return (int) round($totalStudents / $classes->count());
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
     * Get previous semester information
     */
    private function getPreviousSemesterInfo(): array
    {
        $currentSemester = $this->getCurrentSemester();
        $currentYear = $this->getCurrentSchoolYear();
        
        switch ($currentSemester) {
            case '1st':
                return ['Summer', $currentYear];
            case '2nd':
                return ['1st', $currentYear];
            case 'Summer':
                $prevYear = $this->getPreviousSchoolYear();
                return ['2nd', $prevYear];
            default:
                return ['1st', $currentYear];
        }
    }

    /**
     * Get previous school year
     */
    private function getPreviousSchoolYear(): string
    {
        $currentYear = $this->getCurrentSchoolYear();
        $years = explode('-', $currentYear);
        $startYear = (int) $years[0] - 1;
        $endYear = (int) $years[1] - 1;
        
        return $startYear . '-' . $endYear;
    }
}
