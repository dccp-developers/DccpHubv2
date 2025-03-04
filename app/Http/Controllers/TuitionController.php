<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Students;
use App\Models\StudentTuition;
use App\Models\GeneralSettings;
use App\Models\StudentTransactions;
use Illuminate\Support\Facades\Auth;

final class TuitionController extends Controller
{
    public function index(): Response
    {
        /** @var User $user */
        $user = Auth::user();

        abort_unless($user->student, 403, 'Only students can access this page.');

        /** @var Students $student */
        $student = $user->student;

        // Get the student's tuition for the current semester and academic year.
        $currentSemester = GeneralSettings::query()->first()->semester;
        $currentSchoolYear = GeneralSettings::query()->first()->getSchoolYear();

        $tuition = StudentTuition::query()
            ->where('student_id', $student->id)
            ->where('semester', $currentSemester)
            ->where('school_year', $currentSchoolYear)
            ->first();

        abort_unless($tuition, 404, 'Tuition information not found for the current semester.');

        // Get the student's transactions.
        $transactions = StudentTransactions::query()
            ->where('student_id', $student->id)
            ->with(['transaction']) // Eager load the transaction and student
            ->get()
            ->map(function (StudentTransactions $studentTransaction): array {
                $transaction = $studentTransaction->transaction; // Get the related transaction

                return [
                    'id' => $studentTransaction->id,
                    'amount' => $studentTransaction->amount, // This is the *total* amount for the transaction
                    'status' => $studentTransaction->status,
                    'transaction_number' => $transaction->transaction_number,
                    'transaction_date' => $transaction->transaction_date,
                    'description' => $transaction->description, // Fallback description
                    'settlements' => $transaction->settlements, // Include the raw settlements
                ];
            });

        return Inertia::render('Tuition/Index', [
            'tuition' => $tuition,
            'transactions' => $transactions,
            'student' => $student,
        ]);
    }
}
