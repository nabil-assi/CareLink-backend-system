<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\Conversation;
use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * عرض جميع الأطباء للوحة تحكم الأدمن
     */
    public function index()
    {
        // جلب الأطباء مع الترتيب حسب الأحدث
        $doctors = Doctor::latest()->get();

        return response()->json([
            'status' => true,
            'data' => $doctors,
        ], 200);
    }

    // في ChatController (للطبيب)
    public function getMyConversations(Request $request)
    {
        $conversations = Conversation::where('doctor_id', $request->user()->id)
            ->with(['patient:id,name']) // جلب اسم المريض
            ->latest('updated_at') // الترتيب حسب آخر نشاط
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
