<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Stripe\Customer;
use App\Models\Faculty;
use App\Models\Students;
use App\Models\ShsStudents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:accounts'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string'],
            'id' => ['required', 'string'],
            // 'person_type' => ['required', 'string'],
        ])->validate();

        // Determine person type and get actual person ID
        $personData = $this->getPersonData($input['id']);

        if (!$personData) {
            throw new \Exception('Person not found with the provided ID.');
        }

        return DB::transaction(fn () => tap(User::query()->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'username' => $input['name'],
            'phone' => $input['phone'],
            'password' => Hash::make($input['password']),
            'role' => $personData['type'] === Faculty::class ? 'faculty' : 'student',
            'is_active' => true,
            'person_id' => $personData['person_id'],
            'person_type' => $personData['type'],
        ]), function (User $user): void {
            // $this->createTeam($user);
            $this->createCustomer($user);
        }));
    }

    /**
     * Create a billing customer for the user.
     */
    private function createCustomer(User $user): void
    {
        if (! Config::get('cashier.billing_enabled')) {
            return;
        }

        /** @var Customer $stripeCustomer */
        $stripeCustomer = $user->createOrGetStripeCustomer();

        $user->update([
            'stripe_id' => $stripeCustomer->id,
        ]);
    }

    /**
     * Get person data including type and actual person ID.
     */
    private function getPersonData(string $inputId): ?array
    {
        // Check Faculty by faculty_code first, then by UUID
        $faculty = Faculty::query()->where('faculty_code', $inputId)->first();
        if (!$faculty) {
            $faculty = Faculty::query()->where('id', $inputId)->first();
        }
        if ($faculty) {
            return [
                'type' => Faculty::class,
                'person_id' => $faculty->id, // Store the actual UUID
                'person' => $faculty
            ];
        }

        // Check Students table (only if inputId is numeric to avoid bigint errors)
        if (is_numeric($inputId)) {
            $student = Students::query()->where('id', $inputId)->first();
            if ($student) {
                return [
                    'type' => Students::class,
                    'person_id' => $student->id,
                    'person' => $student
                ];
            }
        }

        // Check SHS Students
        $shsStudent = ShsStudents::query()->where('student_lrn', $inputId)->first();
        if ($shsStudent) {
            return [
                'type' => ShsStudents::class,
                'person_id' => $shsStudent->student_lrn,
                'person' => $shsStudent
            ];
        }

        return null;
    }

    /**
     * Determine the person type based on the ID.
     */
    private function determinePersonType(string $studentId): ?string
    {
        $personData = $this->getPersonData($studentId);
        return $personData ? $personData['type'] : null;
    }

    /**
     * Determine the person type based on email for authentication.
     */
    public static function determinePersonTypeByEmail(string $email): ?array
    {
        // Check Students table first
        $student = Students::query()->where('email', $email)->first();
        if ($student) {
            return [
                'type' => Students::class,
                'person' => $student,
                'role' => 'student'
            ];
        }

        // Check Faculty table
        $faculty = Faculty::query()->where('email', $email)->first();
        if ($faculty) {
            return [
                'type' => Faculty::class,
                'person' => $faculty,
                'role' => 'faculty'
            ];
        }

        // Check ShsStudents table
        $shsStudent = ShsStudents::query()->where('email', $email)->first();
        if ($shsStudent) {
            return [
                'type' => ShsStudents::class,
                'person' => $shsStudent,
                'role' => 'student'
            ];
        }

        return null;
    }
}
