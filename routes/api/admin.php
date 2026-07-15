<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\{AdController, NotificationController, PostController};
use App\Http\Controllers\Api\{AdminController, DoctorController, PatientController};

Route::middleware(['auth:sanctum', 'checkRole:admin'])->prefix('admin')->group(function () {
    Route::get('/list', [AdminController::class, 'getAllAdmins']);
    Route::get('/patients', [PatientController::class, 'getAllPatients']);
    
    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::get('/pending-doctors', [AdminController::class, 'showPending']);
    Route::patch('/approve-doctor/{id}', [AdminController::class, 'approveDoctor']);
    Route::delete('/reject-doctor/{id}', [AdminController::class, 'rejectDoctor']);

    Route::apiResource('ads', AdController::class);
    Route::apiResource('posts', PostController::class)->only(['index', 'store', 'destroy']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/general', [NotificationController::class, 'sendGeneral']);
    Route::post('/notifications/user', [NotificationController::class, 'sendToUser']);
    
    Route::post('/broadcast', [AdminController::class, 'sendBroadcast']);
    Route::get('/broadcasts', [AdminController::class, 'getAllBroadcasts']);
});