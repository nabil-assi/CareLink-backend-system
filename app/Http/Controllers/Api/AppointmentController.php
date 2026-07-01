<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    // حجز موعد جديد (للمريض)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'scheduled_at' => 'required|date|after:now',
            'type' => 'required|in:online,in_person',
            'description' => 'nullable|string',
        ]);

        // إضافة الـ patient_id من المستخدم المسجل
        $appointment = $request->user()->appointments()->create([
            'doctor_id' => $validated['doctor_id'],
            'scheduled_at' => $validated['scheduled_at'],
            'type' => $validated['type'],
            'description' => $validated['description'],
            'status' => 'pending', // دائماً يبدأ بـ pending
        ]);

        return response()->json(['message' => 'تم طلب الموعد بانتظار موافقة الطبيب', 'data' => $appointment], 201);
    }

    // عرض مواعيد المريض
    public function index(Request $request)
    {
        return response()->json(['data' => $request->user()->appointments()->with('doctor')->get()]);
    }

    // إلغاء موعد (للمريض أو الطبيب)
    public function cancel(Request $request, $id)
    {
        $appointment = Appointment::where('id', $id)
            ->where(function ($q) use ($request) {
                $q->where('patient_id', $request->user()->id)
                    ->orWhere('doctor_id', $request->user()->id); // الطبيب أيضاً يقدر يلغي
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
            // التعامل مع الملفات المرفقة (أشعة/تحاليل) لاحقاً
        ]);

        // جلب الموعد للتأكد أن الطبيب هو من عاين المريض
        $appointment = Appointment::where('id', $appointmentId)
            ->where('doctor_id', $request->user()->id)
            ->firstOrFail();

        // إنشاء السجل الطبي
        $record = MedicalRecord::create([
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $request->user()->id,
            'appointment_id' => $appointment->id,
            ...$validated,
        ]);

        return response()->json(['message' => 'تم حفظ السجل الطبي بنجاح', 'data' => $record]);
    }

    // جلب سجل طبي معين لموعد (للطبيب)
    public function getMedicalRecord(Request $request, $appointmentId)
    {
        // نتأكد أن الموعد يخص هذا الطبيب
        $appointment = Appointment::where('id', $appointmentId)
            ->where('doctor_id', $request->user()->id)
            ->firstOrFail();

        $record = MedicalRecord::where('appointment_id', $appointment->id)->first();

        if (! $record) {
            return response()->json(['message' => 'لا يوجد سجل طبي لهذا الموعد بعد'], 404);
        }

        return response()->json(['data' => $record]);
    }

    // في AppointmentController
    public function completeAppointment(Request $request, $id)
    {
        $appointment = Appointment::where('id', $id)
            ->where('doctor_id', $request->user()->id)
            ->firstOrFail();

        $appointment->update(['status' => 'completed']);

        return response()->json(['message' => 'تم إنهاء الموعد بنجاح']);
    }
}
