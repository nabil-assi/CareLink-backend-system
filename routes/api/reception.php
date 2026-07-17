<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Reception\ReceptionController;


Route::post('/reception/patients', [ReceptionController::class, 'registerPatient']);
