<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class EnrollmentSignatures extends Model
{
    use HasFactory;

    protected $table = 'enrollment_signatures';

    protected $fillable = [
        'depthead_signature',
        'registrar_signature',
        'cashier_signature',
        'enrollment_id',
        'enrollment_type',
    ];

    public function enrollment()
    {
        return $this->morphTo();
    }
}
