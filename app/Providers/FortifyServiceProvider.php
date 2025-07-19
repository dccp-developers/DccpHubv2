<?php

declare(strict_types=1);

namespace App\Providers;

use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\Route;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\DualAuthenticationAction;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use Illuminate\Support\Facades\RateLimiter;
use App\Actions\User\ActiveOauthProviderAction;
use App\Actions\Fortify\UpdateUserProfileInformation;

final class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Configure custom authentication
        Fortify::authenticateUsing(new DualAuthenticationAction());

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) $request->string(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));

        $this->configureLoginView();
        $this->configureRedirects();
    }

    private function configureLoginView(): void
    {
        Fortify::loginView(fn () => Inertia::render('Auth/Login', [
            'availableOauthProviders' => (new ActiveOauthProviderAction())->handle(),
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]));
    }

    private function configureRedirects(): void
    {
        // Configure login redirect based on user role
        Fortify::redirects('login', function ($request) {
            $user = $request->user();
            return DualAuthenticationAction::getRedirectUrl($user);
        });
    }
}
