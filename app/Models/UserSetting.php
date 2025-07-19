<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'semester', 'school_year_start'];

    protected $casts = [
        'semester' => 'integer',
        'school_year_start' => 'integer',
    ];

    /**
     * Get the user that owns the settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
