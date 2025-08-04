<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\DoctorProfile\DoctorProfileController;
use App\Http\Controllers\Api\V1\PrescriptionController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Models\Prescription;
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

Route::get('ping', fn() => response()->json(['message' => 'API is live']));


Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    // For Admin
    Route::middleware('auth:api')->group(function () {

        Route::get('profile', [AuthController::class, 'profile']);
        Route::put('profile/update', [AuthController::class, 'profileUpdate']);
        // Route::get('specializations', [DoctorProfileController::class, 'specializations']);
        // Route::get('specializations/{id}', [DoctorProfileController::class, 'show']);

        // For Admin
        Route::middleware('role:Admin')->prefix('admin')->group(function () {});

        // For Doctor
        Route::middleware('role:Doctor')->prefix('doctor')->group(function () {
            Route::post('doctor-specializations', [DoctorProfileController::class, 'storeOrUpdateSpecialization']);
            Route::get('get-detail', [DoctorProfileController::class, 'showOwnProfile']);
            Route::get('appointments', [AppointmentController::class, 'index']);
            Route::get('todays-appointment', [AppointmentController::class, 'todaysAppointment']);
            Route::put('appointments/toggle-status/{id}', [AppointmentController::class, 'toggleAppointmentStatus']);
            Route::apiResource('prescriptions', PrescriptionController::class);
            Route::get('reviews/{id}', [ReviewController::class, 'show']);
            Route::get('reviews', [ReviewController::class, 'index']);
        });

        // For Patient
        Route::middleware('role:Patient')->prefix('patient')->group(function () {
            Route::get('doctor-specialists', [DoctorProfileController::class, 'index']);
            Route::get('doctor-specialists/{id}', [DoctorProfileController::class, 'getDoctor']);
            Route::apiResource('appointments', AppointmentController::class);
            Route::get('todays-appointment', [AppointmentController::class, 'todaysAppointment']);
            Route::get('prescriptions', [PrescriptionController::class, 'index']);
            Route::get('prescriptions/{id}', [PrescriptionController::class, 'show']);
            Route::post('reviews', [ReviewController::class, 'store']);
            Route::get('reviews', [ReviewController::class, 'index']);
        });

        // Logout
        Route::post('logout', [AuthController::class, 'logout']);
    });
});
