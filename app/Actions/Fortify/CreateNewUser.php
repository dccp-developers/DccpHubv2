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
            'id' => ['required', 'integer'],
            // 'person_type' => ['required', 'string'],
        ])->validate();
        // Determine person type
        $personType = $this->determinePersonType($input['id']);

        return DB::transaction(fn () => tap(User::query()->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'username' => $input['name'],
            'phone' => $input['phone'],
            'password' => Hash::make($input['password']),
            'role' => $personType === Faculty::class ? 'faculty' : 'student',
            'is_active' => true,
            'person_id' => $input['id'],
            'person_type' => $personType,
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
     * Determine the person type based on the ID.
     */
    private function determinePersonType(string $studentId): ?string
    {
        if (Students::query()->where('id', $studentId)->exists()) {
            return Students::class;
        }

        if (Faculty::query()->where('id', $studentId)->exists()) {
            return Faculty::class;
        }

        if (ShsStudents::query()->where('student_lrn', $studentId)->exists()) {
            return ShsStudents::class;
        }

        return null;
    }
}
