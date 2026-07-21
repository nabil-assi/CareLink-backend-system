<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/temp-test', function () {
//     return [
//         'temp' => sys_get_temp_dir(),
//         'exists' => file_exists(sys_get_temp_dir()),
//         'is_dir' => is_dir(sys_get_temp_dir()),
//         'writable' => is_writable(sys_get_temp_dir()),
//     ];
// });
