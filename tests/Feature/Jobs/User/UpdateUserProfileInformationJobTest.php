<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\OauthConnection;
use Laravel\Socialite\Two\User as SocialiteUser;
use App\Jobs\User\UpdateUserProfileInformationJob;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseCount;

covers(UpdateUserProfileInformationJob::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->socialiteUser = new SocialiteUser;
    $this->socialiteUser->map([
        'id' => '123456789',
        'name' => $this->user->name,
        'email' => $this->user->email,
        'avatar' => fake()->imageUrl(),
        'user' => [
            'id' => 123456789,
        ],
        'token' => 'test-token',
        'refreshToken' => 'test-refresh-token',
        'expiresIn' => null,
    ]);
});

test('creates new oauth connection when none exists', function (): void {
    $job = new UpdateUserProfileInformationJob(
        $this->user,
        $this->socialiteUser,
        'github'
    );

    $job->handle();

    assertDatabaseHas('oauth_connections', [
        'user_id' => $this->user->id,
        'provider' => 'github',
        'provider_id' => '123456789',
        'token' => 'test-token',
        'refresh_token' => 'test-refresh-token',
    ]);

    $connection = OauthConnection::query()->first();
    expect($connection->data)->toEqual(collect(['id' => '123456789']));
});

test('updates existing oauth connection', function (): void {
    OauthConnection::factory()
        ->withProvider('github')
        ->create([
            'user_id' => $this->user->id,
            'provider_id' => 'old-id',
            'token' => 'old-token',
            'refresh_token' => 'old-refresh-token',
        ]);

    $job = new UpdateUserProfileInformationJob(
        $this->user,
        $this->socialiteUser,
        'github'
    );

    $job->handle();

    assertDatabaseHas('oauth_connections', [
        'user_id' => $this->user->id,
        'provider' => 'github',
        'provider_id' => '123456789',
        'token' => 'test-token',
        'refresh_token' => 'test-refresh-token',
    ]);

    assertDatabaseCount('oauth_connections', 1);
});

test('can handle null expiration time correctly', function (): void {
    $job = new UpdateUserProfileInformationJob(
        $this->user,
        $this->socialiteUser,
        'github'
    );

    $job->handle();

    $connection = OauthConnection::query()->first();
    $this->assertNull($connection->expires_at);
});

test('it updates user profile photo path from socialite avatar', function (): void {
    $job = new UpdateUserProfileInformationJob(
        $this->user,
        $this->socialiteUser,
        'github'
    );

    $job->handle();

    assertDatabaseHas('accounts', [
        'id' => $this->user->id,
        'profile_photo_path' => $this->socialiteUser->getAvatar(),
    ]);
});
