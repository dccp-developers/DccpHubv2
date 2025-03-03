<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/* CREATE  TABLE test.student_parents_info (
    id                   INT    NOT NULL AUTO_INCREMENT  PRIMARY KEY,
    fathers_name         VARCHAR(100)       ,
    mothers_name         VARCHAR(100)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

 */

final class StudentParentInfo extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'student_parents_info';

    protected $fillable = [
        'fathers_name', 'mothers_name',
    ];

    protected $primaryKey = 'id';

    public function Student()
    {

        return $this->belongsTo(Students::class);
    }
}
