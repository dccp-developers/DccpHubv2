<?php

namespace App\Http\Controllers;

use App\Models\PendingEnrollment; // Import PendingEnrollment model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Laravel\Socialite\Facades\Socialite;
use Inertia\Inertia;

class EnrollmentAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToGoogle()
    {
        // Store the intended URL (enrollment form) before redirecting
        session(['enrollment_intended_url' => route('enroll')]);
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google and store email in session.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user(); // Use stateless for API-like flow

            if (!$googleUser || !$googleUser->email) {
                Log::error('Google callback failed to retrieve user email.');
                return Redirect::route('enroll')->with('error', 'Failed to retrieve email from Google. Please try again.');
            }

            $email = $googleUser->getEmail();
            $name = $googleUser->getName();
            $avatar = $googleUser->getAvatar();

            // Check if a pending enrollment already exists for this email
            $existingPending = PendingEnrollment::whereJsonContains('data->email', $email)
                                                ->where('status', 'pending') // Only check pending ones
                                                ->first();

            if ($existingPending) {
                 Log::warning("Attempt to start new enrollment with existing pending email: {$email}");
                 // Redirect back with a specific message indicating an existing pending application
                 return Redirect::route('enroll')->with('warning', 'You already have a pending enrollment application associated with this email address. Please wait for it to be processed.');
            }


            // Store the Google user info in the session
            $request->session()->put('enrollment_google_email', $email);
            $request->session()->put('enrollment_google_name', $name);
            $request->session()->put('enrollment_google_avatar', $avatar);
            Log::info("Stored enrollment Google info in session: {$email} ({$name})");

            // Redirect back to the enrollment form (or the intended URL)
            $intendedUrl = session('enrollment_intended_url', route('enroll'));
            session()->forget('enrollment_intended_url'); // Clear the intended URL

            return Redirect::to($intendedUrl)->with('success', 'Google account linked successfully!');


        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
             Log::error('Google InvalidStateException: ' . $e->getMessage());
             return Redirect::route('enroll')->with('error', 'Login session expired. Please try signing in again.');
        } catch (\Exception $e) {
            Log::error('Google callback error: ' . $e->getMessage());
            // Generic error message
            return Redirect::route('enroll')->with('error', 'An error occurred during Google Sign-In. Please try again.');
        }
    }

    /**
     * Log the user out of the enrollment Google session.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $request->session()->forget([
            'enrollment_google_email',
            'enrollment_google_name',
            'enrollment_google_avatar',
        ]);

        Log::info('Cleared enrollment Google session.');

        return Redirect::route('enroll')->with('success', 'You have been signed out from the enrollment session.');
    }
}
