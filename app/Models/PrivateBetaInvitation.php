<?php

declare(strict_types=1);

// app/Models/PrivateBetaInvitation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PrivateBetaInvitation extends Model
{
    protected $fillable = [
        'email',
        'status',
        'access_code',
        'expire_at',
    ];

    protected $casts = [
        'expire_at' => 'datetime',
        'num_requests' => 'integer',
        'last_access_at' => 'datetime',
    ];
}
