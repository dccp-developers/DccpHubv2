<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Attendance Model
 * 
 * Represents student attendance records for classes
 * 
 * @property int $id
 * @property int $class_enrollment_id
 * @property string $student_id
 * @property int $class_id
 * @property string $date
 * @property AttendanceStatus $status
 * @property string|null $remarks
 * @property Carbon|null $marked_at
 * @property string|null $marked_by
 * @property string|null $ip_address
 * @property array|null $location_data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
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
        'class_id',
        'date',
        'status',
        'remarks',
        'marked_at',
        'marked_by',
        'ip_address',
        'location_data',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date' => 'date',
        'status' => AttendanceStatus::class,
        'marked_at' => 'datetime',
        'location_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'ip_address',
        'location_data',
    ];

    /**
     * Get the class enrollment that this attendance belongs to.
     */
    public function classEnrollment(): BelongsTo
    {
        return $this->belongsTo(class_enrollments::class, 'class_enrollment_id');
    }

    /**
     * Get the student that this attendance belongs to.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Students::class, 'student_id', 'student_id');
    }

    /**
     * Get the SHS student that this attendance belongs to.
     */
    public function shsStudent(): BelongsTo
    {
        return $this->belongsTo(ShsStudents::class, 'student_id', 'student_lrn');
    }

    /**
     * Get the class that this attendance belongs to.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    /**
     * Get the faculty member who marked this attendance.
     */
    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'marked_by', 'id');
    }

    /**
     * Get the enrolled student (either regular or SHS).
     */
    public function enrolledStudent()
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
     * Scope to filter by class.
     */
    public function scopeForClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope to filter by student.
     */
    public function scopeForStudent($query, string $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('date', [
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        ]);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, AttendanceStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get present attendances (present, late, partial).
     */
    public function scopePresent($query)
    {
        return $query->whereIn('status', [
            AttendanceStatus::PRESENT,
            AttendanceStatus::LATE,
            AttendanceStatus::PARTIAL,
        ]);
    }

    /**
     * Scope to get absent attendances.
     */
    public function scopeAbsent($query)
    {
        return $query->where('status', AttendanceStatus::ABSENT);
    }

    /**
     * Scope to get recent attendances.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('date', '>=', now()->subDays($days)->format('Y-m-d'));
    }

    /**
     * Scope to order by date descending.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('date', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * Check if the attendance is considered present.
     */
    public function isPresent(): bool
    {
        return $this->status->isPresent();
    }

    /**
     * Check if the attendance is considered absent.
     */
    public function isAbsent(): bool
    {
        return $this->status->isAbsent();
    }

    /**
     * Get the formatted date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('M j, Y');
    }

    /**
     * Get the formatted date with day.
     */
    public function getFormattedDateWithDayAttribute(): string
    {
        return $this->date->format('l, M j, Y');
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    /**
     * Get the status color.
     */
    public function getStatusColorAttribute(): string
    {
        return $this->status->color();
    }

    /**
     * Get the status icon.
     */
    public function getStatusIconAttribute(): string
    {
        return $this->status->icon();
    }

    /**
     * Check if attendance has location data.
     */
    public function hasLocationData(): bool
    {
        return !empty($this->location_data);
    }

    /**
     * Get the location coordinates if available.
     */
    public function getLocationCoordinates(): ?array
    {
        if (!$this->hasLocationData()) {
            return null;
        }

        return [
            'latitude' => $this->location_data['latitude'] ?? null,
            'longitude' => $this->location_data['longitude'] ?? null,
            'accuracy' => $this->location_data['accuracy'] ?? null,
        ];
    }

    /**
     * Check if attendance was marked recently (within last hour).
     */
    public function isRecentlyMarked(): bool
    {
        if (!$this->marked_at) {
            return false;
        }

        return $this->marked_at->diffInHours(now()) < 1;
    }

    /**
     * Get the time since attendance was marked.
     */
    public function getTimeSinceMarked(): ?string
    {
        if (!$this->marked_at) {
            return null;
        }

        return $this->marked_at->diffForHumans();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set marked_at when creating
        static::creating(function ($attendance) {
            if (!$attendance->marked_at) {
                $attendance->marked_at = now();
            }
        });

        // Update marked_at when status changes
        static::updating(function ($attendance) {
            if ($attendance->isDirty('status')) {
                $attendance->marked_at = now();
            }
        });
    }
}
