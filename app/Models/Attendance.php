<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attendance Model
 * 
 * Represents student attendance records for classes
 * 
 * @property int $id
 * @property int $class_enrollment_id
 * @property string $student_id
 * @property string $date
 * @property AttendanceStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Attendance extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'attendances';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'class_enrollment_id',
        'student_id',
        'date',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date' => 'date',
        'status' => AttendanceStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the class enrollment that owns the attendance.
     */
    public function classEnrollment(): BelongsTo
    {
        return $this->belongsTo(class_enrollments::class, 'class_enrollment_id');
    }

    /**
     * Get the student that owns the attendance.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Students::class, 'student_id', 'id');
    }

    /**
     * Get the class through the enrollment.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id', 'id');
    }

    /**
     * Scope a query to only include attendance for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope a query to only include attendance for a specific status.
     */
    public function scopeWithStatus($query, AttendanceStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include attendance for a specific student.
     */
    public function scopeForStudent($query, string $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope a query to only include attendance for a specific class enrollment.
     */
    public function scopeForEnrollment($query, int $enrollmentId)
    {
        return $query->where('class_enrollment_id', $enrollmentId);
    }

    /**
     * Get formatted date attribute.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('M d, Y');
    }

    /**
     * Get status label attribute.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            AttendanceStatus::PRESENT => 'Present',
            AttendanceStatus::ABSENT => 'Absent',
            AttendanceStatus::LATE => 'Late',
            AttendanceStatus::EXCUSED => 'Excused',
            AttendanceStatus::PARTIAL => 'Partial',
            default => 'Unknown',
        };
    }

    /**
     * Get status color attribute for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            AttendanceStatus::PRESENT => 'success',
            AttendanceStatus::ABSENT => 'danger',
            AttendanceStatus::LATE => 'warning',
            AttendanceStatus::EXCUSED => 'info',
            AttendanceStatus::PARTIAL => 'secondary',
            default => 'dark',
        };
    }

    /**
     * Check if the attendance is marked as present (including late and partial).
     */
    public function isPresentAttribute(): bool
    {
        return in_array($this->status, [
            AttendanceStatus::PRESENT,
            AttendanceStatus::LATE,
            AttendanceStatus::PARTIAL,
        ]);
    }

    /**
     * Check if the attendance is marked as absent.
     */
    public function isAbsentAttribute(): bool
    {
        return $this->status === AttendanceStatus::ABSENT;
    }

    /**
     * Check if the attendance is excused.
     */
    public function isExcusedAttribute(): bool
    {
        return $this->status === AttendanceStatus::EXCUSED;
    }
}
