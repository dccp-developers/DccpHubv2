<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\OauthConnection;
use App\Models\ShsStudents;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\Provider;
use App\Http\Controllers\User\OauthController;
use App\Actions\User\ActiveOauthProviderAction;
use App\Exceptions\OAuthAccountLinkingException;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

use function Pest\Laravel\get;
use function Pest\Laravel\delete;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;

covers(OauthController::class);

beforeEach(function (): void {
    test()->socialiteUser = tap(new SocialiteUser, function ($user): void {
        $user->map([
            'id' => '1',
            'nickname' => 'test',
            'name' => 'Test User',
            'email' => 'test@test.com',
            'avatar' => 'https://github.com/avatar.jpg',
            'user' => ['id' => '123456'],
            'token' => 'test-token',
            'refreshToken' => 'test-refresh-token',
            'expiresIn' => 3600,
        ]);
    });

    // Enable GitHub and GitLab for testing by modifying the config
    config(['oauth.providers' => [
        ['slug' => 'github', 'active' => true, 'icon' => 'mdi:github'],
        ['slug' => 'google', 'active' => true, 'icon' => 'mdi:google'],
        ['slug' => 'gitlab', 'active' => true, 'icon' => 'mdi:gitlab'],
    ]]);
});

function mockSocialiteForRedirect()
{
    $mockSocialite = Mockery::mock(Provider::class);
    $mockSocialite->shouldReceive('redirect')->andReturn(redirect('https://example.com'));
    Socialite::shouldReceive('driver')->andReturn($mockSocialite);

    return $mockSocialite;
}

function mockSocialiteForCallback()
{
    $mockSocialite = Mockery::mock(Provider::class);
    Socialite::shouldReceive('driver')->andReturn($mockSocialite);
    $mockSocialite->shouldReceive('user')->andReturn(test()->socialiteUser);

    return $mockSocialite;
}

test('it redirects to oauth provider', function (): void {
    foreach (app(ActiveOauthProviderAction::class)->handle() as $provider) {
        mockSocialiteForRedirect();
        $response = get(route('oauth.redirect', ['provider' => $provider['slug']]));

        $response->assertRedirect();
        $response->assertStatus(302);
    }
});

test('it fails to redirect to oauth provider with invalid provider', function (): void {
    $response = get(route('oauth.redirect', ['provider' => 'invalid-provider']));

    $response->assertStatus(404);
});

test('it handles oauth callback for new user without authenticated user', function (): void {
    // Create a student record with the test email so the OAuth callback can find it
    ShsStudents::create([
        'student_lrn' => '123456789012',
        'fullname' => 'Test Student',
        'email' => 'test@test.com',
        'grade_level' => '11',
        'track' => 'STEM',
        'gender' => 'Male',
        'civil_status' => 'Single',
        'nationality' => 'Filipino',
        'religion' => 'Catholic',
        'guardian_name' => 'Test Guardian',
        'guardian_contact' => '09123456789',
        'student_contact' => '09123456789',
        'complete_address' => 'Test Address',
    ]);

    mockSocialiteForCallback();

    $initialAccountCount = DB::table('accounts')->count();
    $initialConnectionCount = DB::table('oauth_connections')->count();

    $response = get(route('oauth.callback', ['provider' => 'github']));

    // Debug: Check what actually happened
    $finalAccountCount = DB::table('accounts')->count();
    $finalConnectionCount = DB::table('oauth_connections')->count();

    // Check if user and connection were created
    expect($finalAccountCount)->toBe($initialAccountCount + 1);
    expect($finalConnectionCount)->toBe($initialConnectionCount + 1);

    $response->assertRedirect(config('fortify.home'));

    $connection = OauthConnection::query()->latest()->first();
    expect($connection->provider)->toBe('github')
        ->and($connection->provider_id)->toBe('1')
        ->and($connection->token)->toBe('test-token')
        ->and($connection->refresh_token)->toBe('test-refresh-token');
});

test('it handles oauth callback for existing user without authenticated user', function (): void {
    User::factory()->create(['email' => 'test@test.com']);
    mockSocialiteForCallback();

    $initialConnectionCount = DB::table('oauth_connections')->count();
    $initialAccountCount = DB::table('accounts')->count();

    $response = get(route('oauth.callback', ['provider' => 'github']));

    $response->assertRedirect(route('login'))
        ->assertSessionHas('error', OAuthAccountLinkingException::EXISTING_CONNECTION_ERROR_MESSAGE);

    assertDatabaseCount('oauth_connections', $initialConnectionCount);
    assertDatabaseCount('accounts', $initialAccountCount);
});

test('it handles oauth callback for existing user without authenticated user and other provider', function (): void {
    $user = User::factory()->create(['email' => 'test@test.com']);
    mockSocialiteForCallback();
    $existingConnection = OauthConnection::factory()
        ->for($user)
        ->withProvider('github')
        ->create(['provider_id' => '1']);

    $initialConnectionCount = DB::table('oauth_connections')->count();
    $initialAccountCount = DB::table('accounts')->count();

    $response = get(route('oauth.callback', ['provider' => 'gitlab']));

    $response->assertRedirect(route('login'))
        ->assertSessionHas('error', OAuthAccountLinkingException::EXISTING_CONNECTION_ERROR_MESSAGE);

    assertDatabaseCount('oauth_connections', $initialConnectionCount);
    assertDatabaseCount('accounts', $initialAccountCount);
});

test('it handles invalid state exception without authenticated user', function (): void {
    Socialite::shouldReceive('driver')
        ->with('github')
        ->andThrow(new InvalidStateException);

    $response = get(route('oauth.callback', ['provider' => 'github']));

    $response->assertRedirect(route('login'))
        ->assertSessionHas('error', 'The request timed out. Please try again.');
});

test('it handles oauth callback with existing connection and without authenticated user', function (): void {
    $user = User::factory()->create(['email' => 'test@test.com']);
    mockSocialiteForCallback();

    $existingConnection = OauthConnection::factory()
        ->for($user)
        ->withProvider('github')
        ->create(['provider_id' => '1']);

    $initialConnectionCount = DB::table('oauth_connections')->count();
    $initialAccountCount = DB::table('accounts')->count();

    $response = get(route('oauth.callback', ['provider' => 'github']));

    $response->assertRedirect(config('fortify.home'));

    assertDatabaseCount('oauth_connections', $initialConnectionCount);
    assertDatabaseCount('accounts', $initialAccountCount);

    $connection = OauthConnection::query()->first();
    expect($connection->id)->toBe($existingConnection->id)
        ->and($connection->provider)->toBe('github')
        ->and($connection->provider_id)->toBe('1')
        ->and($connection->user_id)->toBe($user->id)
        ->and($connection->token)->toBe('test-token')
        ->and($connection->refresh_token)->toBe('test-refresh-token');
});

test('it handles linking account with same email for authenticated user', function (): void {
    $user = User::factory()->create(['email' => 'test@test.com']);
    mockSocialiteForCallback();

    $initialConnectionCount = DB::table('oauth_connections')->count();
    $initialAccountCount = DB::table('accounts')->count();

    $response = actingAs($user)
        ->get(route('oauth.callback', ['provider' => 'github']))
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('success', 'Your github account has been linked.');

    assertDatabaseCount('oauth_connections', $initialConnectionCount + 1);
    assertDatabaseCount('accounts', $initialAccountCount);
});

test('it handles oauth callback with mismatched emails for authenticated user', function (): void {
    $user = User::factory()->create(['email' => 'different@example.com']);
    mockSocialiteForCallback();

    $initialConnectionCount = DB::table('oauth_connections')->count();
    $initialAccountCount = DB::table('accounts')->count();

    $response = actingAs($user)
        ->get(route('oauth.callback', ['provider' => 'github']))
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('error', 'The email address from this github does not match your account email.');

    assertDatabaseCount('oauth_connections', $initialConnectionCount);
    assertDatabaseCount('accounts', $initialAccountCount);
});

test('it can not unlink oauth connection without authenticated user', function (): void {
    $user = User::factory()->create();
    $connection = OauthConnection::factory()
        ->for($user)
        ->withProvider('github')
        ->create();

    $initialConnectionCount = DB::table('oauth_connections')->count();
    $initialAccountCount = DB::table('accounts')->count();

    delete(route('oauth.destroy', ['provider' => 'github']))
        ->assertRedirect(route('login'));

    assertDatabaseCount('oauth_connections', $initialConnectionCount);
    assertDatabaseCount('accounts', $initialAccountCount);
});

test('it can unlink oauth connection with authenticated user', function (): void {
    $user = User::factory()->create();
    $connection = OauthConnection::factory()
        ->for($user)
        ->withProvider('github')
        ->create();

    $initialConnectionCount = DB::table('oauth_connections')->count();
    $initialAccountCount = DB::table('accounts')->count();

    actingAs($user)
        ->delete(route('oauth.destroy', ['provider' => 'github']))
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('success', 'Your github account has been unlinked.');

    assertDatabaseCount('oauth_connections', $initialConnectionCount - 1);
    assertDatabaseCount('accounts', $initialAccountCount);
});
