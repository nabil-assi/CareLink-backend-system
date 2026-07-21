<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{PatientController, AppointmentController};

Route::middleware(['auth:sanctum', 'checkRole:patient'])->prefix('patient')->group(function () {
    Route::get('/profile', [PatientController::class, 'profile']);
    Route::patch('/profile', [PatientController::class, 'updateProfile']);
    Route::patch('/account', [PatientController::class, 'updateAccount']);
    Route::get('/medical-profile', [PatientController::class, 'getMedicalProfile']);


    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::patch('/appointments/{id}/cancel', [AppointmentController::class, 'cancel']);
    Route::patch('/appointments/{id}/reschedule', [AppointmentController::class, 'reschedule']);

    Route::get('/medical-records', [PatientController::class, 'myMedicalRecords']);
    Route::get('/broadcasts', [PatientController::class, 'getBroadcasts']);

    Route::get('/doctors', [PatientController::class, 'doctors']);

    Route::post('/profile-picture', [PatientController::class, 'updateProfilePicture']);
});
