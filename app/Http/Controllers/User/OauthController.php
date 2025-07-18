<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use Throwable;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Laravel\Socialite\Facades\Socialite;
use App\Actions\User\ActiveOauthProviderAction;
use App\Actions\User\HandleOauthCallbackAction;
use App\Exceptions\OAuthAccountLinkingException;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use App\Http\Middleware\DetectMobileApp;

final class OauthController extends Controller
{
    public function __construct(
        private readonly HandleOauthCallbackAction $handleOauthCallbackAction,
    ) {}

    public function redirect(string $provider): SymfonyRedirectResponse
    {
        abort_unless($this->isValidProvider($provider), 404);

        // Check if this is a mobile app request with custom redirect URI
        $redirectUri = request()->get('redirect_uri');
        $isMobile = request()->get('mobile') === 'true';

        if ($isMobile && $redirectUri) {
            // Store the custom redirect URI in session for callback
            session(['oauth_redirect_uri' => $redirectUri]);
            session(['oauth_is_mobile' => true]);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless($this->isValidProvider($provider), 404);

        try {
            /** @var SocialiteUser $socialiteUser */
            $socialiteUser = Socialite::driver($provider)->user();
            $authenticatedUser = Auth::user();
            $user = $this->handleOauthCallbackAction->handle($provider, $socialiteUser, $authenticatedUser);
        } catch (InvalidStateException) {
            return Redirect::intended(Auth::check() ? route('profile.show') : route('login'))->with('error', __('The request timed out. Please try again.'));
        } catch (OAuthAccountLinkingException $oauthAccountLinkingException) {
            return Redirect::intended(Auth::check() ? route('profile.show') : route('login'))->with('error', $oauthAccountLinkingException->getMessage());
        } catch (Throwable $throwable) {
            report($throwable);

            return Redirect::intended(Auth::check() ? route('profile.show') : route('login'))->with('error', __('An error occurred during authentication. Please try again.'));
        }

        if (Auth::guest()) {
            Auth::login($user, true);

            // Check if this is a mobile app request or has custom redirect URI
            $customRedirectUri = session('oauth_redirect_uri');
            $isMobileFromSession = session('oauth_is_mobile');
            $isMobileApp = DetectMobileApp::isMobileApp(request()) || $isMobileFromSession;

            if ($customRedirectUri && $isMobileApp) {
                // Clear session data
                session()->forget(['oauth_redirect_uri', 'oauth_is_mobile']);

                // Redirect to deep link with success parameters
                $deepLinkUrl = $customRedirectUri . '?authenticated=true&success=true&provider=' . $provider;
                return Redirect::to($deepLinkUrl);
            } elseif ($isMobileApp) {
                // For mobile app without custom redirect, redirect to dashboard with a special parameter
                return Redirect::to(config('fortify.home') . '?authenticated=true&mobile=true');
            }

            return Redirect::intended(config('fortify.home'));
        }

        // For authenticated users linking accounts
        $customRedirectUri = session('oauth_redirect_uri');
        $isMobileFromSession = session('oauth_is_mobile');
        $isMobileApp = DetectMobileApp::isMobileApp(request()) || $isMobileFromSession;

        if ($customRedirectUri && $isMobileApp) {
            // Clear session data
            session()->forget(['oauth_redirect_uri', 'oauth_is_mobile']);

            // Redirect to deep link with linking success parameters
            $deepLinkUrl = $customRedirectUri . '?linked=true&success=true&provider=' . $provider;
            return Redirect::to($deepLinkUrl);
        } elseif ($isMobileApp) {
            return Redirect::to(route('profile.show') . '?linked=true&mobile=true')->with('success', "Your {$provider} account has been linked.");
        }

        return Redirect::intended(route('profile.show'))->with('success', "Your {$provider} account has been linked.");
    }

    public function destroy(string $provider): RedirectResponse
    {
        abort_unless($this->isValidProvider($provider), 404);

        $user = Auth::user();

        $user?->oauthConnections()->where('provider', $provider)->delete();
        session()->flash('success', "Your {$provider} account has been unlinked.");

        return Redirect::route('profile.show');
    }

    private function isValidProvider(string $provider): bool
    {
        $activeProviders = (new ActiveOauthProviderAction)->handle();

        return collect($activeProviders)->contains('slug', $provider);
    }
}
