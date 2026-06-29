<?php

use App\Http\Controllers\Api\Admin\AdController;
use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Admin\PostController;
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
Route::get('/admin/list', [AdminController::class, 'getAllAdmins']);

// --- مسارات محمية للأدمن ---
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {

    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::get('/pending-doctors', [AdminController::class, 'showPending']);
    Route::patch('/approve-doctor/{id}', [AdminController::class, 'approveDoctor']);
    Route::delete('/reject-doctor/{id}', [AdminController::class, 'rejectDoctor']);

    Route::get('/ads', [AdController::class, 'index']);
    Route::post('/ads', [AdController::class, 'store']);
    Route::post('/ads/{id}', [AdController::class, 'update']);
    Route::delete('/ads/{id}', [AdController::class, 'destroy']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/general', [NotificationController::class, 'sendGeneral']);
    Route::post('/notifications/user', [NotificationController::class, 'sendToUser']);

    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);
});

// --- مسارات محمية للطبيب ---
Route::prefix('doctor')->middleware(['auth:sanctum', 'doctor'])->group(function () {
    Route::get('/profile', [DoctorController::class, 'profile']);
    Route::post('/forgot-password', [DoctorAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [DoctorAuthController::class, 'resetPassword']);

});

// --- مسارات محمية للمريض ---
Route::prefix('patient')->middleware(['auth:sanctum', 'patient'])->group(function () {
    Route::get('/profile', [PatientController::class, 'profile']);
    Route::patch('/profile', [PatientController::class, 'updateProfile']);
    Route::get('/medical-profile', [PatientController::class, 'getMedicalProfile']);

    Route::post('/forgot-password', [PatientAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [PatientAuthController::class, 'resetPassword']);

});
