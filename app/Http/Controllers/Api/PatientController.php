<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\MedicalRecord;
use App\Models\User;
use App\Models\PatientProfile;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function getAllPatients()
    {
        $patients = User::where('role', 'patient')
            ->select('id', 'name', 'email', 'created_at')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'تم جلب قائمة المرضى بنجاح',
            'data' => $patients,
        ], 200);
    }

    public function profile(Request $request)
    {
        $patient = $request->user()->load('patientProfile');

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

        auth()->user()->patientProfile()->updateOrCreate(
            ['user_id' => auth()->id()],
            $validated
        );

        return response()->json([
            'message' => 'تم تحديث الملف الطبي بنجاح',
            'profile' => auth()->user()->patientProfile()->first(),
        ]);
    }

    public function getMedicalProfile(Request $request)
    {
        $profile = PatientProfile::firstOrCreate(
            ['user_id' => $request->user()->id]
        );

        return response()->json([
            'message' => 'بيانات الملف الطبي',
            'data' => $profile,
        ], 200);
    }

    public function myMedicalRecords(Request $request)
    {
        $records = MedicalRecord::where('patient_id', $request->user()->id)
            ->with(['doctor:id,name', 'appointment:id,scheduled_at'])
            ->latest()
            ->get();

        return response()->json(['data' => $records]);
    }

    public function getBroadcasts()
    {
        $broadcasts = Broadcast::whereIn('target', ['all', 'patients'])
            ->latest()
            ->get();

        return response()->json(['data' => $broadcasts], 200);
    }
}