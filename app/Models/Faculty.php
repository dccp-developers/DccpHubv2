<?php

declare(strict_types=1);

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Class Faculty
 *
 * @property string $id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $middle_name
 * @property string $email
 * @property string|null $phone_number
 * @property string|null $department
 * @property string|null $office_hours
 * @property Carbon|null $birth_date
 * @property string|null $address_line1
 * @property string|null $biography
 * @property string|null $education
 * @property string|null $courses_taught
 * @property string|null $photo_url
 * @property USER-DEFINED|null $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property USER-DEFINED|null $gender
 * @property int|null $age
 */
final class Faculty extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory, HasUuids, Notifiable;

    public $incrementing = false;

    protected $table = 'faculty';

    protected $casts = [
        'id' => 'string',
        'birth_date' => 'datetime',
        'status' => 'string',
        'gender' => 'string',
        'age' => 'int',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $fillable = [
        'id',
        'faculty_id_number',
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'password',
        'phone_number',
        'department',
        'office_hours',
        'birth_date',
        'address_line1',
        'biography',
        'education',
        'courses_taught',
        'photo_url',
        'status',
        'gender',
        'age',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    private string $guard = 'faculty';

    // Get full name attribute
    public function getFullNameAttribute(): string
    {
        $name = mb_trim("{$this->last_name}, {$this->first_name} {$this->middle_name}");

        return $name !== '' && $name !== '0' ? $name : 'N/A';  // Return 'N/A' if the name is empty
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if ($this->photo_url) {
            return $this->photo_url;
        }

        // Default to gravatar
        $hash = md5(mb_strtolower(mb_trim($this->email)));

        return 'https://www.gravatar.com/avatar/'.$hash.'?d=mp&r=g&s=250';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'faculty';
    }

    // Relationships
    public function classes()
    {
        return $this->hasMany(Classes::class, 'faculty_id', 'id');
    }

    public function classEnrollments()
    {
        return $this->hasManyThrough(
            ClassEnrollment::class,
            Classes::class,
            'faculty_id',
            'class_id'
        );
    }

    /**
     * Get the attendance records marked by this faculty
     */
    public function markedAttendances()
    {
        return $this->hasMany(Attendance::class, 'marked_by', 'id');
    }

    // Update or add this method to ensure we always return a string
    public function getNameAttribute(): string
    {
        return $this->getFullNameAttribute();
    }
}
