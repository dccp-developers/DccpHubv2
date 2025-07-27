<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ShsStrand
 *
 * @property int $id
 * @property string $strand_name
 * @property string|null $description
 * @property int $track_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property ShsTrack $track
 * @property \Illuminate\Database\Eloquent\Collection|ShsStudent[] $students
 */
final class ShsStrand extends Model
{
    use HasFactory;

    protected $table = 'shs_strands';

    protected $fillable = [
        'strand_name',
        'description',
        'track_id',
    ];

    protected $casts = [
        'track_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the track that this strand belongs to.
     */
    public function track()
    {
        return $this->belongsTo(ShsTracks::class, 'track_id');
    }

    /**
     * Get the students enrolled in this strand.
     */
    public function students()
    {
        return $this->hasMany(ShsStudents::class, 'strand_id');
    }
}
