<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * عرض جميع الأطباء للوحة تحكم الأدمن
     */
    public function index()
    {
        // جلب المستخدمين الذين لديهم دور 'doctor'
        $doctors = User::where('role', 'doctor')
            ->with('doctorProfile') // جلب البيانات الطبية مع المستخدم
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $doctors,
        ], 200);
    }

    /**
     * عرض محادثات الطبيب
     */
    public function getMyConversations(Request $request)
    {
        // جلب المحادثات الخاصة بالطبيب الحالي
        $conversations = Conversation::where('doctor_id', $request->user()->id)
            ->with(['patient:id,name']) // علاقة الـ patient في موديل Conversation يجب أن تعود لـ User
            ->latest('updated_at')
            ->get();

        return response()->json(['data' => $conversations]);
    }

    public function getBroadcasts()
    {
        // جلب الرسائل الموجهة للجميع أو للأطباء فقط
        $broadcasts = Broadcast::whereIn('target', ['all', 'doctors'])
            ->latest()
            ->get();

        return response()->json(['data' => $broadcasts], 200);
    }
}