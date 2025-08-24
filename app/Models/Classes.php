<?php

declare(strict_types=1);

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Services\GeneralSettingsService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Class
 *
 * @property int $id
 * @property string|null $subject_code
 * @property string|null $faculty_id
 * @property string|null $academic_year
 * @property string|null $semester
 * @property int|null $schedule_id
 * @property string|null $school_year
 * @property string|null $course_codes
 * @property string|null $section
 * @property int|null $room_id
 * @property string|null $classification
 * @property int|null $maximum_slots
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection|ClassPost[] $class_posts
 * @property Collection|ClassEnrollment[] $class_enrollments
 */
final class Classes extends Model
{
    use HasFactory;
    protected $table = 'classes';

    protected $casts = [
        'subject_id' => 'int',
        'faculty_id' => 'string',
        'schedule_id' => 'int',
        'room_id' => 'int',
        'maximum_slots' => 'int',
        'course_codes' => 'array',
        'shs_track_id' => 'int',
        'shs_strand_id' => 'int',
    ];

    protected $fillable = [
        'subject_id',
        'subject_code',
        'faculty_id',
        'academic_year',
        'semester',
        'schedule_id',
        'school_year',
        'course_codes',
        'section',
        'room_id',
        'classification',
        'maximum_slots',
        'shs_track_id',
        'shs_strand_id',
        'grade_level',
    ];

    public function class_enrollments()
    {
        return $this->hasMany(ClassEnrollment::class, 'class_id', 'id');
    }

    public function Subject()
    {
        // Use subject_id relationship if available, otherwise fallback to code
        return $this->belongsTo(Subject::class, 'subject_id', 'id');
    }

    /**
     * Get the subject with fallback to code-based lookup
     */
    public function getSubjectWithFallbackAttribute()
    {
        // First try the direct relationship
        if ($this->subject_id) {
            $subject = $this->belongsTo(Subject::class, 'subject_id', 'id')->first();
            if ($subject) {
                return $subject;
            }
        }

        // Fallback to code-based lookup
        if ($this->subject_code) {
            return Subject::where('code', $this->subject_code)->first();
        }

        return null;
    }

    public function SubjectByCodeFallback()
    {
        return $this->belongsTo(Subject::class, 'subject_code', 'code');
    }

    public function SubjectById()
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'id');
    }

    public function SubjectByCode()
    {
        return $this->belongsTo(Subject::class, 'subject_code', 'code');
    }

    public function ShsSubject()
    {
        return $this->belongsTo(StrandSubjects::class, 'subject_code', 'code');
    }

    public function ShsTrack()
    {
        return $this->belongsTo(ShsTracks::class, 'shs_track_id', 'id');
    }

    public function ShsStrand()
    {
        return $this->belongsTo(ShsStrand::class, 'shs_strand_id', 'id');
    }

    public function Faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id', 'id');
    }

    public function Room()
    {
        return $this->belongsTo(rooms::class, 'room_id', 'id');
    }

    public function Schedule()
    {
        return $this->hasMany(Schedule::class, 'class_id', 'id');
    }

    public function ClassStudents()
    {
        return $this->hasMany(ClassEnrollment::class, 'class_id', 'id');
    }

    public function enrollments()
    {
        return $this->hasMany(ClassEnrollment::class, 'class_id', 'id');
    }

    public function getScheduleDaysAttribute()
    {
        return $this->Schedule->pluck('day_of_week')->toArray();
    }

    public function getScheduleRoomsAttribute()
    {
        return $this->Schedule->pluck('rooms')->toArray();
    }

    public function getClassSubjectTitleAttribute()
    {
        return $this->Subject->title;
    }

    public function getFacultyFullNameAttribute()
    {
        return $this->Faculty->full_name ?? 'N/A';
    }

    public function getAssignedRoomIDsAttribute()
    {
        return $this->Schedule->pluck('room_id')->toArray();
    }

    public function getAssignedRoomsAttribute()
    {
        return rooms::whereIn('id', $this->assigned_room_ids)
            ->pluck('name')
            ->toArray();
    }

    public function getStudentCountAttribute()
    {
        return $this->ClassStudents->count();
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }

    /**
     * Get the attendance records for this class
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'class_id');
    }

    /**
     * Get the courses associated with this class
     */
    public function courses()
    {
        if (empty($this->course_codes) || ! is_array($this->course_codes)) {
            return collect();
        }

        // Filter out any null or empty values
        $validCourseIds = array_filter($this->course_codes, fn ($id): bool => ! empty($id));

        if ($validCourseIds === []) {
            return collect();
        }

        return Courses::whereIn('id', $validCourseIds)->get();
    }

    /**
     * Get the formatted course codes for display
     */
    public function getFormattedCourseCodesAttribute()
    {
        if (empty($this->course_codes)) {
            return 'N/A';
        }

        return $this->courses()->map(function ($course): string {
            // Extract just the course abbreviation from the code
            // e.g., "BSBA (2018 - 2019) NON-ABM" becomes "BSBA"
            $parts = explode('(', (string) $course->code);

            return mb_trim($parts[0]);
        })->unique()->join(', ');
    }

    /**
     * Get the subject title with course codes for display
     */
    public function getSubjectWithCoursesAttribute()
    {
        $subjectTitle = $this->Subject?->title ?? $this->subject_code;
        $courseCodes = $this->formatted_course_codes;

        return ($courseCodes && $courseCodes !== 'N/A') ? "{$subjectTitle} ({$courseCodes})" : $subjectTitle;
    }

    /**
     * Check if this class is for College students
     */
    public function isCollege(): bool
    {
        return $this->classification === 'college' || empty($this->classification);
    }

    /**
     * Check if this class is for SHS students
     */
    public function isShs(): bool
    {
        return $this->classification === 'shs';
    }

    /**
     * Get the appropriate subject based on classification
     */
    public function getActiveSubjectAttribute()
    {
        if ($this->isShs()) {
            return $this->ShsSubject;
        }

        // Use subject_id relationship if available, otherwise fallback to code
        if ($this->subject_id) {
            return $this->Subject;
        }

        return $this->SubjectByCodeFallback;
    }

    /**
     * Get the subject title for both College and SHS
     */
    public function getSubjectTitleAttribute()
    {
        if ($this->isShs()) {
            return $this->ShsSubject?->title ?? $this->subject_code;
        }

        // Use the active subject which handles the fallback logic
        return $this->getActiveSubjectAttribute()?->title ?? $this->subject_code;
    }

    /**
     * Get formatted track/strand information for SHS classes
     */
    public function getFormattedTrackStrandAttribute()
    {
        if (! $this->isShs()) {
            return null;
        }

        $track = $this->ShsTrack?->track_name;
        $strand = $this->ShsStrand?->strand_name;

        if ($track && $strand) {
            return "{$track} - {$strand}";
        }
        if ($track) {
            return $track;
        }

        return 'N/A';
    }

    /**
     * Get display information based on class type (College courses or SHS track/strand)
     */
    public function getDisplayInfoAttribute()
    {
        if ($this->isShs()) {
            return $this->formatted_track_strand;
        }

        return $this->formatted_course_codes;
    }

    public function getScheduleDetails($seletedCourse, $selectedAcademicYear)
    {
        return Schedule::whereHas('class', function ($query) use (
            $seletedCourse,
            $selectedAcademicYear
        ): void {
            $query
                ->where('academic_year', $selectedAcademicYear)
                ->whereHas('subject', function ($subQuery) use (
                    $seletedCourse
                ): void {
                    $subQuery->where('course_id', $seletedCourse->id);
                });
        })->get();
    }

    /**
     * Scope a query to only include classes for the current school year and semester.
     */
    public function scopeCurrentAcademicPeriod(Builder $query): Builder
    {
        // Use the GeneralSettingsService to get effective settings
        $settingsService = app(GeneralSettingsService::class);
        $schoolYearWithSpaces = $settingsService->getCurrentSchoolYearString(); // e.g., "2020 - 2021"
        $schoolYearNoSpaces = str_replace(' ', '', $schoolYearWithSpaces);      // e.g., "2020-2021"
        $semester = $settingsService->getCurrentSemester();

        // dd($schoolYearWithSpaces, $schoolYearNoSpaces, $semester);
        return $query
            ->whereIn('school_year', [$schoolYearWithSpaces, $schoolYearNoSpaces])
            ->where('semester', $semester);
    }

    /**
     * Scope a query to only include College classes
     */
    public function scopeCollege(Builder $query): Builder
    {
        return $query->where(function ($q): void {
            $q->where('classification', 'college')
                ->orWhereNull('classification');
        });
    }

    /**
     * Scope a query to only include SHS classes
     */
    public function scopeShs(Builder $query): Builder
    {
        return $query->where('classification', 'shs');
    }

    /**
     * @return mixed[]
     */
    public function getFormattedWeeklyScheduleAttribute(): array
    {
        $days = [
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
        ];

        $scheduleByDay = $this->schedules->groupBy(fn ($schedule) => mb_strtolower((string) $schedule->day_of_week));

        $formattedSchedule = [];

        foreach ($days as $day) {
            $formattedSchedule[$day] = $scheduleByDay
                ->get($day, collect())
                ->map(fn ($schedule): array => [
                    'start_time' => $schedule->formatted_start_time,
                    'end_time' => $schedule->formatted_end_time,
                    'time_range' => $schedule->time_range,
                    'room' => [
                        'id' => $schedule->room_id,
                        'name' => $schedule->room?->name ?? 'TBA',
                    ],
                    'has_conflict' => false, // You could calculate this if needed
                ]);
        }

        return $formattedSchedule;
    }

    protected static function boot(): void
    {
        parent::boot();

        self::deleting(function ($model): void {
            $model->Schedule()->delete();
            $model->ClassStudents()->delete();
        });
    }
}
