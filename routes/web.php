<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

// ============================================================================
// CONTROLLER IMPORTS
// ============================================================================

// Core Controllers
use App\Http\Controllers\ChatController;
use App\Http\Controllers\TuitionController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SubjectsController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PendingEnrollmentController;
use App\Http\Controllers\EnrollmentAuthController;
use App\Http\Controllers\GuestDashboardController;

// Authentication Controllers
use App\Http\Controllers\User\OauthController;
use App\Http\Controllers\User\LoginLinkController;
use App\Http\Controllers\SocialAuthController;

// Student Controllers
use App\Http\Controllers\Student\EnrollmentController;
use App\Http\Controllers\Student\StudentSettingsController;
use App\Http\Controllers\Student\StudentAttendanceController;

// Faculty Controllers
use App\Http\Controllers\Faculty\FacultyDashboardController;
use App\Http\Controllers\Faculty\FacultyClassController;
use App\Http\Controllers\Faculty\FacultyStudentController;
use App\Http\Controllers\Faculty\FacultySettingsController;
use App\Http\Controllers\Faculty\FacultyScheduleController;
use App\Http\Controllers\Faculty\FacultyAttendanceController;

// Mobile & APK Controllers
use App\Http\Controllers\APKController;
use App\Http\Controllers\GitHubReleaseController;

// Middleware
use App\Http\Middleware\DetectMobileApp;

// ============================================================================
// PUBLIC ROUTES
// ============================================================================

// Root route - redirect to login for mobile app, or welcome for web
Route::get('/', function () {
    // Check if this is a mobile app request using our middleware
    $isMobileApp = DetectMobileApp::isMobileApp(request());

    // If user is already authenticated, go to dashboard
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    // If mobile app, go directly to login
    if ($isMobileApp) {
        return redirect()->route('login');
    }

    // Otherwise, show welcome page for web browsers
    return app(WelcomeController::class)->home();
})->name('home');

// Welcome/Landing page route (for web users who want to see it)
Route::get('/welcome', [WelcomeController::class, 'home'])->name('welcome');

// ============================================================================
// MOBILE APP & APK ROUTES
// ============================================================================

// APK Generation and Download Routes
Route::prefix('apk')->group(function () {
    Route::get('/', [APKController::class, 'downloadPage'])->name('apk.page');
    Route::post('/generate', [APKController::class, 'generateAPK'])->name('apk.generate');
    Route::get('/download/{filename}', [APKController::class, 'downloadAPK'])->name('apk.download');
    Route::get('/status', [APKController::class, 'getAPKStatus'])->name('apk.status');

    // GitHub Release Routes (optimized downloads)
    Route::get('/releases', [GitHubReleaseController::class, 'getAllReleases'])->name('apk.releases');
    Route::get('/releases/latest', [GitHubReleaseController::class, 'getLatestRelease'])->name('apk.releases.latest');
    Route::get('/releases/latest/download', [GitHubReleaseController::class, 'downloadLatestAPK'])->name('apk.releases.download.latest');
    Route::get('/releases/{version}/download', [GitHubReleaseController::class, 'downloadReleaseAPK'])->name('apk.releases.download.version');
    Route::post('/releases/cache/clear', [GitHubReleaseController::class, 'clearCache'])->name('apk.releases.cache.clear');
});

// Direct APK download routes (with GitHub release optimization)
Route::get('/storage/apk/DCCPHub_latest.apk', [GitHubReleaseController::class, 'downloadLatestAPK'])->name('apk.latest');
Route::get('/download/apk', [GitHubReleaseController::class, 'downloadLatestAPK'])->name('apk.download.latest');
Route::get('/download/apk/{version}', [GitHubReleaseController::class, 'downloadReleaseAPK'])->name('apk.download.version');

// ============================================================================
// AUTHENTICATION ROUTES
// ============================================================================

// Mobile OAuth routes for Capacitor Social Login
Route::prefix('auth')->group(function () {
    Route::post('/google/callback/mobile', [SocialAuthController::class, 'handleMobileCallback'])->name('auth.mobile.callback');
    Route::post('/google/exchange', [SocialAuthController::class, 'exchangeCodeForTokens'])->name('auth.mobile.exchange');
    Route::get('/mobile/test', function() {
        return response()->json(['status' => 'Mobile OAuth routes working', 'timestamp' => now()]);
    })->name('auth.mobile.test');
});

// OAuth and Magic Link Authentication
Route::prefix('auth')->group(function () {
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
});

// ============================================================================
// ENROLLMENT ROUTES
// ============================================================================

// Online Enrollment Route
Route::get('/enroll', [PendingEnrollmentController::class, 'create'])->name('enroll');
Route::post('/pending-enrollment', [PendingEnrollmentController::class, 'store'])->name('pending-enrollment.store');
Route::post('/enroll/confirm', [PendingEnrollmentController::class, 'confirm'])->name('enroll.confirm');

// Enrollment Google Auth Routes
Route::prefix('enrollment/auth')->group(function () {
    Route::get('/google/redirect', [EnrollmentAuthController::class, 'redirectToGoogle'])->name('enrollment.google.redirect');
    Route::get('/callback/google', [EnrollmentAuthController::class, 'handleGoogleCallback'])->name('enrollment.google.callback');
    Route::get('/google/logout', [EnrollmentAuthController::class, 'logout'])->name('enrollment.google.logout');
});

// ============================================================================
// TESTING & UTILITY ROUTES
// ============================================================================

// Test Notifications
Route::get('/test-toast-success', function() {
    return redirect()->route('dashboard')->with('success', 'Success notification test');
})->middleware(['auth:sanctum', config('jetstream.auth_session')])->name('test.toast.success');

Route::get('/test-toast-error', function() {
    return redirect()->route('dashboard')->with('error', 'Error notification test');
})->middleware(['auth:sanctum', config('jetstream.auth_session')])->name('test.toast.error');

Route::get('/test-toast-message', function() {
    return redirect()->route('dashboard')->with('message', 'Info notification test');
})->middleware(['auth:sanctum', config('jetstream.auth_session')])->name('test.toast.message');

// Online Enrollment Route
Route::get('/enroll', [PendingEnrollmentController::class, 'create'])->name('enroll'); // Use controller create method
Route::post('/pending-enrollment', [PendingEnrollmentController::class, 'store'])->name('pending-enrollment.store');
Route::post('/enroll/confirm', [PendingEnrollmentController::class, 'confirm'])->name('enroll.confirm');

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

// Payment Process Route
Route::get('/payment-process', function () {
    return Inertia::render('PaymentProcess');
})->name('payment.process');

// ============================================================================
// AUTHENTICATED ROUTES
// ============================================================================

Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

    // ========================================================================
    // DASHBOARD ROUTES
    // ========================================================================
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/faculty/dashboard', FacultyDashboardController::class)->name('faculty.dashboard');
    Route::get('/enrolee', GuestDashboardController::class)->name('enrolee.dashboard');

    // ========================================================================
    // AUTHENTICATION MANAGEMENT
    // ========================================================================
    Route::delete('/auth/destroy/{provider}', [OauthController::class, 'destroy'])->name('oauth.destroy');

    // ========================================================================
    // FACULTY ROUTES
    // ========================================================================
    Route::prefix('faculty')->name('faculty.')->group(function () {

        // Faculty Classes Management
        Route::get('/classes', [FacultyClassController::class, 'index'])->name('classes.index');
        Route::get('/classes/{class}', [FacultyClassController::class, 'show'])->name('classes.show');
        Route::get('/classes/{class}/students', [FacultyClassController::class, 'students'])->name('classes.students');
        Route::put('/classes/{class}', [FacultyClassController::class, 'update'])->name('classes.update');

        // Faculty Class Attendance Management
        Route::post('/classes/{class}/attendance/setup', [FacultyClassController::class, 'setupAttendance'])->name('classes.attendance.setup');
        Route::get('/classes/{class}/attendance/data', [FacultyClassController::class, 'getAttendanceData'])->name('classes.attendance.data');
        Route::post('/classes/{class}/attendance/initialize', [FacultyClassController::class, 'initializeAttendance'])->name('classes.attendance.initialize');
        Route::post('/classes/{class}/attendance/update', [FacultyClassController::class, 'updateAttendance'])->name('classes.attendance.update');
        Route::post('/classes/{class}/attendance/bulk-update', [FacultyClassController::class, 'bulkUpdateAttendance'])->name('classes.attendance.bulk-update');
        Route::delete('/classes/{class}/attendance/reset', [FacultyClassController::class, 'resetAttendance'])->name('classes.attendance.reset');

        // Faculty Class Grades Management
        Route::post('/classes/{class}/grades/update', [FacultyClassController::class, 'updateGrades'])->name('classes.grades.update');
        Route::post('/classes/{class}/grades/bulk-update', [FacultyClassController::class, 'bulkUpdateGrades'])->name('classes.grades.bulk-update');
        Route::post('/classes/{class}/grades/import', [FacultyClassController::class, 'importGrades'])->name('classes.grades.import');
        Route::get('/classes/{class}/grades/export', [FacultyClassController::class, 'exportGrades'])->name('classes.grades.export');

        // Faculty Students Management
        Route::get('/students', [FacultyStudentController::class, 'index'])->name('students.index');
        Route::get('/students/{student}', [FacultyStudentController::class, 'show'])->name('students.show');

        // Faculty Schedule Management
        Route::get('/schedule', [FacultyScheduleController::class, 'index'])->name('schedule.index');
        Route::get('/schedule/{schedule}', [FacultyScheduleController::class, 'show'])->name('schedule.show');
        Route::post('/schedule/export', [FacultyScheduleController::class, 'export'])->name('schedule.export');

        // Faculty Attendance Management
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/', [FacultyAttendanceController::class, 'index'])->name('index');
            Route::get('/class/{class}', [FacultyAttendanceController::class, 'showClass'])->name('class.show');
            Route::post('/class/{class}/mark', [FacultyAttendanceController::class, 'markAttendance'])->name('class.mark');
            Route::patch('/attendance/{attendance}', [FacultyAttendanceController::class, 'updateAttendance'])->name('update');
            Route::get('/class/{class}/export', [FacultyAttendanceController::class, 'exportAttendance'])->name('class.export');
            Route::get('/reports', [FacultyAttendanceController::class, 'reports'])->name('reports');
            Route::get('/analytics', [FacultyAttendanceController::class, 'analytics'])->name('analytics');
        });
    });

    // Faculty Settings Routes
    Route::prefix('faculty/settings')->name('faculty.settings.')->group(function () {
        Route::get('/', [FacultySettingsController::class, 'index'])->name('index');
        Route::patch('/semester', [FacultySettingsController::class, 'updateSemester'])->name('semester');
        Route::patch('/school-year', [FacultySettingsController::class, 'updateSchoolYear'])->name('school-year');
        Route::patch('/both', [FacultySettingsController::class, 'updateBoth'])->name('both');
    });

    // ========================================================================
    // STUDENT ROUTES
    // ========================================================================
    Route::prefix('student')->name('student.')->group(function () {

        // Student Enrollment
        Route::get('/enroll/subjects', [EnrollmentController::class, 'showEnrollmentForm'])->name('enroll.subjects');
        Route::post('/enroll/subjects', [EnrollmentController::class, 'processEnrollment'])->name('enroll.subjects.submit');

        // Student Attendance
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/', [StudentAttendanceController::class, 'index'])->name('index');
            Route::get('/class/{class}', [StudentAttendanceController::class, 'showClass'])->name('class.show');
            Route::get('/statistics', [StudentAttendanceController::class, 'statistics'])->name('statistics');
            Route::get('/history', [StudentAttendanceController::class, 'history'])->name('history');
            Route::get('/export', [StudentAttendanceController::class, 'export'])->name('export');
            Route::get('/data', [StudentAttendanceController::class, 'getAttendanceData'])->name('data');
        });
    });

    // Student Settings Routes
    Route::prefix('student/settings')->name('student.settings.')->group(function () {
        Route::get('/', [StudentSettingsController::class, 'index'])->name('index');
        Route::patch('/semester', [StudentSettingsController::class, 'updateSemester'])->name('semester');
        Route::patch('/school-year', [StudentSettingsController::class, 'updateSchoolYear'])->name('school-year');
        Route::patch('/both', [StudentSettingsController::class, 'updateBoth'])->name('both');
    });

    // ========================================================================
    // SHARED ACADEMIC ROUTES
    // ========================================================================
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::get('/tuition', [TuitionController::class, 'index'])->name('tuition.index');
    Route::get('/subjects', [SubjectsController::class, 'index'])->name('subjects.index');
    Route::get('/changelog', [ChangelogController::class, 'index'])->name('changelog.index');
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');

    // ========================================================================
    // SUBSCRIPTION ROUTES
    // ========================================================================
    Route::resource('/subscriptions', SubscriptionController::class)
        ->names('subscriptions')
        ->only(['index', 'create', 'store', 'show']);
});


