<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DoctorProfile;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DoctorAuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string',
            'specialty' => 'required|string',
            'years_of_experience' => 'required|integer',
            'credential_document' => 'required|file|mimes:pdf,jpg,png|max:2048',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $path = $request->file('credential_document')->store('documents', 'public');

            // 1. إنشاء المستخدم بدور 'doctor'
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'],
                'role' => 'doctor',
            ]);

            // 2. إنشاء البروفايل الخاص بالطبيب بحالة inactive
            $user->doctorProfile()->create([
                'specialty' => $validated['specialty'],
                'years_of_experience' => $validated['years_of_experience'],
                'credential_document' => $path,
                'status' => 'inactive',
            ]);

            $token = $user->createToken('doctor_token')->plainTextToken;

            return response()->json([
                'message' => 'تم تسجيل الطبيب بنجاح, بانتظار التفعيل',
                'access_token' => $token,
                'user' => $user->load('doctorProfile'),
            ], 201);
        });
    }

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);

        $user = User::where('email', $request->email)->where('role', 'doctor')->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }

        // التحقق من حالة التفعيل داخل البروفايل
        if ($user->doctorProfile->status !== 'active') {
            return response()->json(['message' => 'حسابك بانتظار موافقة الإدارة'], 403);
        }

        return response()->json([
            'access_token' => $user->createToken('doctor_token')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->where('role', 'doctor')->first();

        $otp = random_int(10000, 99999);
        Cache::put('otp_doctor_'.$user->email, $otp, now()->addMinutes(10));

        NotificationService::send('password_reset', $user, ['otp' => $otp]);

        return response()->json(['message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|numeric',
            'password' => 'required|confirmed|min:8',
        ]);

        $storedOtp = Cache::get('otp_doctor_'.$request->email);

        if (! $storedOtp || (int) $storedOtp !== (int) $request->token) {
            return response()->json(['message' => 'الكود غير صحيح أو انتهت صلاحيته'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        Cache::forget('otp_doctor_'.$request->email);

        return response()->json(['message' => 'تم تغيير كلمة السر بنجاح'], 200);
    }
}