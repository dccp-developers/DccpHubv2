<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/*CREATE TABLE laravel.subject (
    id                   int  NOT NULL  AUTO_INCREMENT  PRIMARY KEY,
    code                 varchar(255)  NOT NULL    ,
    title                varchar(255)  NOT NULL    ,
    units                int  NOT NULL    ,
    lecture              int  NOT NULL    ,
    laboratory           int      ,
    pre_riquisite        text  NOT NULL DEFAULT '[]'   ,
    academic_year        int  NOT NULL    ,
    semester             int  NOT NULL    ,
    course_id            int  NOT NULL
 );

ALTER TABLE laravel.subject ADD CONSTRAINT fk_subject_courses FOREIGN KEY ( course_id ) REFERENCES laravel.courses( id ) ON DELETE NO ACTION ON UPDATE NO ACTION;

*/

class Subject extends Model
{
    protected $table = 'subject';

    protected $primaryKey = 'id';

    protected $fillable = [
        'code', 'title', 'units', 'lecture', 'laboratory', 'course_id', 'pre_riquisite', 'academic_year', 'semester',
    ];

    protected $casts = [
        'pre_riquisite' => 'array',
        'units' => 'integer',
        'lecture' => 'integer',
        'laboratory' => 'integer',
        'academic_year' => 'integer',
        'semester' => 'integer',
    ];

    public $timestamps = false;

    /**
     * Get the course associated with the subject
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Courses::class, 'course_id', 'id');
    }

    /**
     * Get the subject enrollments for this subject
     */
    public function subjectEnrolleds(): HasMany
    {
        return $this->hasMany(SubjectEnrolled::class, 'subject_id');
    }

    /**
     * Get the classes for this subject
     */
    public function classes(): HasMany
    {
        return $this->hasMany(Classes::class, 'subject_code', 'code');
    }

    /**
     * Get all prerequisites for this subject
     */
    public function getAllPreRequisitesAttribute(): array
    {
        return $this->pre_riquisite ?? [];
    }

    /**
     * Get a formatted representation of the subject with code and units
     */
    public function getFormattedTitleAttribute(): string
    {
        return "{$this->title} ({$this->code}, {$this->units} units)";
    }

    /**
     * Get subjects details by academic year
     */
    public static function getSubjectsDetailsByYear(Collection $subjects, int $year): string
    {
        return $subjects->where('academic_year', $year)->map(function ($subject) {
            return "{$subject->title} (Code: {$subject->code}, Units: {$subject->units})";
        })->join(', ');
    }

    /**
     * Get the total hours per week (lecture + laboratory)
     */
    public function getTotalHoursPerWeekAttribute(): int
    {
        return ($this->lecture ?? 0) + ($this->laboratory ?? 0);
    }

    /**
     * Check if this subject has prerequisites
     */
    public function hasPrerequisites(): bool
    {
        return !empty($this->pre_riquisite);
    }
}
