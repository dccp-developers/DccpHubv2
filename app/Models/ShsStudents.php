<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class ShsStudents extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_lrn',
        'fullname',
        'civil_status',
        'religion',
        'nationality',
        'birthdate',
        'guardian_name',
        'guardian_contact',
        'student_contact',
        'complete_address',
        'grade_level',
        'track',
        'gender',
        'email',
        'remarks',
    ];

    public function classEnrollments()
    {
        return $this->hasMany(class_enrollments::class, 'student_id', 'student_lrn');
    }

    public function getCodeAttribute()
    {
        $Strand = \App\Models\TracksStrands::query()->where('id', $this->strand_id)->first();

        return $Strand->code;
    }

    public function getStudentIdNoAttribute()
    {
        return $this->student_lrn;
    }

    public function getYearStandingAttribute()
    {
        return $this->grade_level;
    }
}
