<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\PatientController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'checkRole:patient'])->prefix('patient')->group(function () {
    Route::get('/profile', [PatientController::class, 'profile']);
    Route::patch('/profile', [PatientController::class, 'updateProfile']);
    Route::patch('/account', [PatientController::class, 'updateAccount']);
    Route::get('/medical-profile', [PatientController::class, 'getMedicalProfile']);

    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::delete('/appointments/{id}', [AppointmentController::class, 'cancel']);
    Route::patch('/appointments/{id}/reschedule', [AppointmentController::class, 'reschedule']);
    Route::post('/appointments/{id}/rate', [PatientController::class, 'storeRating']);

    Route::get('/medical-records', [PatientController::class, 'myMedicalRecords']);
    Route::get('/broadcasts', [PatientController::class, 'getBroadcasts']);

    Route::get('/doctors', [PatientController::class, 'doctors']);

    Route::post('/profile-picture', [PatientController::class, 'updateProfilePicture']);

});
