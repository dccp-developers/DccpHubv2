<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/* CREATE TABLE `class_enrollments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `student_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_class_enrollments_classes` (`class_id`),
  KEY `fk_class_enrollments_students` (`student_id`),
  CONSTRAINT `fk_class_enrollments_classes` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  CONSTRAINT `fk_class_enrollments_students` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci */

final class class_enrollments extends Model
{
    use HasFactory;

    protected $table = 'class_enrollments';

    protected $fillable = [
        'class_id',
        'student_id',
        'completion_date',
        'status',
        'prelim_grade',
        'midterm_grade',
        'finals_grade',
        'total_average',
        'remarks',
    ];

    /**
     * Get the class associated with the enrollment
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class);
    }

    /**
     * Get the student associated with the enrollment
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Students::class);
    }

    /**
     * Get the SHS student associated with the enrollment
     */
    public function ShsStudent(): BelongsTo
    {
        return $this->belongsTo(ShsStudents::class, 'student_id', 'student_lrn');
    }

    /**
     * Get the name of the enrolled student
     */
    public function getStudentNameAttribute(): string
    {
        if ($this->ShsStudent) {
            return $this->ShsStudent->fullname;
        }

        return $this->student->full_name;

    }

    /**
     * Get the academic year or grade level of the student
     */
    public function getStudentYearStandingAttribute(): string
    {
        if ($this->ShsStudent) {
            return $this->ShsStudent->grade_level;
        }

        return (string) $this->student->academic_year;

    }

    /**
     * Get the student ID or LRN
     */
    public function getStudentIdNumAttribute(): string
    {
        if ($this->ShsStudent) {
            return $this->ShsStudent->student_lrn;
        }

        return $this->student->id;

    }

    /**
     * Get the course or strand of the student
     */
    public function getCourseStrandAttribute(): string
    {
        if ($this->ShsStudent) {
            return $this->ShsStudent->track;
        }

        return $this->student->course->code;

    }

    /**
     * Get the enrolled student model (either SHS or regular)
     */
    public function EnrolledStudent()
    {
        if ($this->ShsStudent) {
            return $this->ShsStudent;
        }

        return $this->student;

    }

    /**
     * Get the attendance records for this enrollment
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'class_enrollment_id', 'id');
    }

    /**
     * Get attendance statistics for this enrollment
     */
    public function getAttendanceStatsAttribute(): array
    {
        $attendances = $this->attendances;
        $total = $attendances->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0,
                'partial' => 0,
                'attendance_rate' => 0,
            ];
        }

        $present = $attendances->where('status', 'present')->count();
        $absent = $attendances->where('status', 'absent')->count();
        $late = $attendances->where('status', 'late')->count();
        $excused = $attendances->where('status', 'excused')->count();
        $partial = $attendances->where('status', 'partial')->count();

        $presentCount = $present + $late + $partial; // All these count as present
        $attendanceRate = $total > 0 ? round(($presentCount / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'partial' => $partial,
            'present_count' => $presentCount,
            'attendance_rate' => $attendanceRate,
        ];
    }

    /**
     * Get the current grade status
     */
    public function getGradeStatusAttribute(): string
    {
        if ($this->total_average) {
            return $this->total_average >= 75 ? 'Passing' : 'Failing';
        }

        return 'Pending';
    }

    /**
     * Check if the student has completed the class
     */
    public function getIsCompletedAttribute(): bool
    {
        return ! empty($this->completion_date);
    }
}
