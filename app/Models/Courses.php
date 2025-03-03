<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/* CREATE TABLE test.courses (
    id                   int  NOT NULL  AUTO_INCREMENT,
    code                 varchar(20)  NOT NULL  ,
    title                varchar(60)  NOT NULL  ,
    description          text  NOT NULL  ,
    department           varchar(255)  NOT NULL  ,
    CONSTRAINT pk_courses PRIMARY KEY ( id )
 );

 */

final class Courses extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'courses';

    protected $fillable = [
        'code',
        'title',
        'description',
        'department',
        'remarks',
        'lec_per_unit',
        'lab_per_unit',
    ];

    protected $primaryKey = 'id';

    public static function getCourseDetails($courseId): string
    {
        $course = self::query()->find($courseId);

        return $course ? "{$course->title} (Code: {$course->code}, Department: {$course->department})" : 'Course details not available';
    }

    // protected $casts = [
    //     'code' => 'array',
    // ];

    public function Subjects()
    {
        return $this->hasMany(Subject::class, 'course_id', 'id');
    }

    public function getCourseCodeAttribute(): string
    {
        return mb_strtoupper((string) $this->attributes['code']);
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function ($model): void {
            $model->code = mb_strtoupper((string) $model->code);
        });

        self::deleting(function ($model): void {
            $model->Subjects()->delete();
        });
    }
}
