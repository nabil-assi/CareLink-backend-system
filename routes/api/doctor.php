<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{DoctorController, AppointmentController};

Route::middleware(['auth:sanctum', 'checkRole:doctor'])->prefix('doctor')->group(function () {
    Route::get('/profile', [DoctorController::class, 'profile']);
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::patch('/appointments/{id}/cancel', [AppointmentController::class, 'cancel']);
    Route::post('/appointments/{appointment}/medical-records', [AppointmentController::class, 'storeMedicalRecord']);
    Route::get('/appointments/{appointment}/medical-records', [AppointmentController::class, 'getMedicalRecord']);
    Route::get('/broadcasts', [DoctorController::class, 'getBroadcasts']);
});