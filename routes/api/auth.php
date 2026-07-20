<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\DoctorAuthController;
use App\Http\Controllers\Api\PatientAuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\StaffAuthController;

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/patient/register', [PatientAuthController::class, 'register']);
    Route::post('/patient/login', [PatientAuthController::class, 'login']);
    Route::post('/patient/forgot-password', [PatientAuthController::class, 'forgotPassword']);
    Route::post('/patient/reset-password', [PatientAuthController::class, 'resetPassword']);

 
    Route::post('/doctor/register', [DoctorAuthController::class, 'register']);
    Route::post('/doctor/login', [DoctorAuthController::class, 'login']);
    Route::post('/doctor/forgot-password', [DoctorAuthController::class, 'forgotPassword']);
    Route::post('/doctor/reset-password', [DoctorAuthController::class, 'resetPassword']);

    Route::post('/admin/login', [AdminAuthController::class, 'login']);
    Route::get('/admin/list', [AdminController::class, 'getAllAdmins']);
    Route::get('/admin/patients', [PatientController::class, 'getAllPatients']);

    Route::post('/staff/login', [StaffAuthController::class, 'login']);

});
