<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
}
