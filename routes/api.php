<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiUserController;
use App\Http\Controllers\ApiGuestController;
use App\Http\Controllers\CourseController; // Added
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\WebPushController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('user', ApiUserController::class);
Route::post('check-id', [ApiGuestController::class, 'checkId'])->name('api.checkId');
Route::post('check-email', [ApiGuestController::class, 'checkEmail'])->name('api.checkEmail');
// Route to get course details
Route::get('/courses/{course}/details', [CourseController::class, 'getDetails'])
    ->name('api.courses.details'); // Added name for potential use

// Notification routes (protected by web auth)
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('api.notifications.index');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('api.notifications.unread-count');
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead'])->name('api.notifications.mark-read');
        Route::patch('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('api.notifications.mark-all-read');
        Route::delete('/clear-all', [NotificationController::class, 'clearAll'])->name('api.notifications.clear-all');
        Route::post('/test', [NotificationController::class, 'sendTest'])->name('api.notifications.test');
    });

    // Web Push routes
    Route::prefix('webpush')->group(function () {
        Route::post('/subscribe', [WebPushController::class, 'subscribe'])->name('api.webpush.subscribe');
        Route::post('/unsubscribe', [WebPushController::class, 'unsubscribe'])->name('api.webpush.unsubscribe');
        Route::get('/status', [WebPushController::class, 'status'])->name('api.webpush.status');
    });
});
