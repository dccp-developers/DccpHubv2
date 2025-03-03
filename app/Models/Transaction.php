<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'description',
        'status',
        'transaction_date',
        'transaction_number',
        'settlements',
        'invoicenumber',
        'signature',
    ];

    protected $casts = [
        'settlements' => 'array',
    ];

    public function getTransactionTypeStringAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->transaction_type));
    }

    public function getStudentFullNameAttribute()
    {
        $student = $this->student()->first();

        return $student->full_name;
    }

    public function getStudentCourseAttribute(): string
    {
        $student = $this->student()->first();

        return $student->course->code.' '.$student->academic_year;
    }

    //  get student email
    public function getStudentEmailAttribute()
    {
        $student = $this->student()->first();

        return $student->email;
    }

    //  get student personal contact
    public function getStudentPersonalContactAttribute()
    {
        $student = $this->student()->first();

        return $student->studentContactsInfo->personal_contact ?? '';
    }

    public function getStudentIdAttribute()
    {
        $student = $this->student()->first();

        return $student->id;
    }

    public function student()
    {
        return $this->belongsToMany(Students::class, 'student_transactions', 'transaction_id', 'student_id');
    }

    public function studentTransactions()
    {
        return $this->hasMany(StudentTransactions::class, 'transaction_id');
    }

    // New method to access individual settlement amounts
    public function getSettlementAmount(string $key): float
    {
        return (float) ($this->settlements[$key] ?? 0.0);
    }
}
