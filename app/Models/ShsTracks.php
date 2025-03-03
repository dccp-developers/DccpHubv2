<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class ShsTracks extends Model
{
    use HasFactory;

    protected $fillable = [
        'track_name',
        'description',
    ];

    public function strands()
    {
        return $this->hasMany(TracksStrands::class, 'track_id');
    }
}
