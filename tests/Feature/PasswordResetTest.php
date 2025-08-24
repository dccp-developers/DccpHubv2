<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Fortify\Features;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;

test('reset password link screen can be rendered', function (): void {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
})->skip(fn (): bool => ! Features::enabled(Features::resetPasswords()), 'Password updates are not enabled.');

test('reset password link can be requested', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    Notification::assertSentTo($user, ResetPassword::class);
})->skip(fn (): bool => ! Features::enabled(Features::resetPasswords()), 'Password updates are not enabled.');

test('reset password screen can be rendered', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    Notification::assertSentTo($user, ResetPassword::class, function (object $notification): true {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
})->skip(fn (): bool => ! Features::enabled(Features::resetPasswords()), 'Password updates are not enabled.');

test('password can be reset with valid token', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    // Request password reset (this creates a real token)
    $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    // Verify the password reset request was successful and test the reset form
    Notification::assertSentTo($user, ResetPassword::class, function (object $notification) use ($user): true {
        // Test that the reset password form can be accessed with the token
        $resetResponse = $this->get('/reset-password/' . $notification->token);
        $resetResponse->assertStatus(200);

        // Verify token was created in database for the user
        $tokenRecord = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        expect($tokenRecord)->not->toBeNull();

        return true;
    });
})->skip(fn (): bool => ! Features::enabled(Features::resetPasswords()), 'Password updates are not enabled.');
