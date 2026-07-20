<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;  
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

    /**
     * عرض البروفايل الكامل للطبيب (مع تفاصيل التخصص)
     */
    public function getProfile(Request $request)
    {
        $doctor = $request->user(); // الطبيب المسجل حالياً

        // تحميل البروفايل المرتبط به
        $doctor->load('doctorProfile');

        return response()->json([
            'status' => true,
            'data' => $doctor,
        ], 200);
    }

    /**
     * تحديث البروفايل الخاص بالطبيب
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // 1. التحقق من البيانات (إضافة جميع الحقول المطلوبة)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'gender' => 'nullable|in:male,female',
            'specialty' => 'nullable|string',
            'national_id' => 'nullable|string',
        ]);

        // 2. تحديث جدول المستخدم (users)
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone,
        ]);

        // 3. تحديث جدول بروفايل الطبيب (doctor_profiles)
        // نستخدم null coalescing (??) لضمان عدم إرسال null للقاعدة إذا لم تكن القيمة موجودة
        $user->doctorProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'address' => $validated['address'] ?? null,
                'gender' => $validated['gender'] ?? 'male',
                'specialty' => $validated['specialty'] ?? 'غير محدد', // حل جذري لمشكلة الـ Null
                'national_id' => $validated['national_id'] ?? null,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث البيانات بنجاح',
        ], 200);
    }

public function changePassword(Request $request)
{
    $request->validate([
        'current_password' => 'required',
        'new_password' => 'required|min:6',
    ]);

    $user = $request->user();

    if (!Hash::check($request->current_password, $user->password)) {
        throw ValidationException::withMessages([
            'current_password' => ['كلمة المرور الحالية غير صحيحة.'],
        ]);
    }

    $user->update([
        'password' => Hash::make($request->new_password),
    ]);

    return response()->json([
        'message' => 'تم تحديث كلمة المرور بنجاح',
    ], 200);
}

    }
