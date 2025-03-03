<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class TracksStrands extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'description',
        'track_id',
    ];

    public function Track()
    {
        return $this->belongsTo(ShsTracks::class, 'track_id');
    }

    public function Subjects()
    {
        return $this->hasMany(StrandSubjects::class, 'strand_id');
    }
}
