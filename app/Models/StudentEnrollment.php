<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class StudentEnrollment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'student_enrollment';

    protected $fillable = [
        'student_id',
        'course_id',
        'downpayment',
        'academic_year',
    ];

    public function signature()
    {
        return $this->morphOne(EnrollmentSignatures::class, 'enrollment');
    }

    public function student()
    {
        return $this->belongsTo(Students::class, 'student_id', 'id');
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
        return $this->resources()->where('type', 'assessment')->first()->file_path;
    }

    public function getCertificatePathAttribute(): string
    {
        return $this->resources()->where('type', 'certificate')->first()->file_path;
    }

    public function getAssessmentUrlAttribute(): string
    {
        $resource = $this->resources()->where('type', 'assessment')->first();

        return $resource ? Storage::disk('public')->url($resource->file_path) : '';
    }

    public function getCertificateUrlAttribute(): string
    {
        $resource = $this->resources()->where('type', 'certificate')->first();

        return $resource ? Storage::disk('public')->url($resource->file_path) : '';
    }

    // protected $dates = ['enrollment_date', 'completion_date'];

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (self $model): void {
            $settings = GeneralSettings::query()->first();
            $model->status = 'Pending';
            $model->school_year = $settings->getSchoolYearString();
            $model->semester = $settings->semester;
        });

        // delete also the subjects enrolled
        // static::deleting(function (self $model) {
        //     $model->subjectsEnrolled()->delete();
        // });
    }
}
