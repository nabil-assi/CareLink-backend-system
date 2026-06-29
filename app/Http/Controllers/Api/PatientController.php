<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * عرض بيانات المريض الشخصية
     * المسار: GET /api/patient/profile
     */
    public function profile(Request $request)
    {
        // نستخدم load لتحميل العلاقة مع المريض في نفس الاستعلام
        $patient = $request->user()->load('profile');

        return response()->json([
            'message' => 'بيانات المريض الشخصية والطبية',
            'user' => $patient,
        ], 200);
    }

    /**
     * تحديث بيانات المريض (اختياري)
     * المسار: PUT /api/patient/profile/update
     */
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


public function getMedicalProfile(Request $request)
{
    // نستخدم firstOrCreate لضمان وجود بروفايل دائماً
    $profile = \App\Models\PatientProfile::firstOrCreate(
        ['patient_id' => $request->user()->id], // الشرط: هل يوجد بروفايل لهذا المريض؟
        [ // البيانات الافتراضية إذا لم يوجد
            'blood_type' => null,
            'weight_kg' => 0,
            'height_cm' => 0,
            'is_diabetic' => false,
            'is_hypertensive' => false,
            'is_smoker' => false,
        ]
    );

    return response()->json([
        'message' => 'بيانات الملف الطبي',
        'data' => $profile
    ], 200);
}
}
