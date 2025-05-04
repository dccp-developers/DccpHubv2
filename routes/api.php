<?php

use App\Http\Controllers\CourseController; // Added
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route to get course details
Route::get('/courses/{course}/details', [CourseController::class, 'getDetails'])
    ->name('api.courses.details'); // Added name for potential use
