<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Broadcast;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;  

class AdminController extends Controller
{


public function store(Request $request)
    {
        // 1. تحقق صارم يتطابق مع الحقول المرسلة من React
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'specialty' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'gender' => 'required|in:male,female',
        ]);

        // استخدام Transaction لضمان عدم إنشاء مستخدم بدون بروفايل في حال حدوث خطأ
        return DB::transaction(function () use ($validated) {
            
            // 2. إنشاء المستخدم الأساسي
            $user = User::create([
                'name' => $validated['name'], // تأكد أن اسم العمود في الداتا بيز name وليس full_name
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'role' => 'doctor',
            ]);

            // 3. إنشاء البروفايل الخاص بالطبيب بحالة Active فوراً
            $user->doctorProfile()->create([
                'specialty' => $validated['specialty'] ?? 'غير محدد',
                'national_id' => $validated['national_id'] ?? null,
                'address' => $validated['address'] ?? null,
                'gender' => $validated['gender'],
                'status' => 'active', // مفعل تلقائياً لأن الأدمن من أضافه
                // 'credential_document' => null, // تركناها فارغة ليقوم الطبيب برفعها لاحقاً
            ]);

            // لا ترجع أي Token هنا، فقط رسالة نجاح
            return response()->json([
                'status' => true,
                'message' => 'تم إضافة الطبيب وتفعيله بنجاح',
            ], 201);
        });
    }


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

    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    // تحديث البيانات الشخصية
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email,' . $user->id,
            'phone'       => 'required|string|max:20',
            'national_id' => 'required|string|max:20',
            'role'        => 'required|string',
        ]);

        $user->update($validated);

        return response()->json(['message' => 'تم تحديث البيانات بنجاح']);
    }

    // تحديث كلمة المرور
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password'      => 'required|current_password',
            'password'              => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'تم تحديث كلمة المرور بنجاح']);
    }

    
}