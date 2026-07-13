<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Broadcast;
use App\Models\Doctor;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function getAllAdmins()
    {
         $admins = Admin::select('id', 'name', 'email', 'created_at')->get();

        return response()->json([
            'status' => true,
            'message' => 'تم جلب قائمة المسؤولين بنجاح',
            'data' => $admins,
        ], 200);
    }

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

    public function sendBroadcast(Request $request)
    {
        $request->validate(['message' => 'required', 'target' => 'required']);

         $broadcast = Broadcast::create($request->all());

 
        return response()->json(['message' => 'تم الإرسال والحفظ', 'data' => $broadcast]);
    }

    public function getAllBroadcasts()
    {
        return response()->json(Broadcast::latest()->get());
    }
}
