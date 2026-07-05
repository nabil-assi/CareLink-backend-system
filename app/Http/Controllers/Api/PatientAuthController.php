<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

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

        // 1. إنشاء المريض
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

        $patient->profile()->create([]);

        $token = $patient->createToken('patient_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل المريض بنجاح',
            'access_token' => $token,
            'patient' => $patient->load('profile'),
        ], 201);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'blood_type' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'weight_kg' => 'nullable|numeric|min:0',
            'height_cm' => 'nullable|numeric|min:0',
            'is_diabetic' => 'nullable|boolean',
            'is_hypertensive' => 'nullable|boolean',
            'is_smoker' => 'nullable|boolean',
            'allergies' => 'nullable|string',
            'chronic_diseases' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
        ]);

        auth()->user()->profile()->update($validated);

        return response()->json([
            'message' => 'تم تحديث الملف الطبي بنجاح',
            'profile' => auth()->user()->profile,
        ]);
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
        $request->validate(['email' => 'required|email|exists:patients,email']);

        $patient = Patient::where('email', $request->email)->first();

        // توليد كود عشوائي من 5 أرقام
        $otp = random_int(10000, 99999);

        // تخزين الكود في الكاش لمدة 10 دقائق
        Cache::put('otp_patient_'.$patient->email, $otp, now()->addMinutes(10));

        // إرسال الكود عبر الإيميل
        NotificationService::send('password_reset', $patient, ['otp' => $otp]);

        return response()->json(['message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:patients,email',
            'token' => 'required|numeric', // الكود الذي أرسله المستخدم
            'password' => 'required|confirmed|min:8',
        ]);

        $email = $request->email;
        $otp = $request->token;

        // 1. استرجاع الكود من الكاش
        $storedOtp = Cache::get('otp_patient_'.$email);

       
        // 2. التحقق من صحة الكود
        // استبدل سطر التحقق بهذا:
        if (! $storedOtp || (string) $storedOtp !== (string) $otp) {
            // استخدم dd لرؤية القيم إذا استمرت المشكلة:
 
            return response()->json(['message' => 'الكود غير صحيح أو انتهت صلاحيته'], 400);
        }

        // 3. تحديث كلمة السر للمريض
        $patient = Patient::where('email', $email)->first();
        $patient->password = Hash::make($request->password);
        $patient->save();

        // 4. حذف الكود من الكاش بعد الاستخدام
        Cache::forget('otp_patient_'.$email);

        return response()->json(['message' => 'تم تغيير كلمة السر بنجاح'], 200);
    }
}
