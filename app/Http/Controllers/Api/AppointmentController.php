<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    // حجز موعد جديد (للمريض)
    public function store(Request $request)
    {
        $validated = $request->validate([
            // التأكد أن المستخدم هو طبيب
            'doctor_id' => 'required|exists:users,id',
            'scheduled_at' => 'required|date|after:now',
            'type' => 'required|in:online,in_person',
            'description' => 'nullable|string',
        ]);

        // التحقق الإضافي أن الشخص المختار طبيب فعلاً
        $doctor = User::where('id', $validated['doctor_id'])->where('role', 'doctor')->first();
        if (!$doctor) {
            return response()->json(['message' => 'المعرف المختار ليس لطبيب'], 422);
        }
        // التحقق أن الطبيب غير مشغول في نفس الوقت
        $existingAppointment = Appointment::where('doctor_id', $validated['doctor_id'])
            ->where('scheduled_at', $validated['scheduled_at'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($existingAppointment) {
            return response()->json([
                'message' => 'الطبيب لديه موعد آخر في هذا الوقت، يرجى اختيار وقت مختلف.'
            ], 409);
        }

        $appointment = $request->user()->appointments()->create([
            'doctor_id' => $validated['doctor_id'],
            // 'patient_id' => $request->user()->id, // مأخوذ من auth
            'scheduled_at' => $validated['scheduled_at'],
            'type' => $validated['type'],
            'description' => $validated['description'],
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'تم طلب الموعد بانتظار موافقة الطبيب', 'data' => $appointment], 201);
    }

    // عرض مواعيد المريض
    public function index(Request $request)
    {
        // استخدام العلاقة المباشرة من الـ User
        return response()->json(['data' => $request->user()->appointments()->with('doctor:id,name')->get()]);
    }

    // إلغاء موعد (للمريض أو الطبيب)
    public function cancel(Request $request, $id)
    {
        $appointment = Appointment::where('id', $id)
            ->where(function ($q) use ($request) {
                $q->where('patient_id', $request->user()->id)
                    ->orWhere('doctor_id', $request->user()->id);
            })->firstOrFail();

        $appointment->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->reason ?? 'No reason provided',
        ]);

        return response()->json(['message' => 'تم إلغاء الموعد']);
    }

    public function storeMedicalRecord(Request $request, $appointmentId)
    {
        $validated = $request->validate([
            'record_type' => 'required|in:diagnosis,lab_result,prescription,radiology',
            'diagnosis' => 'nullable|string',
            'notes' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,png|max:5120',
        ]);

        // التأكد أن الموعد يخص الطبيب الحالي
        $appointment = Appointment::where('id', $appointmentId)
            ->where('doctor_id', $request->user()->id)
            ->firstOrFail();

        $data = [
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $request->user()->id,
            'appointment_id' => $appointment->id,
            'record_type' => $validated['record_type'],
            'diagnosis' => $validated['diagnosis'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('medical_records', 'public');
            $data['file_url'] = $path;
            $data['file_name'] = $request->file('file')->getClientOriginalName();
        }

        $record = MedicalRecord::create($data);

        return response()->json(['message' => 'تم حفظ السجل الطبي بنجاح', 'data' => $record]);
    }

    public function getMedicalRecord(Request $request, $appointmentId)
    {
        $appointment = Appointment::where('id', $appointmentId)
            ->where('doctor_id', $request->user()->id)
            ->firstOrFail();

        $record = MedicalRecord::where('appointment_id', $appointment->id)->first();

        if (! $record) {
            return response()->json(['message' => 'لا يوجد سجل طبي لهذا الموعد بعد'], 404);
        }

        return response()->json(['data' => $record]);
    }

    public function completeAppointment(Request $request, $id)
    {
        $appointment = Appointment::where('id', $id)
            ->where('doctor_id', $request->user()->id)
            ->firstOrFail();

        $appointment->update(['status' => 'completed']);

        return response()->json(['message' => 'تم إنهاء الموعد بنجاح']);
    }
}
