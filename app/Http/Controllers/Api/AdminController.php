<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Services\NotificationService;
class AdminController extends Controller
{

    
    // 1. عرض قائمة الأطباء الذين ينتظرون التفعيل (حالتهم inactive)
    public function showPending()
    {
        $pendingDoctors = Doctor::where('status', 'inactive')->get();

        if ($pendingDoctors->isEmpty()) {
            return response()->json(['message' => 'لا يوجد أطباء بانتظار التفعيل حالياً'], 200);
        }

        return response()->json([
            'message' => 'قائمة الأطباء المعلقين',
            'doctors' => $pendingDoctors,
        ], 200);
    }

    // 2. تفعيل حساب الطبيب
    public function approveDoctor($id)
    {
        $doctor = Doctor::find($id);

        if (! $doctor) {
            return response()->json(['message' => 'الطبيب غير موجود'], 404);
        }

        // تحديث الحالة إلى active
        $doctor->update(['status' => 'active']);
        NotificationService::send('doctor_approved', $doctor, ['name' => $doctor->name]);

        return response()->json([
            'message' => 'تم تفعيل حساب الطبيب '.$doctor->name.' بنجاح',
        ], 200);
    }

    // 3. رفض الطلب وحذف الطبيب
    public function rejectDoctor($id)
    {
        $doctor = Doctor::find($id);

        if (! $doctor) {
            return response()->json(['message' => 'الطبيب غير موجود'], 404);
        }

        Storage::delete($doctor->credential_document);

        $doctor->delete();

        return response()->json([
            'message' => 'تم رفض طلب الطبيب وحذف بياناته من النظام',
        ], 200);
    }
}
