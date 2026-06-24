<?php
use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PatientAuthController;
use App\Http\Controllers\Api\DoctorAuthController;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// مسارات المريض
Route::post('/patient/register', [PatientAuthController::class, 'register']);

// مسارات الطبيب
Route::post('/doctor/register', [DoctorAuthController::class, 'register']);