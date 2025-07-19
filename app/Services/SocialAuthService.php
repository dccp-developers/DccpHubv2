<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Faculty;
use App\Models\Students;
use App\Models\ShsStudents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Actions\Fortify\CreateNewUser;
use Laravel\Socialite\Two\User as SocialiteUser;

final class SocialAuthService
{
    /**
     * Find or create a user from social login with proper role detection.
     */
    public function findOrCreateUser(SocialiteUser $socialiteUser, string $provider): User
    {
        $email = $socialiteUser->getEmail();
        $name = $socialiteUser->getName();
        $providerId = $socialiteUser->getId();
        $avatar = $socialiteUser->getAvatar();

        Log::info('Social login attempt', [
            'email' => $email,
            'provider' => $provider,
            'name' => $name
        ]);

        // First, check if user already exists
        $existingUser = User::where('email', $email)->first();
        
        if ($existingUser) {
            return $this->updateExistingUser($existingUser, $socialiteUser, $provider);
        }

        // Check if email exists in Student or Faculty tables
        $personData = CreateNewUser::determinePersonTypeByEmail($email);
        
        if (!$personData) {
            Log::warning('Social login failed: Email not found in Student/Faculty records', [
                'email' => $email,
                'provider' => $provider
            ]);

            throw new \InvalidArgumentException(
                'Your email address (' . $email . ') is not found in our Student or Faculty records. ' .
                'Please contact the administration to ensure your email is registered in the system before using social login. ' .
                'Only registered students and faculty members can use social login.'
            );
        }

        return $this->createNewUserWithRole($socialiteUser, $provider, $personData);
    }

    /**
     * Update existing user with social login information.
     */
    private function updateExistingUser(User $user, SocialiteUser $socialiteUser, string $provider): User
    {
        $updateData = [
            'name' => $socialiteUser->getName(),
            'email_verified_at' => now()
        ];

        // Add provider-specific fields
        if ($provider === 'google') {
            $updateData['google_id'] = $socialiteUser->getId();
        }

        if ($socialiteUser->getAvatar()) {
            $updateData['avatar'] = $socialiteUser->getAvatar();
        }

        $user->update($updateData);

        Log::info('Existing user updated via social login', [
            'user_id' => $user->id,
            'email' => $user->email,
            'provider' => $provider
        ]);

        return $user;
    }

    /**
     * Create new user with proper role detection and person linking.
     */
    private function createNewUserWithRole(SocialiteUser $socialiteUser, string $provider, array $personData): User
    {
        $person = $personData['person'];
        $personType = $personData['type'];
        $role = $personData['role'];

        return DB::transaction(function () use ($socialiteUser, $provider, $person, $personType, $role) {
            $userData = [
                'name' => $socialiteUser->getName(),
                'email' => $socialiteUser->getEmail(),
                'password' => Hash::make(str()->random(32)), // Random password for OAuth users
                'role' => $role,
                'is_active' => true,
                'person_id' => $person->id,
                'person_type' => $personType,
                'email_verified_at' => now()
            ];

            // Add provider-specific fields
            if ($provider === 'google') {
                $userData['google_id'] = $socialiteUser->getId();
            }

            if ($socialiteUser->getAvatar()) {
                $userData['avatar'] = $socialiteUser->getAvatar();
            }

            $user = User::create($userData);

            Log::info('New user created via social login', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $role,
                'person_type' => $personType,
                'person_id' => $person->id,
                'provider' => $provider
            ]);

            return $user;
        });
    }

    /**
     * Validate that an email exists in Student or Faculty records.
     */
    public function validateEmailInRecords(string $email): bool
    {
        return CreateNewUser::determinePersonTypeByEmail($email) !== null;
    }

    /**
     * Get person data by email for role determination.
     */
    public function getPersonDataByEmail(string $email): ?array
    {
        return CreateNewUser::determinePersonTypeByEmail($email);
    }
}
