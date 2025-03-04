<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class Classes extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'classes';

    protected $primaryKey = 'id';

    protected $fillable = [
        'subject_code',
        'faculty_id',
        'academic_year',
        'semester',
        'school_year',
        'course_codes',
        'section',
    ];

    protected $casts = [
        'academic_year' => 'int',
        'course_codes' => 'array',
        'faculty_id' => 'string',
    ];

    protected $with = ['Faculty'];

    /**
     * Get the subject associated with the class
     */
    public function Subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_code', 'code');
    }

    /**
     * Get the SHS subject associated with the class
     */
    public function ShsSubject(): BelongsTo
    {
        return $this->belongsTo(StrandSubjects::class, 'subject_code', 'code');
    }

    /**
     * Get the faculty member teaching the class
     */
    public function Faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'faculty_id', 'id')
            ->withCasts(['id' => 'string']);
    }

    /**
     * Get the room assigned to the class
     */
    public function Room(): BelongsTo
    {
        return $this->belongsTo(rooms::class, 'room_id', 'id');
    }

    /**
     * Get the schedules for the class
     */
    public function Schedule(): HasMany
    {
        return $this->hasMany(Schedule::class, 'class_id', 'id');
    }

    /**
     * Get the students enrolled in the class
     */
    public function ClassStudents(): HasMany
    {
        return $this->hasMany(class_enrollments::class, 'class_id', 'id');
    }

    /**
     * Get the days of the week when the class is scheduled
     */
    public function getScheduleDaysAttribute(): array
    {
        return $this->Schedule->pluck('day_of_week')->toArray();
    }

    /**
     * Get the title of the subject for the class
     */
    public function getSubjectTitleAttribute(): string
    {
        if ($this->ShsSubject) {
            return $this->ShsSubject->title;
        }

        return $this->Subject->title ?? 'Unknown Subject';

    }

    /**
     * Get the formatted subject code
     */
    public function getFormatedSubjectCodeAttribute(): string
    {
        if ($this->ShsSubject) {
            return $this->ShsSubject->code;
        }

        return $this->Subject->code ?? $this->subject_code;

    }

    /**
     * Get the full name of the faculty member teaching the class
     */
    public function getFacultyFullNameAttribute(): string
    {
        return $this->Faculty->faculty_full_name ?? 'N/A';
    }

    /**
     * Get the IDs of rooms assigned to the class
     */
    public function getAssignedRoomIDsAttribute(): array
    {
        return $this->Schedule->pluck('room_id')->toArray();
    }

    /**
     * Get the names of rooms assigned to the class
     */
    public function getAssignedRoomsAttribute(): array
    {
        return rooms::query()->whereIn('id', $this->assigned_room_ids)->pluck('name')->toArray();
    }

    /**
     * Get the number of students enrolled in the class
     */
    public function getStudentCountAttribute(): int
    {
        return $this->ClassStudents->count();
    }

    /**
     * Get a formatted representation of the academic year
     */
    public function getFormatedAcademicYearAttribute(): string
    {
        $yearMapping = [
            1 => '1st year',
            2 => '2nd year',
            3 => '3rd year',
            4 => '4th year',
        ];

        return $yearMapping[$this->academic_year] ?? 'Unknown year';
    }

    public function getFormatedTitleAttribute()
    {
        if ($this->ShsSubject) {
            return $this->ShsSubject->title;
        }

        return $this->Subject->title;

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
