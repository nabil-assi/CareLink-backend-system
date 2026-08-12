<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\DoctorAuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\PatientAuthController;
use App\Http\Controllers\Api\StaffAuthController;

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/patient/register', [PatientAuthController::class, 'register']);
    Route::post('/patient/login', [PatientAuthController::class, 'login']);
    Route::post('/patient/forgot-password', [PatientAuthController::class, 'forgotPassword']);
    Route::post('/patient/reset-password', [PatientAuthController::class, 'resetPassword']);
    // زر "المتابعة بـ Google" بصفحتي تسجيل الدخول/الحساب الجديد للمريض
    Route::get('/google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('/google/callback', [GoogleAuthController::class, 'callback']);
    Route::post('/doctor/register', [DoctorAuthController::class, 'register']);
    Route::post('/doctor/login', [DoctorAuthController::class, 'login']);
    Route::post('/doctor/forgot-password', [DoctorAuthController::class, 'forgotPassword']);
    Route::post('/doctor/reset-password', [DoctorAuthController::class, 'resetPassword']);
    Route::post('/admin/login', [AdminAuthController::class, 'login']);

    Route::post('/staff/login', [StaffAuthController::class, 'login']);
});

// /admin/list و /admin/patients كانوا هون بدون أي حماية (auth:sanctum ولا
// checkRole) - أي حد بيعرف الرابط كان يقدر يشوف بيانات كل الأدمن وكل المرضى
// بدون تسجيل دخول. /admin/patients إلها نسخة محمية أصلاً بـ routes/api.php،
// فحذفناها من هون وخلينا /admin/list بس، محمية زي باقي مسارات الأدمن.
Route::middleware(['auth:sanctum', 'checkRole:admin'])->prefix('auth')->group(function () {
    Route::get('/admin/list', [AdminController::class, 'getAllAdmins']);
});

// تغيير كلمة المرور الإجبارية بعد كلمة مرور مؤقتة - الفرونت بيجرب المسار
// الأول وبعدين البديل كـ fallback، فربطنا الاثنين بنفس الميثود
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/staff/change-password', [StaffAuthController::class, 'changePassword']);
    Route::post('/auth/change-password', [StaffAuthController::class, 'changePassword']);
});
