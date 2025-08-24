<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Fortify\Features;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;

test('email verification screen can be rendered', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)->get('/email/verify');

    $response->assertStatus(200);
})->skip(fn (): bool => ! Features::enabled(Features::emailVerification()), 'Email verification not enabled.');

test('email can be verified', function (): void {
    Event::fake();

    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
})->skip(fn (): bool => ! Features::enabled(Features::emailVerification()), 'Email verification not enabled.');

test('email can not verified with invalid hash', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
})->skip(fn (): bool => ! Features::enabled(Features::emailVerification()), 'Email verification not enabled.');
