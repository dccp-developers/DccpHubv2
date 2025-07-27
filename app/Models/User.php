<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Panel;
use Carbon\CarbonImmutable;
use Laravel\Cashier\Billable;
use Laravel\Jetstream\HasTeams;
use Laravel\Cashier\Subscription;
use Laravel\Sanctum\HasApiTokens;
use Database\Factories\UserFactory;
use Laravel\Jetstream\HasProfilePhoto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\PersonalAccessToken;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotificationCollection;
use NotificationChannels\WebPush\HasPushSubscriptions;

use function Illuminate\Events\queueable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property string|null $profile_photo_path
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string|null $deleted_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property string|null $trial_ends_at
 * @property-read Team|null $currentTeam
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, OauthConnection> $oauthConnections
 * @property-read int|null $oauth_connections_count
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read int|null $owned_teams_count
 * @property-read Collection<int, Team> $ownedTeamsBase
 * @property-read int|null $owned_teams_base_count
 * @property-read string $profile_photo_url
 * @property-read Collection<int, Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read Membership|null $membership
 * @property-read Collection<int, Team> $teams
 * @property-read int|null $teams_count
 * @property-read Collection<int, PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User hasExpiredGenericTrial()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onGenericTrial()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurrentTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePmLastFour($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePmType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProfilePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStripeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTrialEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class User extends Authenticatable implements FilamentUser
{
    use Billable;


    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasApiTokens;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasPushSubscriptions;

    protected $table = 'accounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'person_id',
        'person_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    // protected $appends = [
    //     'profile_photo_url',
    // ];

    /**
     * Get the team that the invitation belongs to.
     *
     * @return HasMany<Team, covariant $this>
     */
    public function ownedTeams(): HasMany
    {
        return $this->ownedTeamsBase();
    }

    /**
     * Get the Oauth Connections for the user.
     *
     * @return HasMany<OauthConnection, covariant $this>
     */
    public function oauthConnections(): HasMany
    {
        return $this->hasMany(OauthConnection::class);
    }

    /**
     * Configure the panel access.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return false;
    }

    public function person()
    {
        return $this->morphTo();
    }
    public function getProfilePhotoUrlAttribute()
    {
        if (!$this->profile_photo_path) {
            return null;
        }

        // If profile_photo_path starts with http:// or https://, it's already a URL (from OAuth)
        if (str_starts_with($this->profile_photo_path, 'http://') ||
            str_starts_with($this->profile_photo_path, 'https://')) {
            return $this->profile_photo_path;
        }

        // Get the configured profile photo disk from Jetstream config
        $disk = config('jetstream.profile_photo_disk', 'public');

        // Return URL from the configured disk
        return Storage::disk($disk)->url($this->profile_photo_path);
    }

    public function UserPerson()
    {
        return $this->morphTo();
    }

    /**
     * Get the student record if this user is a student.
     */
    public function student()
    {
        return $this->belongsTo(Students::class, 'person_id', 'id');
    }

    /**
     * Get the faculty record if this user is a faculty.
     */
    public function faculty()
    {
        // Match by email for simplicity and reliability
        return $this->hasOne(Faculty::class, 'email', 'email');
    }

    /**
     * Get the SHS student record if this user is an SHS student.
     */
    public function shsStudent()
    {
        return $this->belongsTo(ShsStudents::class, 'person_id', 'student_lrn');
    }

    public function getIsStudentAttribute()
    {
        return $this->hasOne(Students::class, 'person_id');
    }

    public function getIsFacultyAttribute()
    {
        return $this->hasOne(Faculty::class, 'person_id');
    }

    /**
     * Check if the user is a student.
     */
    public function isStudent(): bool
    {
        return $this->role === 'student' &&
               ($this->person_type === Students::class || $this->person_type === ShsStudents::class);
    }

    /**
     * Check if the user is a faculty member.
     */
    public function isFaculty(): bool
    {
        return $this->role === 'faculty' && $this->faculty()->exists();
    }

    /**
     * Get the person record (polymorphic relationship).
     */
    public function getPerson()
    {
        if ($this->person_type === Students::class) {
            return $this->student;
        }

        if ($this->person_type === Faculty::class) {
            return $this->faculty;
        }

        if ($this->person_type === ShsStudents::class) {
            return $this->shsStudent;
        }

        return null;
    }

    protected static function booted(): void
    {
        self::updated(queueable(function (User $customer): void {
            if ($customer->hasStripeId()) {
                $customer->syncStripeCustomerDetails();
            }
        }));
    }

    // public function getPhotoUrl(): Attribute
    // {
    //     return Attribute::get(fn() => $this->profile_photo_path
    //         ? Storage::disk('s3')->url($this->profile_photo_path)
    //         : null);
    // }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's approved pending enrollment if the user is a guest.
     */
    public function getApprovedPendingEnrollmentAttribute()
    {
        if ($this->role !== 'guest') {
            return null;
        }
        $enrollment = \App\Models\PendingEnrollment::where(function ($query) {
            $query->whereJsonContains('data->email', $this->email)
                  ->orWhereJsonContains('data->enrollment_google_email', $this->email);
        })->where('status', 'approved')->first();
        return $enrollment;
    }

    /**
     * Get the faculty notifications for the user.
     */
    public function facultyNotifications(): HasMany
    {
        return $this->hasMany(FacultyNotification::class);
    }

    /**
     * Get unread faculty notifications for the user.
     */
    public function unreadFacultyNotifications(): HasMany
    {
        return $this->facultyNotifications()->unread()->notExpired();
    }

    /**
     * Get the count of unread faculty notifications.
     */
    public function getUnreadNotificationCountAttribute(): int
    {
        return $this->unreadFacultyNotifications()->count();
    }
}
