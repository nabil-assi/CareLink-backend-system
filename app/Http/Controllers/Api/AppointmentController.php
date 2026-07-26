<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ImagingOrder;
use App\Models\LabOrder;
use App\Models\User;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:users,id,role,doctor',
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

        // إنشاء الموعد - المريض هون هو صاحب حساب (User)، عكس مواعيد الاستقبال يلي بتحجز لـ Patient
        $appointment = Appointment::create([
            'patient_id'   => $request->user()->id,
            'patient_type' => User::class,
            'doctor_id'    => $validated['doctor_id'],
            'scheduled_at' => $validated['scheduled_at'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'تم حجز الموعد بنجاح',
            'data' => $appointment,
        ], 201);
    }

   public function index(Request $request)
{
    // patient_type لازم كمان، وإلا ممكن نجيب موعد مريض استقبال بالصدفة إذا تصادف نفس الـ id
    $appointments = Appointment::where('patient_id', $request->user()->id)
        ->where('patient_type', User::class)
        ->with(['doctor', 'doctor.doctorProfile'])
        ->get();

    return response()->json([
        'message' => 'تم استرجاع مواعيدك بنجاح',
        'data' => $appointments,
    ], 200);
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

    public function getAllMedicalRecords()
    {
        $doctorId = auth()->id();

        $records = Appointment::where('doctor_id', $doctorId)
            ->whereNotNull('diagnosis') // المواعيد التي تم تسجيل تشخيص لها فقط
            ->with('patient')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'تم استرجاع السجلات الطبية بنجاح',
            'data' => $records,
        ], 200);
    }

    public function getMedicalRecord($appointmentId)
    {
        $doctorId = auth()->id();

        $appointment = Appointment::where('id', $appointmentId)
            ->where('doctor_id', $doctorId)
            ->with('patient')
            ->firstOrFail();

        return response()->json([
            'message' => 'تم استرجاع السجل الطبي بنجاح',
            'data' => [
                'diagnosis' => $appointment->diagnosis,
                'clinical_notes' => $appointment->clinical_notes,
                'lab_tests' => $appointment->lab_tests,
                'medications' => $appointment->medications,
                'status' => $appointment->status,
                'patient' => $appointment->patient,
            ],
        ], 200);
    }

    public function storeMedicalRecord(Request $request, $appointmentId)
    {
        $doctorId = auth()->id();

        $appointment = Appointment::where('id', $appointmentId)
            ->where('doctor_id', $doctorId)
            ->firstOrFail();

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
            'message' => 'تم حفظ السجل الطبي بنجاح',
            'data' => $appointment,
        ], 200);
    }

    public function storeLabOrder(Request $request, $id)
    {
        $request->validate([
            'tests' => 'required|string',
            'priority' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // يمكنك جلب الموعد لمعرفة المريض والطبيب المرتبطين به
        // $appointment = Appointment::findOrFail($id);

        $labOrder = LabOrder::create([
            'appointment_id' => $id, // أضف حقل appointment_id إذا لم يكن موجوداً في جدول lab_orders
            'patient_id' => $request->patient_id, // أو جلبها من الموعد
            'doctor_id' => auth()->id(),
            'tests' => $request->tests,
            'clinical_reason' => $request->notes ?? 'طلب فحص طبي من الطبيب المعالج',
            'priority' => $request->priority ?? 'routine',
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إرسال طلب التحاليل بنجاح',
            'data' => $labOrder,
        ], 201);
    }

    // إنشاء طلب أشعة للمريض تبع الموعد (زي storeLabOrder بالظبط، بس بدون
    // عمود patient_id منفصل - المريض بينجلب دايماً من appointment->patient)
    public function storeImagingOrder(Request $request, $id)
    {
        $request->validate([
            'studies' => 'required|string',
            'modality' => 'nullable|string',
            'anatomy' => 'nullable|string',
            'priority' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $imagingOrder = ImagingOrder::create([
            'appointment_id' => $id,
            'doctor_id' => auth()->id(),
            'studies' => $request->studies,
            'modality' => $request->modality,
            'anatomy' => $request->anatomy,
            'clinical_reason' => $request->notes ?: 'طلب تصوير من الطبيب المعالج',
            'notes' => $request->notes,
            'priority' => $request->priority ?? 'routine',
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إرسال طلب الأشعة بنجاح',
            'data' => $imagingOrder,
        ], 201);
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

    public function doctorPatients()
    {
        $doctorId = auth()->id();

        // المريض ممكن يكون User (حجز بنفسه) أو Patient (سجله الاستقبال)، فبنعتمد
        // على العلاقة polymorphic ($appointment->patient) بدل ما نفترض جدول واحد بس
        $patients = Appointment::where('doctor_id', $doctorId)
            ->with('patient')
            ->get()
            ->pluck('patient')
            ->filter()
            ->unique(fn ($patient) => get_class($patient).':'.$patient->id)
            ->values()
            ->map(fn ($patient) => [
                'id' => $patient->id,
                'full_name' => $patient->full_name ?? $patient->name ?? 'مريض',
                'phone' => $patient->phone ?? null,
            ]);

        return response()->json([
            'message' => 'تم استرجاع قائمة المرضى بنجاح',
            'data' => $patients,
        ], 200);
    }

    public function doctorPatientDetail($id)
    {
        $doctorId = auth()->id();

    // التأكد أن المريض لديه موعد مع هذا الطبيب، وناخد منه patient_type الصحيح
    $appointment = Appointment::where('doctor_id', $doctorId)
        ->where('patient_id', $id)
        ->with('patient')
        ->first();

    if (!$appointment || !$appointment->patient) {
        return response()->json(['message' => 'المريض غير موجود أو ليس لديك صلاحية لعرضه'], 404);
    }

    $patient = $appointment->patient;

    // جلب كل مواعيد هذا المريض (نفس patient_id ونفس patient_type) مع هذا الطبيب
    $patientAppointments = Appointment::where('doctor_id', $doctorId)
        ->where('patient_id', $id)
        ->where('patient_type', $appointment->patient_type)
        ->orderBy('scheduled_at', 'desc')
        ->get();

        // إرسال البيانات مجتمعة
        return response()->json([
            'message' => 'تم استرجاع تفاصيل المريض بنجاح',
            'data' => [
                'id' => $patient->id,
                'full_name' => $patient->full_name ?? $patient->name ?? 'مريض',
                'phone' => $patient->phone,
                'national_id' => $patient->national_id ?? null,
                'blood_type' => $patient->blood_type ?? null,
                'appointments' => $patientAppointments,
            ],
        ], 200);
    }

    public function reschedule(Request $request, $id)
    {
        $validated = $request->validate([
            'scheduled_at' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $appointment = Appointment::findOrFail($id);

        // تأكد أن المريض هو صاحب الموعد (ولازم نتحقق من patient_type كمان، مش بس الـ id)
        if ($appointment->patient_type !== User::class || $appointment->patient_id != $request->user()->id) {
            return response()->json([
                'message' => 'غير مصرح لك بتعديل هذا الموعد',
            ], 403);
        }

        // تأكد أن الوقت الجديد غير محجوز
        $exists = Appointment::where('doctor_id', $appointment->doctor_id)
            ->where('scheduled_at', $validated['scheduled_at'])
            ->whereIn('status', ['pending', 'scheduled'])
            ->where('id', '!=', $appointment->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'هذا الموعد محجوز مسبقاً',
            ], 409);
        }

        $appointment->update([
            'scheduled_at' => $validated['scheduled_at'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'تم تحديث الموعد بنجاح',
            'data' => $appointment,
        ]);
    }

    public function storeRating(Request $request, $appointmentId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $appointment = Appointment::findOrFail($appointmentId);

        // التحقق أن الزيارة منتهية وأن المريض هو صاحب الموعد
        if ($appointment->status !== 'completed') {
            return response()->json(['message' => 'لا يمكن تقييم موعد غير منتهي'], 422);
        }

        // التأكد من عدم تكرار التقييم لنفس الموعد
        $existingRating = DoctorRating::where('appointment_id', $appointment->id)->first();
        if ($existingRating) {
            return response()->json(['message' => 'تم تقييم هذه الزيارة مسبقاً'], 422);
        }

        DoctorRating::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
            'patient_id' => auth()->id(),
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json(['message' => 'تم إضافة تقييمك بنجاح. شكراً لك!'], 200);
    }

   public function complete(Request $request, $id)
{
    $order = LabOrder::findOrFail($id);
    
    // التقاط أي بيانات قادمة من الفرونت إند فوراً دون أي قيود
    $resultText = $request->input('result_text') 
               ?? $request->input('resultText') 
               ?? $request->input('result') 
               ?? $request->input('notes') 
               ?? '—';

    $order->update([
        'status' => 'completed',
        'result_text' => is_array($resultText) ? json_encode($resultText) : $resultText,
        'completed_by' => $request->input('completed_by') ?? 'فني مختبر',
        'completed_at' => now(),
    ]);

    return response()->json([
        'status' => true,
        'message' => 'تم إرسال النتيجة بنجاح',
        'data' => $order
    ], 200);
}
public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);

        $appointment = Appointment::where('doctor_id', auth()->id())->findOrFail($id);
        
        $appointment->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'تم تحديث حالة الموعد بنجاح',
            'data' => $appointment,
        ], 200);
    }
}
