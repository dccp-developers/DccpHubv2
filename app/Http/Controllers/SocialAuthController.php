<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\User as SocialiteUser;
use App\Services\SocialAuthService;
use Laravel\Socialite\Facades\Socialite;
use App\Models\OauthConnection;
use Illuminate\Validation\ValidationException;

class SocialAuthController extends Controller
{
    /**
     * Handle mobile OAuth callback from Capacitor Social Login plugin
     */
    public function handleMobileCallback(Request $request)
    {
        try {
            $request->validate([
                'access_token' => 'required|string',
                'provider' => 'required|string|in:google',
                'user_info' => 'sometimes|array'
            ]);

            $accessToken = $request->input('access_token');
            $provider = $request->input('provider');
            $userInfo = $request->input('user_info', []);

            Log::info('Mobile OAuth callback received', [
                'provider' => $provider,
                'has_access_token' => !empty($accessToken),
                'user_info_keys' => array_keys($userInfo)
            ]);

            // Get user info from Google API using access token
            $googleUser = $this->getUserFromGoogle($accessToken);

            if (!$googleUser) {
                return response()->json([
                    'error' => 'Failed to get user information from Google'
                ], 400);
            }

            // Find or create user using the enhanced service
            $socialAuthService = new SocialAuthService();
            $user = $socialAuthService->findOrCreateUser($googleUser, $provider);

            // Create or update OAuth connection
            OauthConnection::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => $provider,
                ],
                [
                    'provider_id' => $googleUser->getId(),
                    'data' => [
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'avatar' => $googleUser->getAvatar(),
                    ],
                    'expires_at' => null, // Mobile OAuth doesn't provide expiration
                ]
            );

            // Log the user in
            Auth::login($user, true);

            Log::info('Mobile OAuth login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'provider' => $provider
            ]);

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'redirect_url' => route('dashboard')
            ]);

        } catch (ValidationException $e) {
            // Re-throw validation exceptions to let Laravel handle them properly
            throw $e;
        } catch (\InvalidArgumentException $e) {
            // Handle validation errors (email not found in records)
            Log::warning('Mobile OAuth validation error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Validation Error',
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Mobile OAuth callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'OAuth authentication failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exchange authorization code for tokens (deep link fallback)
     */
    public function exchangeCodeForTokens(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|string',
                'redirect_uri' => 'required|string',
                'state' => 'sometimes|string'
            ]);

            $code = $request->input('code');
            $redirectUri = $request->input('redirect_uri');

            Log::info('Token exchange requested', [
                'code_length' => strlen($code),
                'redirect_uri' => $redirectUri
            ]);

            // Use Laravel Socialite to exchange code for tokens
            $socialiteUser = Socialite::driver('google')
                ->redirectUrl($redirectUri)
                ->user();

            if (!$socialiteUser) {
                return response()->json([
                    'error' => 'Failed to exchange code for tokens'
                ], 400);
            }

            // Find or create user using the enhanced service
            $socialAuthService = new SocialAuthService();
            $user = $socialAuthService->findOrCreateUser($socialiteUser, 'google');

            // Log the user in
            Auth::login($user, true);

            Log::info('Token exchange login successful', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'redirect_url' => route('dashboard')
            ]);

        } catch (\InvalidArgumentException $e) {
            // Handle validation errors (email not found in records)
            Log::warning('Token exchange validation error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Validation Error',
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Token exchange error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Token exchange failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user information from Google API using access token
     */
    private function getUserFromGoogle($accessToken)
    {
        try {
            $response = Http::get('https://www.googleapis.com/oauth2/v2/userinfo', [
                'access_token' => $accessToken
            ]);

            if ($response->successful()) {
                $userData = $response->json();

                // Create a Socialite User object from the Google API response
                $socialiteUser = new SocialiteUser();
                $socialiteUser->map([
                    'id' => $userData['id'] ?? null,
                    'name' => $userData['name'] ?? null,
                    'email' => $userData['email'] ?? null,
                    'avatar' => $userData['picture'] ?? null,
                ]);

                return $socialiteUser;
            }

            Log::error('Failed to get user from Google API', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Google API request error', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }


}
