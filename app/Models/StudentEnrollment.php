<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder; // Import Builder
use Illuminate\Support\Facades\Cache; // Import Cache

/**
 * Class StudentEnrollment
 *
 * @property int $id
 * @property string $student_id
 * @property string $course_id
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $semester
 * @property int|null $academic_year
 * @property string|null $school_year
 * @property string|null $deleted_at
 * @property float|null $downpayment
 * @property string|null $remarks
 *
 * @package App\Models
 */
class StudentEnrollment extends Model
{
    use SoftDeletes;
    // use HasFactory;
    // use SoftDeletes;

    protected $table = 'student_enrollment';

    protected $fillable = [
        'student_id',
        'course_id',
        'status',
        'semester',
        'academic_year',
        'school_year',
        'downpayment',
        'payment_method',
        'remarks',
    ];

    protected $casts = [
        'id' => 'integer',
        'semester' => 'integer',
        'academic_year' => 'integer',
        'downpayment' => 'float',
        'payment_method' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            $settings = GeneralSettings::first();
            $model->status = 'Pending';
            $model->school_year = $settings->getSchoolYearString();
            $model->semester = $settings->semester;
        });

        // delete also the subjects enrolled
        static::forceDeleted(function (self $model) {
            $model->subjectsEnrolled()->delete();
            $model->studentTuition()->delete();
        });
    }



    public function student()
    {
        return $this->belongsTo(Students::class, 'student_id', 'id')
            ->withoutGlobalScopes()
            ->withDefault();
    }

    public function getStudentNameAttribute(): string
    {
        return $this->student->full_name;
    }

    public function course()
    {
        return $this->belongsTo(Courses::class);
    }

    public function subjectsEnrolled()
    {
        return $this->hasMany(SubjectEnrolled::class, 'enrollment_id', 'id');
    }

    public function studentTuition()
    {
        return $this->hasOne(StudentTuition::class, 'enrollment_id', 'id');
    }

    public function resources()
    {
        return $this->morphMany(Resource::class, 'resourceable');
    }

    public function getAssessmentPathAttribute(): string
    {
        return $this->resources()->where('type', 'assessment')->latest()->first()->file_path;
    }

    public function getCertificatePathAttribute(): string
    {
        return $this->resources()->where('type', 'certificate')->latest()->first()->file_path;
    }

    public function getAssessmentUrlAttribute(): string
    {
        $resource = $this->resources()->where('type', 'assessment')->latest()->first();
        return $resource ? Storage::disk('r2')->url($resource->file_path) : '';
    }

    public function getCertificateUrlAttribute(): string
    {
        
        $resource = $this->resources()->where('type', 'certificate')->latest()->first();
        return $resource ? Storage::disk('r2')->url($resource->file_path) : '';
    }

    /**
     * Scope a query to only include enrollments for the current school year and semester.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrentAcademicPeriod(Builder $query): Builder
    {
        // Fetch settings dynamically using cache
        $settings = Cache::remember('general_settings', 3600, function () {
            return GeneralSettings::first();
        });

        if ($settings) {
            $schoolYear = $settings->getSchoolYearString(); // e.g., "2024 - 2025"
            $semester = $settings->semester;

            return $query
                ->where('school_year', $schoolYear)
                ->where('semester', $semester);
        }

        // If no settings found, return the original query
        // Log::warning('General settings not found for scoping Student Enrollments.'); // Optional logging
        return $query;
    }

    /**
     * Check if a student is already enrolled for a specific semester and school year.
     *
     * @param  string  $studentId
     * @param  int  $semester
     * @param  string  $schoolYear
     * @return bool
     */
    public static function isStudentEnrolled(string $studentId, int $semester, string $schoolYear): bool
    {
        return self::where('student_id', $studentId)
            ->where('semester', $semester)
            ->where('school_year', $schoolYear)
            ->where('status', 'Verified By Cashier')
            ->exists();
    }

    /**
     * Check if a student has any enrollment record for a specific semester and school year.
     *
     * @param  string  $studentId
     * @param  int  $semester
     * @param  string  $schoolYear
     * @return bool
     */
    public static function hasEnrollmentRecord(string $studentId, int $semester, string $schoolYear): bool
    {
        return self::where('student_id', $studentId)
            ->where('semester', $semester)
            ->where('school_year', $schoolYear)
            ->exists();
    }

    /**
     * Get the enrollment status for a student in a specific semester and school year.
     *
     * @param  string  $studentId
     * @param  int  $semester
     * @param  string  $schoolYear
     * @return string|null
     */
    public static function getEnrollmentStatus(string $studentId, int $semester, string $schoolYear): ?string
    {
        $enrollment = self::where('student_id', $studentId)
            ->where('semester', $semester)
            ->where('school_year', $schoolYear)
            ->latest('created_at')
            ->first();

        return $enrollment ? $enrollment->status : null;
    }
}
