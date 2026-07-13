<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\PatientProfile;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function getAllPatients()
    {
        // جلب جميع الأدمنز (يفضل دائماً عدم إرجاع كلمة السر)
        $admins = Patient::select('id', 'name', 'email', 'created_at')->get();

        return response()->json([
            'status' => true,
            'message' => 'تم جلب قائمة المرضى بنجاح',
            'data' => $admins,
        ], 200);
    }

 
    public function profile(Request $request)
    {
        // نستخدم load لتحميل العلاقة مع المريض في نفس الاستعلام
        $patient = $request->user()->load('profile');

        return response()->json([
            'message' => 'بيانات المريض الشخصية والطبية',
            'user' => $patient,
        ], 200);
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

    public function getMedicalProfile(Request $request)
    {
        // نستخدم firstOrCreate لضمان وجود بروفايل دائماً
        $profile = PatientProfile::firstOrCreate(
            ['patient_id' => $request->user()->id],  
            [ 
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
            'data' => $profile,
        ], 200);
    }

    // عرض كل السجلات الطبية للمريض
    public function myMedicalRecords(Request $request)
    {
        // جلب كل السجلات الطبية مع بيانات الطبيب الذي كتبها
        $records = MedicalRecord::where('patient_id', $request->user()->id)
            ->with(['doctor:id,name', 'appointment:id,scheduled_at'])
            ->latest() // الأحدث أولاً
            ->get();

        return response()->json(['data' => $records]);
    }

    public function getBroadcasts()
    {
        // جلب الرسائل الموجهة للجميع أو للمرضى فقط
        $broadcasts = Broadcast::whereIn('target', ['all', 'patients'])
            ->latest()
            ->get();

        return response()->json(['data' => $broadcasts], 200);
    }
}
