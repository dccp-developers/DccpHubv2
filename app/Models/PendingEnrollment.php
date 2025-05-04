<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; // Import Attribute class

class PendingEnrollment extends Model
{
    use HasFactory;

    protected $table = 'pending_enrollments';

    protected $fillable = [
        'data',
        'status',
        'remarks',
        'approved_by',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array', // Cast the JSON column to an array
        'processed_at' => 'datetime',
    ];

    /**
     * Get the user who approved/rejected the enrollment.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Accessor to get specific data points from the JSON data easily.
     * Example: $pendingEnrollment->first_name
     */
    protected function dataValue(string $key): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => json_decode($attributes['data'] ?? '{}', true)[$key] ?? null,
        );
    }

    // You can add more accessors for commonly used fields if needed
    public function getFirstNameAttribute()
    {
        return $this->data['first_name'] ?? null;
    }

     public function getLastNameAttribute()
    {
        return $this->data['last_name'] ?? null;
    }

     public function getEmailAttribute()
    {
        return $this->data['email'] ?? null;
    }

     public function getCourseIdAttribute()
    {
        return $this->data['course_id'] ?? null;
    }

    // Add a relationship to Course if needed for display
    public function course()
    {
        // Check if course_id exists in data before trying to relate
        if (isset($this->data['course_id'])) {
            return $this->belongsTo(Courses::class, 'data->course_id'); // Adjust if course_id is stored differently
        }
        // Return a dummy relationship or null if course_id is not set
        return $this->belongsTo(Courses::class)->whereRaw('1 = 0'); // Example: always returns empty
    }

}
