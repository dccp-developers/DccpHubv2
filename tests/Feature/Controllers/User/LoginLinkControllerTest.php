<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\LoginLink;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Notifications\LoginLinkMail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Notification;
use App\Http\Controllers\User\LoginLinkController;
use Illuminate\Routing\Middleware\ThrottleRequests;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\withoutMiddleware;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertAuthenticatedAs;

covers(LoginLinkController::class);

beforeEach(function (): void {
    RateLimiter::clear('login-link:test@example.com');
    withoutMiddleware(ThrottleRequests::class);
    Notification::fake();
});

describe('can create login link', function (): void {
    it('creates login link and sends notification', function (): void {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = post(route('login-link.store'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        assertDatabaseHas('login_links', [
            'user_id' => $user->id,
            'used_at' => null,
        ]);

        Notification::assertSentTo($user, LoginLinkMail::class);
        Notification::assertCount(1);
    });

    it('validates email exists', function (): void {
        $initialLinkCount = DB::table('login_links')->count();

        $response = post(route('login-link.store'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors(['email']);
        assertDatabaseCount('login_links', $initialLinkCount);
        Notification::assertNothingSent();
    });

    it('requires valid email format', function (): void {
        $initialLinkCount = DB::table('login_links')->count();

        $response = post(route('login-link.store'), [
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors(['email']);
        assertDatabaseCount('login_links', $initialLinkCount);
        Notification::assertNothingSent();
    });

    it('rate limits requests', function (): void {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $initialLinkCount = DB::table('login_links')->count();

        // First request should succeed
        post(route('login-link.store'), ['email' => $user->email])
            ->assertSessionHas('success');

        // Second request within 1 minute should fail
        post(route('login-link.store'), ['email' => $user->email])
            ->assertSessionHas('error');

        assertDatabaseCount('login_links', $initialLinkCount + 1);
    });
});

describe('can login with the link', function (): void {
    it('authenticates user with valid token', function (): void {
        $user = User::factory()->create();

        Str::createRandomStringsUsing(fn (): string => 'fake-random-string');
        post(route('login-link.store'), [
            'email' => $user->email,
        ]);

        Str::createRandomStringsNormally();

        $url = URL::signedRoute('login-link.login', ['token' => 'fake-random-string']);

        $response = get($url);

        $response->assertRedirect(route('dashboard'));

        assertAuthenticatedAs($user);
    });

    it('fails with expired token', function (): void {
        $loginLink = LoginLink::factory()->create([
            'expires_at' => now()->subMinute(),
            'used_at' => null,
        ]);

        $response = get(route('login-link.login', ['token' => 'fake-random-string']));

        $response->assertForbidden();

        assertGuest();
    });

    it('fails with already used token', function (): void {
        LoginLink::factory()->create([
            'token' => 'fake-random-string',
            'expires_at' => now()->addMinutes(15),
            'used_at' => now()->subMinute(),
        ]);

        $response = get(route('login-link.login', ['token' => 'fake-random-string']));

        $response->assertForbidden();

        assertGuest();
    });

    it('fails with invalid token', function (): void {
        $response = get(route('login-link.login', ['token' => 'invalid-token']));

        $response->assertForbidden();

        assertGuest();
    });
});
