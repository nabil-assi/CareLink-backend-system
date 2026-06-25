<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\NotificationService;
class PatientAuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:patients',
            'national_id' => 'required|string|min:9|max:9|unique:patients',
            'password' => 'required|string|min:8',
            'phone' => 'required|string',
            'date_of_birth' => 'required|date',
            'address' => 'required|string',
            'gender' => 'required|in:male,female',
        ]);

        $patient = Patient::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'national_id' => $validated['national_id'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'],
            'date_of_birth' => $validated['date_of_birth'],
            'address' => $validated['address'],
            'gender' => $validated['gender'],
        ]);

        $token = $patient->createToken('patient_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل المريض بنجاح',
            'access_token' => $token,
            'patient' => $patient,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);

        $patient = Patient::where('email', $request->email)->first();

        if (! $patient || ! Hash::check($request->password, $patient->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }

        return response()->json([
            'access_token' => $patient->createToken('patient_token')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $patient->id,
                'name' => $patient->name,
                'email' => $patient->email,
                'role' => 'patient',
            ],
        ]);
    }

    public function forgotPassword(Request $request)
    {
        // التحقق من وجود الإيميل في جدول المرضى حصراً
        $request->validate(['email' => 'required|email|exists:patients,email']);

        $patient = Patient::where('email', $request->email)->first();
        
        // توليد الـ Token
        $token = Password::createToken($patient);
        
        // إرسال الإيميل
        NotificationService::send('password_reset', $patient, ['token' => $token]);

        return response()->json(['message' => 'تم إرسال رابط إعادة تعيين كلمة السر إلى إيميلك']);
    }

    public function resetPassword(Request $request)
{
    // 1. التحقق من البيانات
    $request->validate([
        'email' => 'required|email|exists:patients,email',
        'token' => 'required',
        'password' => 'required|confirmed|min:8',
    ]);

    // 2. تنفيذ إعادة التعيين باستخدام Password Broker
    $status = Password::broker()->reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->password = \Illuminate\Support\Facades\Hash::make($password);
            $user->save();
        }
    );

    // 3. التحقق من نتيجة العملية
    if ($status === Password::PASSWORD_RESET) {
        return response()->json(['message' => 'تم تغيير كلمة السر بنجاح'], 200);
    }

    return response()->json(['message' => 'فشل إعادة التعيين، الرمز غير صحيح أو منتهي الصلاحية'], 400);
}
}
