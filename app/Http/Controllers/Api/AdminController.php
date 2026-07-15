<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Broadcast;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function getAllAdmins()
    {
        // جلب جميع المستخدمين ذوي الدور 'admin'
        $admins = User::where('role', 'admin')
            ->select('id', 'name', 'email', 'created_at')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'تم جلب قائمة المسؤولين بنجاح',
            'data' => $admins,
        ], 200);
    }

    // 1. عرض قائمة الأطباء الذين ينتظرون التفعيل (حالة الـ profile الخاصة بهم inactive)
    public function showPending()
    {
        $pendingDoctors = User::where('role', 'doctor')
            ->whereHas('doctorProfile', function ($query) {
                $query->where('status', 'inactive');
            })
            ->with('doctorProfile')
            ->get();

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
        $doctor = User::where('role', 'doctor')->findOrFail($id);

        // تحديث حالة الطبيب في الـ Profile
        $doctor->doctorProfile()->update(['status' => 'active']);
        
        NotificationService::send('doctor_approved', $doctor, ['name' => $doctor->name]);

        return response()->json([
            'message' => 'تم تفعيل حساب الطبيب ' . $doctor->name . ' بنجاح',
        ], 200);
    }

    // 3. رفض الطلب وحذف الطبيب
    public function rejectDoctor($id)
    {
        $doctor = User::where('role', 'doctor')->findOrFail($id);

        // حذف ملفات الشهادات من الـ profile إذا كانت موجودة
        if ($doctor->doctorProfile && $doctor->doctorProfile->credential_document) {
            Storage::delete($doctor->doctorProfile->credential_document);
        }

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