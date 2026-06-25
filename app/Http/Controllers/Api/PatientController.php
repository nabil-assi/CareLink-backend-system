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
        // $request->user() يجلب بيانات المريض المسجل حالياً عبر Sanctum
        return response()->json([
            'message' => 'بيانات المريض الشخصية',
            'user' => $request->user()
        ], 200);
    }

    /**
     * تحديث بيانات المريض (اختياري)
     * المسار: PUT /api/patient/profile/update
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'string|max:255',
            'phone' => 'string|max:15',
            'address' => 'string|max:255',
        ]);

        $patient = $request->user();
        $patient->update($request->all());

        return response()->json([
            'message' => 'تم تحديث البيانات بنجاح',
            'user' => $patient
        ], 200);
    }
}