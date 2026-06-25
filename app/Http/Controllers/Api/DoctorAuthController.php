<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Services\NotificationService;
class DoctorAuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:doctors',
            'national_id' => 'required|string|min:9|max:9|unique:doctors',
            'password' => 'required|string|min:8',
            'phone' => 'required|string',
            'date_of_birth' => 'required|date',
            'address' => 'required|string',
            'gender' => 'required|in:male,female',
            'specialty' => 'required|string',
            'years_of_experience' => 'required|integer',
            'credential_document' => 'required|file|mimes:pdf,jpg,png|max:2048', // حد أقصى 2 ميجا
        ]);
        $path = $request->file('credential_document')->store('documents', 'public');

        $doctor = Doctor::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'national_id' => $validated['national_id'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'],
            'date_of_birth' => $validated['date_of_birth'],
            'address' => $validated['address'],
            'gender' => $validated['gender'],
            'specialty' => $validated['specialty'],
            'years_of_experience' => $validated['years_of_experience'],
            'credential_document' => $path,
            'status' => 'inactive',
        ]);

        $token = $doctor->createToken('doctor_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الطبيب بنجاح, بانتظار التفعيل, ستصلك رسالة عبر الايميل الخاص بك لضمان التفعيل',
            'access_token' => $token,
            'doctor' => $doctor,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);

        $doctor = Doctor::where('email', $request->email)->first();

        if (! $doctor || ! Hash::check($request->password, $doctor->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }

        // التحقق من حالة التفعيل
        if ($doctor->status !== 'active') {
            return response()->json(['message' => 'حسابك بانتظار موافقة الإدارة'], 403);
        }

        return response()->json([
            'access_token' => $doctor->createToken('doctor_token')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'email' => $doctor->email,
                'role' => 'doctor',
            ],
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:doctors,email']);

        $doctor = Doctor::where('email', $request->email)->first();
        
        // توليد الـ Token الخاص بـ لارافيل
        $token = Password::createToken($doctor);
        
        // إرسال الإيميل عبر خدمتنا
        NotificationService::send('password_reset', $doctor, ['token' => $token]);

        return response()->json(['message' => 'تم إرسال رابط إعادة تعيين كلمة السر إلى إيميلك']);
    }
    public function resetPassword(Request $request)
{
    // التحقق من البيانات
    $request->validate([
        'email' => 'required|email|exists:doctors,email',
        'token' => 'required',
        'password' => 'required|confirmed|min:8',
    ]);

    // تنفيذ إعادة التعيين
    $status = Password::broker()->reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->password = \Illuminate\Support\Facades\Hash::make($password);
            $user->save();
        }
    );

    if ($status === Password::PASSWORD_RESET) {
        return response()->json(['message' => 'تم تغيير كلمة سر الطبيب بنجاح'], 200);
    }

    return response()->json(['message' => 'فشل إعادة التعيين، التوكين غير صالح'], 400);
}
}
