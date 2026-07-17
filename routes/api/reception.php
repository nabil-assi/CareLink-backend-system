<?php

use App\Http\Controllers\Api\Reception\ReceptionController;
use Illuminate\Support\Facades\Route;

//Route::middleware(['auth:sanctum', 'role:receptionist'])->group(function () {
    // مسار تسجيل مريض جديد
    Route::post('/reception/patients', [ReceptionController::class, 'registerPatient']);
    // مسار حجز موعد للمريض
    Route::post('/reception/appointments', [ReceptionController::class, 'createAppointment']);

//});
