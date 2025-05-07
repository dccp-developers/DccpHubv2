<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Models\User;
use App\Models\LoginLink;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notifications\LoginLinkMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;

use function Illuminate\Support\defer;

final class LoginLinkController extends Controller
{
    private const string RATE_LIMIT_PREFIX = 'login-link:';

    private const int RATE_LIMIT_ATTEMPTS = 5;

    private const int EXPIRATION_TIME = 15;

    /**
     * Create a new magic link.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        /** @var array<string, string> $validated */
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $validated['email'];
        $key = self::RATE_LIMIT_PREFIX.$email;

        // Rate limit check - 1 attempt per minute
        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            // Return JSON for Inertia or AJAX requests
            if ($request->expectsJson() || $request->header('X-Inertia')) {
                return response()->json([
                    'message' => __('Please wait :seconds seconds before requesting another magic link.', ['seconds' => $seconds]),
                ], 429);
            }

            // session()->flash('error', __('Please wait :seconds seconds before requesting another magic link.', ['seconds' => $seconds]));
            return redirect()->back();
        }

        $user = User::query()->where('email', $email)->firstOrFail();

        // Increment the rate limiter
        RateLimiter::increment($key);

        $magicLink = LoginLink::query()->create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->addMinutes(self::EXPIRATION_TIME),
        ]);

        defer(fn () => $user->notify(new LoginLinkMail($magicLink)), 'login-link-notification');

        session()->flash('success', __('Magic link sent to your email!'));

        return redirect()->back();
    }

    /**
     * Login with a magic link.
     */
    public function login(string $token): RedirectResponse
    {
        $magicLink = LoginLink::query()->where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $magicLink->update(['used_at' => now()]);
        Auth::login($magicLink->user, true);

        return redirect()->route('dashboard');
    }
}
