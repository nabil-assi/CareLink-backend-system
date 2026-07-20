<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_id'    => 'required|exists:users,id',
            'scheduled_at' => 'required|date',
        ]);

        // التحقق إذا كان الموعد محجوزاً مسبقاً
        $exists = Appointment::where('doctor_id', $validated['doctor_id'])
            ->where('scheduled_at', $validated['scheduled_at'])
            ->whereIn('status', ['pending', 'scheduled'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'هذا الموعد محجوز مسبقاً',
            ], 409);
        }

        // إنشاء الموعد
        $appointment = Appointment::create([
            'patient_id'   => $request->user()->id,
            'doctor_id'    => $validated['doctor_id'],
            'scheduled_at' => $validated['scheduled_at'],
            'status'       => 'pending',
        ]);

        return response()->json([
            'message' => 'تم حجز الموعد بنجاح',
            'data'    => $appointment,
        ], 201);
    }

    public function index(Request $request)
    {
        //الطبيب سيحصل على مواعيده.
        //المريض سيحصل على مواعيده.
        $user = $request->user();

        if ($user->role === 'doctor') {

            $appointments = Appointment::where('doctor_id', $user->id)
                ->with('patient')
                ->orderBy('scheduled_at')
                ->get();

        } else {

            $appointments = Appointment::where('patient_id', $user->id)
                ->with('doctor')
                ->orderBy('scheduled_at')
                ->get();

        }

        return response()->json([
            'message' => 'تم استرجاع مواعيدك بنجاح',
            'data' => $appointments,
        ]);
    }
    // public function index()
    // {
    //     // الحصول على معرف الطبيب المسجل حالياً
    //     $doctorId = auth()->id();

    //     // جلب المواعيد الخاصة بهذا الطبيب فقط مع بيانات المريض
    //     $appointments = Appointment::where('doctor_id', $doctorId)
    //         ->with('patient') // جلب بيانات المريض المرتبط بالموعد
    //         ->orderBy('scheduled_at', 'asc') // ترتيب المواعيد من الأقدم للأحدث
    //         ->get();

    //     return response()->json([
    //         'message' => 'تم استرجاع مواعيدك بنجاح',
    //         'data' => $appointments,
    //     ], 200);
    // }

    public function show($id)
    {
        $doctorId = auth()->id();

        $appointment = Appointment::where('id', $id)
            ->where('doctor_id', $doctorId)
            ->with('patient')
            ->first();

        if (! $appointment) {
            return response()->json(['message' => 'الموعد غير موجود'], 404);
        }

        return response()->json([
            'message' => 'تم استرجاع تفاصيل الموعد بنجاح',
            'data' => $appointment,
        ], 200);
    }

    public function saveDiagnosis(Request $request, $id)
    {
        $doctorId = auth()->id();
        $appointment = Appointment::where('id', $id)->where('doctor_id', $doctorId)->firstOrFail();

        $validated = $request->validate([
            'diagnosis' => 'required|string',
            'clinical_notes' => 'nullable|string',
        ]);

        $appointment->update([
            'diagnosis' => $validated['diagnosis'],
            'clinical_notes' => $validated['clinical_notes'] ?? null,
            'status' => 'with_doctor',
        ]);

        return response()->json([
            'message' => 'تم حفظ التشخيص بنجاح',
            'data' => $appointment,
        ]);
    }

    public function cancel(Request $request, $id)
    {
        // 1. البحث عن الموعد
        $appointment = Appointment::findOrFail($id);

        // 2. اختيارياً: التحقق من أن المستخدم لديه صلاحية للإلغاء
        // (مثلاً الطبيب أو موظف الاستقبال فقط)

        // 3. تحديث الحالة
        $appointment->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'message' => 'تم إلغاء الموعد بنجاح',
            'appointment' => $appointment,
        ], 200);
    }

    public function getMedicalRecord($appointmentId)
    {
        // 1. البحث عن الموعد مع السجل الطبي التابع له
        $appointment = Appointment::with('medicalRecord')->findOrFail($appointmentId);

        // 2. التأكد من وجود سجل طبي للموعد
        if (! $appointment->medicalRecord) {
            return response()->json(['message' => 'لا يوجد سجل طبي لهذا الموعد'], 404);
        }

        return response()->json([
            'message' => 'تم استرجاع السجل الطبي بنجاح',
            'data' => $appointment->medicalRecord,
        ], 200);
    }





    public function storeLabOrder(Request $request, $id)
    {
        $doctorId = auth()->id();
        $appointment = Appointment::where('id', $id)->where('doctor_id', $doctorId)->firstOrFail();

        $validated = $request->validate([
            'tests' => 'required|string',
        ]);

        $appointment->update([
            'lab_tests' => $validated['tests'],
            'lab_status' => 'pending',
            'status' => 'awaiting_lab',
        ]);

        return response()->json([
            'message' => 'تم إرسال طلب التحاليل بنجاح',
            'data' => $appointment,
        ]);
    }

    public function storePrescription(Request $request, $id)
    {
        $doctorId = auth()->id();
        $appointment = Appointment::where('id', $id)->where('doctor_id', $doctorId)->firstOrFail();

        $validated = $request->validate([
            'medications' => 'required|string',
        ]);

        $appointment->update([
            'medications' => $validated['medications'],
            'status' => 'awaiting_pharmacy',
        ]);

        return response()->json([
            'message' => 'تم إرسال الوصفة الطبية بنجاح',
            'data' => $appointment,
        ]);
    }

    public function completeAppointment($id)
    {
        $doctorId = auth()->id();
        $appointment = Appointment::where('id', $id)->where('doctor_id', $doctorId)->firstOrFail();

        $appointment->update([
            'status' => 'completed',
        ]);

        return response()->json([
            'message' => 'تم إنهاء الزيارة بنجاح',
            'data' => $appointment,
        ]);
    }
}
