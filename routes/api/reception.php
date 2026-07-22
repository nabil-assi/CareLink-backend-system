<?php

use App\Http\Controllers\Api\Reception\ReceptionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('reception')->group(function () {
    Route::get('/patients', [ReceptionController::class, 'listPatients']);
    Route::post('/register-and-book', [ReceptionController::class, 'registerAndBook']);

    Route::get('/reception/doctor-schedule', [ReceptionController::class, 'getDoctorSchedule']);
    Route::post('/reception/appointments', [ReceptionController::class, 'storeAppointment']);

    // Route::post('/patients', [ReceptionController::class, 'registerPatient']);
    // Route::post('/patients', [ReceptionController::class, 'storePatient']);
    Route::put('/patients/{id}/meta', [ReceptionController::class, 'updatePatientMeta']);

    Route::post('/appointments', [ReceptionController::class, 'createAppointment']);
    Route::put('/appointments/{id}', [ReceptionController::class, 'updateAppointment']);
    Route::delete('/appointments/{id}', [ReceptionController::class, 'cancelAppointment']);

    Route::get('/doctors', [ReceptionController::class, 'listDoctors']);

});
