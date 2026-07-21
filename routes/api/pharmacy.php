<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'checkRole:patient'])->prefix('pharmacy')->group(function () {

    Route::get('/prescriptions', [PharmacyController::class, 'index']);
    Route::post('/prescriptions/{id}/ready', [PharmacyController::class, 'markReady']);
    Route::post('/prescriptions/{id}/dispense', [PharmacyController::class, 'dispense']);

    Route::get('/home-stats', [PharmacyController::class, 'homeStats']);

    //Route::get('/inventory', [PharmacyController::class, 'getInventory']);
    //Route::post('/inventory', [PharmacyController::class, 'storeInventory']);
    //Route::put('/inventory/{id}', [PharmacyController::class, 'updateInventory']);
    //Route::post('/inventory/{id}/adjust', [PharmacyController::class, 'adjustQuantity']);
});
