<?php

declare(strict_types=1);

namespace App\Models;

use Exception;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens; // Import the trait
use Spatie\Permission\Traits\HasRoles;

final class Admins extends Authenticatable implements FilamentUser, HasAvatar
{
    // use  HasPanelShield;

    use HasApiTokens,
        HasFactory,
        HasRoles,
        Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'email', 'password', 'avatar_url'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * The attributes that should be cast.
     *
     *@var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getFilamentAvatarUrl(): ?string
    {
        if ($this->avatar_url) {
            try {
                if (Storage::disk('supabase')->exists($this->avatar_url)) {
                    return Storage::disk('supabase')->url($this->avatar_url);
                }
            } catch (Exception) {
                // Fallback to gravatar if there's an SSL or other error
            }
        }

        // Default to gravatar
        $hash = md5(mb_strtolower(mb_trim($this->email)));

        return 'https://www.gravatar.com/avatar/'.$hash.'?d=mp&r=g&s=250';
    }

    public function canComment(): bool
    {
        return true;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasAnyRole([
                'cashier',
                'panel_user',
                'admin',
                'super_admin',
            ]);
        }
        if ($panel->getId() === 'faculty') {
            // Only allow access if the faculty panel is enabled in config
            if (! config('app.enable_faculty_panel', false)) {
                return false;
            }

            return $this->hasRole('faculty');
        }

        return $this->hasRole('super_admin');
    }

    // admin Transactions

    public function transactions()
    {
        return $this->hasMany(AdminTransaction::class, 'admin_id', 'id');
    }

    public function getIsSuperAdminAttribute(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function getIsCashierAttribute(): bool
    {
        return $this->hasRole('Cashier');
    }

    public function getIsRegistrarAttribute(): bool
    {
        return $this->hasRole('registrar');
    }

    public function getIsDeptHeadAttribute(): bool
    {
        return $this->hasAnyRole([
            'IT-head-dept',
            'BA-head-dept',
            'HM-head-dept',
        ]);
    }

    public function getViewableCoursesAttribute(): array
    {
        $courses = [];

        if ($this->can('view_IT_students_students')) {
            $courses = array_merge($courses, [1, 6, 10, 13]);
        }

        if ($this->can('view_BA_students_students')) {
            $courses = array_merge($courses, [4, 5, 8, 9]);
        }

        if ($this->can('view_HM_students_students')) {
            return array_merge($courses, [2, 3, 11, 12]);
        }

        return $courses;
    }

    public function getViewTitleCourseAttribute(): string
    {
        $titles = [];

        if ($this->can('view_IT_students_students')) {
            $titles[] = 'IT';
        }

        if ($this->can('view_BA_students_students')) {
            $titles[] = 'BA';
        }

        if ($this->can('view_HM_students_students')) {
            $titles[] = 'HM';
        }

        return implode(', ', $titles);
    }

    // Dpublic function notifications()
    // {
    //     return $this->morphMany(
    //         DatabaseNotification::class,
    //         "notifiable"
    //     )->orderBy("created_at", "desc");
    // }
}
