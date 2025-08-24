<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Students;
use App\Models\OauthConnection;
use App\Actions\User\HandleOauthCallbackAction;
use App\Exceptions\OAuthAccountLinkingException;
use Laravel\Socialite\Two\User as SocialiteUser;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    // Generate unique provider ID and email for each test to avoid conflicts
    $uniqueId = uniqid('test_');
    $uniqueEmail = 'john_' . $uniqueId . '@example.com';

    $this->socialiteUser = tap(new SocialiteUser, function ($user) use ($uniqueId, $uniqueEmail): void {
        $user->map([
            'id' => $uniqueId,
            'nickname' => 'johndoe',
            'name' => 'John Doe',
            'email' => $uniqueEmail,
            'avatar' => 'https://example.com/avatar.jpg',
            'token' => 'oauth-token',
            'refreshToken' => 'refresh-token',
            'expiresIn' => 3600,
        ]);
    });

    $this->uniqueProviderId = $uniqueId;
    $this->uniqueEmail = $uniqueEmail;

    // Create a student record with the unique email and ID so social auth can find it
    $uniqueStudentId = 2024000000 + (int) (microtime(true) * 1000) % 999999; // Generate unique student ID based on timestamp
    $this->student = Students::factory()->create([
        'id' => $uniqueStudentId,
        'email' => $uniqueEmail
    ]);
});

test('it creates new user when handling unauthenticated user', function (): void {
    $result = (new HandleOauthCallbackAction())->handle('github', $this->socialiteUser);

    expect($result)
        ->toBeInstanceOf(User::class)
        ->and($result->name)->toBe('John Doe')
        ->and($result->email)->toBe($this->uniqueEmail);

    assertDatabaseHas('oauth_connections', [
        'user_id' => $result->id,
        'provider' => 'github',
        'provider_id' => $this->uniqueProviderId,
    ]);
});

test('it links oauth account to authenticated user with matching email', function (): void {
    $user = User::factory()->create(['email' => $this->uniqueEmail]);

    $result = (new HandleOauthCallbackAction())->handle('github', $this->socialiteUser, $user);

    expect($result->id)->toBe($user->id);

    assertDatabaseHas('oauth_connections', [
        'user_id' => $user->id,
        'provider' => 'github',
        'provider_id' => $this->uniqueProviderId,
    ]);
});

test('it throws exception when emails do not match for authenticated user', function (): void {
    $user = User::factory()->create(['email' => 'different@example.com']);

    expect(fn (): User => (new HandleOauthCallbackAction())->handle('github', $this->socialiteUser, $user))
        ->toThrow(
            OAuthAccountLinkingException::class,
            'The email address from this github does not match your account email.'
        );
});

test('it throws exception when oauth connection exists for different user', function (): void {
    $existingUser = User::factory()->create();
    $newUser = User::factory()->create(['email' => $this->uniqueEmail]);

    OauthConnection::factory()->create([
        'user_id' => $existingUser->id,
        'provider' => 'github',
        'provider_id' => $this->uniqueProviderId,
    ]);

    expect(fn (): User => (new HandleOauthCallbackAction())->handle('github', $this->socialiteUser, $newUser))
        ->toThrow(InvalidArgumentException::class, 'Validation error try again later.');
});

test('it throws exception when trying to connect to existing user without oauth connection', function (): void {
    User::factory()->create(['email' => $this->uniqueEmail]);

    expect(fn (): User => (new HandleOauthCallbackAction())->handle('github', $this->socialiteUser))
        ->toThrow(
            OAuthAccountLinkingException::class,
            'Please login with your existing authentication method.'
        );
});

test('it handles existing user with oauth connection', function (): void {
    $user = User::factory()->create(['email' => $this->uniqueEmail]);

    OauthConnection::factory()->create([
        'user_id' => $user->id,
        'provider' => 'github',
        'provider_id' => $this->uniqueProviderId,
    ]);

    $result = (new HandleOauthCallbackAction())->handle('github', $this->socialiteUser);

    expect($result->id)->toBe($user->id);

    assertDatabaseHas('oauth_connections', [
        'user_id' => $user->id,
        'provider' => 'github',
        'provider_id' => $this->uniqueProviderId,
    ]);
});
