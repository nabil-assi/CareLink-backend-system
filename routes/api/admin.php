<?php

use App\Http\Controllers\Api\Admin\AdController;
use App\Http\Controllers\Api\Admin\ArticleController;
use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Admin\PostController;
use App\Http\Controllers\Api\Admin\StaffController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\DoctorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'checkRole:admin'])->prefix('admin')->group(function () {

    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::post('/doctors', [AdminController::class, 'store']);

    Route::get('/pending-doctors', [AdminController::class, 'showPending']);
    Route::patch('/approve-doctor/{id}', [AdminController::class, 'approveDoctor']);
    Route::delete('/reject-doctor/{id}', [AdminController::class, 'rejectDoctor']);

    // Route::apiResource('ads', AdController::class);
    // Route::apiResource('posts', PostController::class)->only(['index', 'store', 'destroy']);

    Route::get('/ads', [AdController::class, 'index']);
    Route::post('/ads', [AdController::class, 'store']);
    Route::put('/ads/{id}', [AdController::class, 'update']);
    Route::delete('/ads/{id}', [AdController::class, 'destroy']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/general', [NotificationController::class, 'sendGeneral']);
    Route::post('/notifications/user', [NotificationController::class, 'sendToUser']);

    Route::patch('/posts/{id}/approve', [PostController::class, 'approve']);

    Route::post('/broadcast', [AdminController::class, 'sendBroadcast']);
    Route::get('/broadcasts', [AdminController::class, 'getAllBroadcasts']);

    Route::get('/articles', [ArticleController::class, 'index']);
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::put('/articles/{id}', [ArticleController::class, 'update']);
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);

    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'update']);

    Route::get('/', [AdminController::class, 'show']);
    Route::put('/profile', [AdminController::class, 'updateProfile']);
    Route::put('/password', [AdminController::class, 'updatePassword']);

    Route::get('/staff', [StaffController::class, 'index']);
    Route::post('/staff', [StaffController::class, 'store']);
    Route::put('/staff/{id}', [StaffController::class, 'update']);
    Route::patch('/staff/{id}/status', [StaffController::class, 'updateStatus']);
    Route::delete('/staff/{id}', [StaffController::class, 'destroy']);

});
