<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Faculty;
use App\Models\Students;
use App\Models\ShsStudents;
use App\Enums\UserRole;
use App\Enums\AccountStatus;
use App\Enums\PersonType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Collection;
use Carbon\Carbon;

final class UserAccountService
{
    /**
     * Activate a user account.
     */
    public function activateAccount(User $user): bool
    {
        try {
            $user->update([
                'is_active' => true,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            Log::info('User account activated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'activated_by' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to activate user account', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Deactivate a user account.
     */
    public function deactivateAccount(User $user, ?string $reason = null): bool
    {
        try {
            $user->update(['is_active' => false]);

            // Revoke all API tokens
            $user->tokens()->delete();

            Log::info('User account deactivated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'reason' => $reason,
                'deactivated_by' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to deactivate user account', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Change user role with validation.
     */
    public function changeUserRole(User $user, UserRole $newRole): bool
    {
        try {
            $oldRole = $user->role;

            // Validate role change is allowed
            if (!$this->canChangeRole($user, $newRole)) {
                throw new \InvalidArgumentException('Role change not allowed');
            }

            $user->update(['role' => $newRole->value]);

            Log::info('User role changed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'old_role' => $oldRole,
                'new_role' => $newRole->value,
                'changed_by' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to change user role', [
                'user_id' => $user->id,
                'new_role' => $newRole->value,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Reset user password and send notification.
     */
    public function resetPassword(User $user, bool $sendEmail = true): ?string
    {
        try {
            $newPassword = $this->generateSecurePassword();
            
            $user->update([
                'password' => Hash::make($newPassword),
                'remember_token' => null,
            ]);

            // Revoke all API tokens for security
            $user->tokens()->delete();

            if ($sendEmail) {
                // Send password reset email (implement your email logic here)
                $this->sendPasswordResetEmail($user, $newPassword);
            }

            Log::info('User password reset', [
                'user_id' => $user->id,
                'email' => $user->email,
                'reset_by' => auth()->id(),
                'email_sent' => $sendEmail,
            ]);

            return $newPassword;
        } catch (\Exception $e) {
            Log::error('Failed to reset user password', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send email verification.
     */
    public function sendEmailVerification(User $user): bool
    {
        try {
            // Implement your email verification logic here
            // This could use Laravel's built-in email verification or custom implementation
            
            Log::info('Email verification sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'sent_by' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send email verification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Link user to person record.
     */
    public function linkToPerson(User $user, string $personId, PersonType $personType): bool
    {
        try {
            // Validate person exists
            $person = $this->findPersonRecord($personId, $personType);
            if (!$person) {
                throw new \InvalidArgumentException('Person record not found');
            }

            $user->update([
                'person_id' => $personId,
                'person_type' => $personType->value,
            ]);

            Log::info('User linked to person record', [
                'user_id' => $user->id,
                'person_id' => $personId,
                'person_type' => $personType->value,
                'linked_by' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to link user to person record', [
                'user_id' => $user->id,
                'person_id' => $personId,
                'person_type' => $personType->value,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get user statistics.
     */
    public function getUserStatistics(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'verified_users' => User::whereNotNull('email_verified_at')->count(),
            'unverified_users' => User::whereNull('email_verified_at')->count(),
            'users_by_role' => User::selectRaw('role, COUNT(*) as count')
                ->groupBy('role')
                ->pluck('count', 'role')
                ->toArray(),
            'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count(),
            'users_with_2fa' => User::whereNotNull('two_factor_secret')->count(),
        ];
    }

    /**
     * Get recent user activities.
     */
    public function getRecentActivities(int $limit = 10): Collection
    {
        return User::with(['person'])
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'user' => $user,
                    'activity' => 'Updated',
                    'timestamp' => $user->updated_at,
                ];
            });
    }

    /**
     * Bulk activate users.
     */
    public function bulkActivateUsers(array $userIds): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user && $this->activateAccount($user)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed to activate user ID: {$userId}";
            }
        }

        return $results;
    }

    /**
     * Bulk deactivate users.
     */
    public function bulkDeactivateUsers(array $userIds, ?string $reason = null): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user && $this->deactivateAccount($user, $reason)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed to deactivate user ID: {$userId}";
            }
        }

        return $results;
    }

    /**
     * Check if role change is allowed.
     */
    private function canChangeRole(User $user, UserRole $newRole): bool
    {
        // Implement your business logic for role changes
        // For example, prevent changing admin roles without proper permissions
        
        $currentUser = auth()->user();
        if (!$currentUser) {
            return false;
        }

        // Only admins can change roles to admin
        if ($newRole === UserRole::ADMIN && $currentUser->role !== 'admin') {
            return false;
        }

        return true;
    }

    /**
     * Generate a secure password.
     */
    private function generateSecurePassword(int $length = 12): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle(str_repeat($characters, $length)), 0, $length);
    }

    /**
     * Send password reset email.
     */
    private function sendPasswordResetEmail(User $user, string $newPassword): void
    {
        // Implement your email sending logic here
        // This is a placeholder for the actual email implementation
    }

    /**
     * Find person record by ID and type.
     */
    private function findPersonRecord(string $personId, PersonType $personType): ?object
    {
        return match ($personType) {
            PersonType::STUDENT => Students::find($personId),
            PersonType::FACULTY => Faculty::where('faculty_code', $personId)->first(),
            PersonType::SHS_STUDENT => ShsStudents::where('student_lrn', $personId)->first(),
            default => null,
        };
    }
}
