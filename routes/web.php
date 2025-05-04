<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log; // Added Log facade import
use Inertia\Inertia;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\TuitionController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SubjectsController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\User\OauthController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\User\LoginLinkController;
use App\Http\Controllers\PendingEnrollmentController;
use App\Http\Controllers\EnrollmentAuthController; // Added

Route::get('/', [WelcomeController::class, 'home'])->name('home');

// Online Enrollment Route
Route::get('/enroll', [PendingEnrollmentController::class, 'create'])->name('enroll'); // Use controller create method
Route::post('/pending-enrollment', [PendingEnrollmentController::class, 'store'])->name('pending-enrollment.store');

// Enrollment Google Auth Routes
Route::prefix('enrollment/auth')->group(function () {
    Route::get('/google/redirect', [EnrollmentAuthController::class, 'redirectToGoogle'])->name('enrollment.google.redirect');
    Route::get('/callback/google', [EnrollmentAuthController::class, 'handleGoogleCallback'])->name('enrollment.google.callback'); // Restored original
    Route::get('/google/logout', [EnrollmentAuthController::class, 'logout'])->name('enrollment.google.logout'); // Added logout route
});

// PWA Offline Route
Route::get('/offline', function () {
    return view('vendor.laravelpwa.offline');
})->name('offline');

Route::prefix('auth')->group(
    function () {
        // OAuth
        Route::get('/redirect/{provider}', [OauthController::class, 'redirect'])->name('oauth.redirect');
        Route::get('/callback/{provider}', [OauthController::class, 'callback'])->name('oauth.callback');
        // Magic Link
        Route::middleware('throttle:login-link')->group(function () {
            Route::post('/login-link', [LoginLinkController::class, 'store'])->name('login-link.store');
            Route::get('/login-link/{token}', [LoginLinkController::class, 'login'])
                ->name('login-link.login')
                ->middleware('signed');
        });
    }
);

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::delete('/auth/destroy/{provider}', [OauthController::class, 'destroy'])->name('oauth.destroy');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');

    Route::resource('/subscriptions', SubscriptionController::class)
        ->names('subscriptions')
        ->only(['index', 'create', 'store', 'show']);

    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::get('/tuition', [TuitionController::class, 'index'])->name('tuition.index');
    Route::get('/subjects', [SubjectsController::class, 'index'])->name('subjects.index');
    Route::get('/changelog', [ChangelogController::class, 'index'])->name('changelog.index');
});
