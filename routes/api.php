<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\DoctorProfile\DoctorProfileController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    // For Admin
    Route::middleware('auth:api')->group(function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::put('profile/update', [AuthController::class, 'profileUpdate']);
        Route::get('specializations', [DoctorProfileController::class, 'specializations']);
        Route::get('specializations/{id}', [DoctorProfileController::class, 'show']);
        Route::get('doctors', [DoctorProfileController::class, 'index']);
        Route::get('doctors/{id}', [DoctorProfileController::class, 'getDoctor']);
        // For Admin
        Route::middleware('role:Admin')->prefix('admin')->group(function () {});
        // For Doctor
        Route::middleware('role:Doctor')->prefix('doctor')->group(function () {
            Route::post('specializations', [DoctorProfileController::class, 'storeOrUpdateSpecialization']);
            Route::get('get-detail', [DoctorProfileController::class, 'showOwnProfile']);
        });

        // Logout
        Route::post('logout', [AuthController::class, 'logout']);
    });
});
