<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\DoctorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'checkRole:doctor'])->prefix('doctor')->group(function () {
    Route::get('/profile', [DoctorController::class, 'getProfile']);
    Route::put('/profile', [DoctorController::class, 'updateProfile']);

     Route::post('/profile/change-password', [DoctorController::class, 'changePassword']);

    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/{id}', [AppointmentController::class, 'show']);

    Route::patch('/appointments/{id}/cancel', [AppointmentController::class, 'cancel']);
    Route::post('/appointments/{appointment}/medical-records', [AppointmentController::class, 'storeMedicalRecord']);
    // Route::get('/appointments/{appointment}/medical-records', [AppointmentController::class, 'getMedicalRecord']);

    Route::post('/appointments/{id}/diagnosis', [AppointmentController::class, 'saveDiagnosis']);
    Route::post('/appointments/{id}/lab-orders', [AppointmentController::class, 'storeLabOrder']);
    Route::post('/appointments/{id}/prescriptions', [AppointmentController::class, 'storePrescription']);
    Route::post('/appointments/{id}/complete', [AppointmentController::class, 'completeAppointment']);

    Route::get('/medical-records', [AppointmentController::class, 'getAllMedicalRecords']);
    // حفظ السجل الطبي مرتبطاً بالموعد
    Route::get('/patients', [AppointmentController::class, 'doctorPatients']);
    Route::get('/patients/{id}', [AppointmentController::class, 'doctorPatientDetail']);

    Route::get('/broadcasts', [DoctorController::class, 'getBroadcasts']);
    Route::post('/appointments/{appointment}/prescriptions', [AppointmentController::class, 'storePrescription']);
});
