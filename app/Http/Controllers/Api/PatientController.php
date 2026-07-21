<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Broadcast;
use App\Models\MedicalRecord;
use App\Models\User;
use App\Models\PatientProfile;
use Illuminate\Support\Facades\Storage;
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

    public function updateAccount(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $request->user()->id,
            'phone' => 'nullable|string|max:20',
            'national_id' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|string',
            'address' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'national_id' => $validated['national_id'],
            'birth_date' => $validated['birth_date'],
            'gender' => $validated['gender'],
            'address' => $validated['address'],
        ]);

        return response()->json([
            'message' => 'تم تحديث بيانات الحساب بنجاح',
            'user' => $user,
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
        $records = Appointment::where('patient_id', $request->user()->id)
            ->where('status', 'completed')
            ->with('doctor:id,name')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'تم استرجاع السجلات الطبية',
            'data' => $records,
        ]);
    }

    public function getBroadcasts()
    {
        $broadcasts = Broadcast::whereIn('target', ['all', 'patients'])
            ->latest()
            ->get();

        return response()->json(['data' => $broadcasts], 200);
    }

public function updateProfilePicture(Request $request)
{
    try {

        $request->validate([
            'profile_picture' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = $request->user();

        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $path = $request->file('profile_picture')
            ->store('profile_pictures', 'public');

        $user->update([
            'profile_picture' => $path,
        ]);

        return response()->json([
            'message' => 'تم تحديث الصورة الشخصية',
            'profile_picture' => asset('storage/' . $path),
        ]);

    } catch (\Throwable $e) {

        return response()->json([
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ], 500);

    }
}
}
