<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{AdminAuthController, DoctorAuthController, PatientAuthController};

Route::prefix('auth')->group(function () {
    Route::post('/patient/register', [PatientAuthController::class, 'register']);
    Route::post('/patient/login', [PatientAuthController::class, 'login']);
    Route::post('/doctor/register', [DoctorAuthController::class, 'register']);
    Route::post('/doctor/login', [DoctorAuthController::class, 'login']);
    Route::post('/admin/login', [AdminAuthController::class, 'login']);
});