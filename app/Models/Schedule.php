<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/* CREATE TABLE "schedule" (
  "id" int unsigned NOT NULL AUTO_INCREMENT,
  "created_at" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "deleted_at" datetime DEFAULT NULL,
  "day_of_week" varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  "start_time" time NOT NULL,
  "end_time" time NOT NULL,
  "name" varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY ("id")
) */

final class Schedule extends Model
{
    use HasFactory;

    protected $table = 'schedule';

    protected $fillable = [
        'day_of_week',
        'start_time',
        'end_time',
        'room_id',
        'class_id',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'room_id' => 'int',
    ];

    /**
     * Get the room associated with the schedule
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(rooms::class, 'room_id');
    }

    /**
     * Get the class associated with the schedule
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    // public function getRoomsAttribute()
    // {
    //     return implode(', ', $this->room()->get()->pluck('name')->toArray());
    // }

    /**
     * Get the subject title from the associated class
     */
    public function getSubjectAttribute(): string
    {
        return $this->class->subject_title ?? 'Unknown Subject';
    }

    /**
     * Get the duration of the class in minutes
     */
    public function getDurationMinutesAttribute(): int
    {
        if (! $this->start_time || ! $this->end_time) {
            return 0;
        }

        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Get a formatted representation of the duration
     */
    public function getFormattedDurationAttribute(): string
    {
        $minutes = $this->duration_minutes;
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return $hours.'h '.($remainingMinutes > 0 ? $remainingMinutes.'m' : '');
        }

        return $remainingMinutes.'m';
    }

    /**
     * Check if this schedule overlaps with another schedule
     */
    public function overlaps(self $otherSchedule): bool
    {
        // Different days don't overlap
        if ($this->day_of_week !== $otherSchedule->day_of_week) {
            return false;
        }

        // Check time overlap
        return $this->start_time < $otherSchedule->end_time &&
               $this->end_time > $otherSchedule->start_time;
    }
}
