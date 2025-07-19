<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\Faculty;
use App\Models\Students;
use App\Models\ShsStudents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

final class DualAuthenticationAction
{
    /**
     * Attempt to authenticate the request's credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function __invoke(Request $request)
    {
        $email = $request->input(Fortify::username());
        $password = $request->input('password');

        // First, try to find an existing User account
        $user = User::where('email', $email)->first();
        
        if ($user && Hash::check($password, $user->password)) {
            return $user;
        }

        // If no User account exists, check if the email exists in Students or Faculty
        $personData = CreateNewUser::determinePersonTypeByEmail($email);
        
        if (!$personData) {
            throw ValidationException::withMessages([
                Fortify::username() => [trans('auth.failed')],
            ]);
        }

        // If we found a person but no User account, we need to check if they have a password
        // For now, we'll assume they need to register first or use OAuth
        throw ValidationException::withMessages([
            Fortify::username() => ['Please register your account first or use social login.'],
        ]);
    }

    /**
     * Find or create a user account for the given person.
     *
     * @param  array  $personData
     * @param  string  $password
     * @return \App\Models\User
     */
    private function findOrCreateUserAccount(array $personData, string $password): User
    {
        $person = $personData['person'];
        $personType = $personData['type'];
        $role = $personData['role'];

        // Check if a User account already exists for this person
        $user = User::where('person_id', $person->id)
                   ->where('person_type', $personType)
                   ->first();

        if ($user) {
            return $user;
        }

        // Create a new User account
        return User::create([
            'name' => $this->getPersonFullName($person, $personType),
            'email' => $person->email,
            'password' => Hash::make($password),
            'role' => $role,
            'is_active' => true,
            'person_id' => $person->id,
            'person_type' => $personType,
        ]);
    }

    /**
     * Get the full name of a person based on their type.
     *
     * @param  mixed  $person
     * @param  string  $personType
     * @return string
     */
    private function getPersonFullName($person, string $personType): string
    {
        if ($personType === Faculty::class) {
            return $person->first_name . ' ' . ($person->middle_name ? $person->middle_name . ' ' : '') . $person->last_name;
        }

        if ($personType === Students::class) {
            return $person->first_name . ' ' . ($person->middle_name ? $person->middle_name . ' ' : '') . $person->last_name;
        }

        if ($personType === ShsStudents::class) {
            return $person->fullname;
        }

        return $person->email;
    }

    /**
     * Determine the redirect URL based on user role.
     *
     * @param  \App\Models\User  $user
     * @return string
     */
    public static function getRedirectUrl(User $user): string
    {
        if ($user->role === 'faculty') {
            return route('faculty.dashboard');
        }

        if ($user->role === 'student') {
            return route('dashboard');
        }

        if ($user->role === 'guest') {
            return route('enrolee.dashboard');
        }

        return route('dashboard');
    }
}
