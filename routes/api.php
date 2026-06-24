<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PatientAuthController;
use App\Http\Controllers\Api\DoctorAuthController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AdminController; 
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\PatientController;

// --- مسارات عامة (التسجيل والدخول) ---
Route::post('/patient/register', [PatientAuthController::class, 'register']);
Route::post('/patient/login', [PatientAuthController::class, 'login']);

Route::post('/doctor/register', [DoctorAuthController::class, 'register']);
Route::post('/doctor/login', [DoctorAuthController::class, 'login']);

Route::post('/admin/login', [AdminAuthController::class, 'login']);

// --- مسارات محمية للأدمن ---
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/pending-doctors', [AdminController::class, 'showPending']);
});

// --- مسارات محمية للطبيب ---
Route::middleware(['auth:sanctum', 'doctor'])->group(function () {
    Route::get('/doctor/profile', [DoctorController::class, 'profile']);
});

// --- مسارات محمية للمريض ---
Route::middleware(['auth:sanctum', 'patient'])->group(function () {
    Route::get('/patient/profile', [PatientController::class, 'profile']);
});