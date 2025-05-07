<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiUserController;
use App\Http\Controllers\ApiGuestController;
use App\Http\Controllers\CourseController; // Added

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
