<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\GuestEnrollment;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\Student;

/* CREATE  TABLE `laravel-v1`.subject_enrollments (
    id                   BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT  PRIMARY KEY,
    subject_id           INT    NOT NULL   ,
    created_at           TIMESTAMP       ,
    updated_at           TIMESTAMP       ,
    grade                INT       ,
    instructor           VARCHAR(255)   COLLATE utf8mb4_unicode_ci    ,
    student_id           INT       ,
    academic_year        INT       ,
    school_year          VARCHAR(255)       ,
    semester             INT
 );

CREATE INDEX fk_subject_enrollments_subject ON `laravel-v1`.subject_enrollments ( subject_id );

ALTER TABLE `laravel-v1`.subject_enrollments ADD CONSTRAINT fk_subject_enrollments_subject FOREIGN KEY ( subject_id ) REFERENCES `laravel-v1`.subject( id ) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `laravel-v1`.subject_enrollments ADD CONSTRAINT fk_subject_enrollments_students FOREIGN KEY ( student_id ) REFERENCES `laravel-v1`.students( id ) ON DELETE NO ACTION ON UPDATE NO ACTION;


 */

final class SubjectEnrolled extends Model
{
    use HasFactory;
    protected $table = 'subject_enrollments';

    protected $casts = [
        'subject_id' => 'int',
        'class_id' => 'int',
        'grade' => 'float',
        'student_id' => 'int',
        'semester' => 'int',
        'enrollment_id' => 'int',
        'is_credited' => 'bool',
        'credited_subject_id' => 'int',
        'is_modular' => 'bool',
        'lecture_fee' => 'float',
        'laboratory_fee' => 'float'
    ];

    protected $fillable = [
        'subject_id',
        'class_id',
        'grade',
        'instructor',
        'student_id',
        'academic_year',
        'school_year',
        'semester',
        'enrollment_id',
        'remarks',
        'classification',
        'school_name',
        'is_credited',
        'credited_subject_id',
        'section',
        'is_modular',
        'lecture_fee',
        'laboratory_fee'
    ];

    protected static function boot()
    {
        parent::boot();
      
        static::creating(function ($model) {
            $highestId = static::max('id');
            $model->id = $highestId ? $highestId + 1 : 1;
        });
    }

    /**
     * Calculate the average grade for a specific semester and academic year.
     */
    public static function calculateSemesterGPA(int $studentId, int $academicYear, int $semester): ?float
    {
        $average = self::query()
            ->where('student_id', $studentId)
            ->where('academic_year', $academicYear)
            ->where('semester', $semester)
            ->avg('grade'); // Use the 'grade' column

        return $average !== null ? (float) number_format($average, 2) : null;
    }

    /**
     * Calculate the average grade across two semesters (1st and 2nd) within a single academic year.
     */
    public static function calculateYearlyGPA(int $studentId, int $academicYear): ?float
    {
        $firstSemesterGPA = self::calculateSemesterGPA($studentId, $academicYear, 1);
        $secondSemesterGPA = self::calculateSemesterGPA($studentId, $academicYear, 2);

        if ($firstSemesterGPA !== null && $secondSemesterGPA !== null) {
            return ($firstSemesterGPA + $secondSemesterGPA) / 2;
        }

        if ($firstSemesterGPA !== null) {
            return $firstSemesterGPA; // Only 1st semester grades available
        }

        if ($secondSemesterGPA !== null) {
            return $secondSemesterGPA; // Only 2nd semester grades available.
        }

        return null; // No grades for either semester
    }

    /**
     * Calculate the average grade across all four academic years.
     */
    public static function calculateOverallGPA(int $studentId): ?float
    {
        $yearlyGPAs = [];

        for ($year = 1; $year <= 4; $year++) {
            $yearlyGPA = self::calculateYearlyGPA($studentId, $year);
            if ($yearlyGPA !== null) {
                $yearlyGPAs[] = $yearlyGPA;
            }
        }

        if ($yearlyGPAs !== []) {
            $overallGPA = array_sum($yearlyGPAs) / count($yearlyGPAs);

            return (float) number_format($overallGPA, 2);
        }

        return null; // No grades available for any year.
    }

    public function guestEnrollment(): BelongsTo
    {
        return $this->belongsTo(GuestEnrollment::class, 'enrollment_id', 'id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
