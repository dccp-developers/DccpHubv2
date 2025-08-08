<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Enums\AttendanceStatus;

/**
 * Attendance Model
 *
 * Tracks student attendance for specific class sessions
 *
 * @property int $id
 * @property int $class_enrollment_id
 * @property string $student_id
 * @property int $class_id
 * @property Carbon $date
 * @property string $status
 * @property string|null $remarks
 * @property Carbon|null $marked_at
 * @property string|null $marked_by
 * @property string|null $ip_address
 * @property array|null $location_data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
final class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    // Attendance Status Constants
    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LATE = 'late';
    public const STATUS_EXCUSED = 'excused';
    public const STATUS_PARTIAL = 'partial';

    // Available statuses
    public const STATUSES = [
        self::STATUS_PRESENT => 'Present',
        self::STATUS_ABSENT => 'Absent',
        self::STATUS_LATE => 'Late',
        self::STATUS_EXCUSED => 'Excused',
        self::STATUS_PARTIAL => 'Partial',
    ];

    protected $fillable = [
        'class_enrollment_id',
        'student_id',
        'class_id',
        'date',
        'status',
        'remarks',
        'marked_at',
        'marked_by',
        'ip_address',
        'location_data',
    ];

    protected $casts = [
        'date' => 'date',
        'marked_at' => 'datetime',
        'location_data' => 'array',
        'status' => AttendanceStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = [
        'date',
        'marked_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the class enrollment this attendance belongs to
     */
    public function classEnrollment(): BelongsTo
    {
        return $this->belongsTo(class_enrollments::class, 'class_enrollment_id');
    }

    /**
     * Get the class this attendance is for
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    /**
     * Get the student this attendance belongs to (regular students)
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Students::class, 'student_id', 'student_id');
    }

    /**
     * Get the SHS student this attendance belongs to
     */
    public function shsStudent(): BelongsTo
    {
        return $this->belongsTo(ShsStudents::class, 'student_id', 'student_lrn');
    }

    /**
     * Get the faculty member who marked this attendance
     */
    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'marked_by', 'id');
    }

    /**
     * Get the enrolled student (either regular or SHS)
     */
    public function getEnrolledStudent()
    {
        // Try to get regular student first
        $student = $this->student;
        if ($student) {
            return $student;
        }

        // Fall back to SHS student
        return $this->shsStudent;
    }

    /**
     * Check if the attendance status is considered present
     */
    public function isPresent(): bool
    {
        return in_array($this->status, [self::STATUS_PRESENT, self::STATUS_LATE, self::STATUS_PARTIAL]);
    }

    /**
     * Check if the attendance status is considered absent
     */
    public function isAbsent(): bool
    {
        return $this->status === self::STATUS_ABSENT;
    }

    /**
     * Check if the attendance is excused
     */
    public function isExcused(): bool
    {
        return $this->status === self::STATUS_EXCUSED;
    }

    /**
     * Get the status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? 'Unknown';
    }

    /**
     * Get the status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PRESENT => 'success',
            self::STATUS_LATE => 'warning',
            self::STATUS_PARTIAL => 'info',
            self::STATUS_EXCUSED => 'secondary',
            self::STATUS_ABSENT => 'destructive',
            default => 'secondary',
        };
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by class
     */
    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope to filter by student
     */
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope to get present attendances
     */
    public function scopePresent($query)
    {
        return $query->whereIn('status', [self::STATUS_PRESENT, self::STATUS_LATE, self::STATUS_PARTIAL]);
    }

    /**
     * Scope to get absent attendances
     */
    public function scopeAbsent($query)
    {
        return $query->where('status', self::STATUS_ABSENT);
    }
}
