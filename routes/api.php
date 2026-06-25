<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\DoctorAuthController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\PatientAuthController;
use App\Http\Controllers\Api\PatientController;
use Illuminate\Support\Facades\Route;

// --- مسارات عامة (بدون حماية) ---
Route::post('/patient/register', [PatientAuthController::class, 'register']);
Route::post('/patient/login', [PatientAuthController::class, 'login']);

Route::post('/doctor/register', [DoctorAuthController::class, 'register']);
Route::post('/doctor/login', [DoctorAuthController::class, 'login']);

Route::post('/admin/login', [AdminAuthController::class, 'login']);

// --- مسارات محمية للأدمن ---
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::get('/pending-doctors', [AdminController::class, 'showPending']);
    Route::patch('/approve-doctor/{id}', [AdminController::class, 'approveDoctor']);
    Route::delete('/reject-doctor/{id}', [AdminController::class, 'rejectDoctor']);
});

// --- مسارات محمية للطبيب ---
Route::middleware(['auth:sanctum', 'doctor'])->group(function () {
    Route::get('/doctor/profile', [DoctorController::class, 'profile']);
    Route::post('/doctor/forgot-password', [DoctorAuthController::class, 'forgotPassword']);
    Route::post('/doctor/reset-password', [DoctorAuthController::class, 'resetPassword']);

});

// --- مسارات محمية للمريض ---
Route::middleware(['auth:sanctum', 'patient'])->group(function () {
    Route::get('/patient/profile', [PatientController::class, 'profile']);
    Route::post('/patient/forgot-password', [PatientAuthController::class, 'forgotPassword']);
    Route::post('/patient/reset-password', [PatientAuthController::class, 'resetPassword']);
});
