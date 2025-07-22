<?php

declare(strict_types=1);

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ClassEnrollment
 *
 * @property int $id
 * @property int $class_id
 * @property float $student_id
 * @property Carbon|null $completion_date
 * @property bool $status
 * @property string|null $remarks
 * @property float|null $prelim_grade
 * @property float|null $midterm_grade
 * @property float|null $finals_grade
 * @property float|null $total_average
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property bool $is_grades_finalized
 * @property bool $is_grades_verified
 * @property int|null $verified_by
 * @property Carbon|null $verified_at
 * @property string|null $verification_notes
 * @property Class $class
 */
final class ClassEnrollment extends Model
{
    use SoftDeletes;

    protected $table = 'class_enrollments';

    protected $casts = [
        'class_id' => 'int',
        'student_id' => 'float',
        'completion_date' => 'datetime',
        'status' => 'bool',
        'prelim_grade' => 'float',
        'midterm_grade' => 'float',
        'finals_grade' => 'float',
        'total_average' => 'float',
        'is_grades_finalized' => 'boolean',
        'is_grades_verified' => 'boolean',
        'verified_by' => 'integer',
        'verified_at' => 'datetime',
    ];

    protected $fillable = [
        'class_id',
        'student_id',
        'completion_date',
        'status',
        'remarks',
        'prelim_grade',
        'midterm_grade',
        'finals_grade',
        'total_average',
        'is_grades_finalized',
        'is_grades_verified',
        'verified_by',
        'verified_at',
        'verification_notes',
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id', 'id');
    }

    public function student()
    {
        return $this->belongsTo(Students::class, 'student_id', 'id');
    }
}
