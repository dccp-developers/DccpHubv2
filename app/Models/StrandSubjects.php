<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class StrandSubjects extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'description',
        'grade_year',
        'semester',
        'strand_id',
    ];

    public function strand()
    {
        return $this->belongsTo(TracksStrands::class, 'strand_id');
    }
}
