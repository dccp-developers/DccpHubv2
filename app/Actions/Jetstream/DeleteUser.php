<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Jetstream\Contracts\DeletesUsers;

final readonly class DeleteUser implements DeletesUsers
{

    /**
     * Delete the given user.
     */
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->deleteProfilePhoto();
            // Delete all pending enrollments for this user
            \App\Models\PendingEnrollment::whereJsonContains('data->email', $user->email)->delete();
            $user->tokens->each->delete();
            $user->delete();
        });
    }
}
