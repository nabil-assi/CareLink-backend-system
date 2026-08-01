<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ImagingOrder;
use App\Models\LabOrder;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = Appointment::with(['patient', 'doctor', 'rating'])->latest()->get();

        return response()->json([
            'message' => 'تم استرجاع جميع المواعيد بنجاح',
            'data' => $appointments,
        ], 200);
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'doctor_id' => 'required|exists:users,id,role,doctor',
        'scheduled_at' => 'required|date',
        'type' => 'sometimes|in:online,in_person', // التحقق من نوع الموعد
        'description' => 'nullable|string|max:500', // التحقق من الوصف
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

    // إنشاء الموعد مع حفظ النوع والوصف المرسل من الواجهة الأمامية
    $appointment = Appointment::create([
        'patient_id' => $request->user()->id,
        'patient_type' => User::class,
        'doctor_id' => $validated['doctor_id'],
        'scheduled_at' => $validated['scheduled_at'],
        'type' => $validated['type'] ?? 'in_person', // حفظ النوع أو افتراضي
        'description' => $validated['description'] ?? null, // حفظ الوصف
        'status' => 'pending',
    ]);

    return response()->json([
        'message' => 'تم حجز الموعد بنجاح',
        'data' => $appointment,
    ], 201);
}
    public function getPatientAppointments(Request $request)
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

    public function getDoctorAppointments()
    {
        // الحصول على معرف الطبيب المسجل حالياً
        $doctorId = auth()->id();

        // جلب المواعيد الخاصة بهذا الطبيب فقط مع بيانات المريض
        $appointments = Appointment::where('doctor_id', $doctorId)
            ->with('patient') // جلب بيانات المريض المرتبط بالموعد
            ->orderBy('scheduled_at', 'desc') // ترتيب المواعيد من الأقدم للأحدث
            ->get();

        return response()->json([
            'message' => 'تم استرجاع مواعيدك بنجاح',
            'data' => $appointments,
        ], 200);
    }

    public function showDoctorAppointment($id)
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

        // 2. التحقق من أن المستخدم صاحب الموعد فعلاً (نفس منطق reschedule تحت) —
        // قبل هيك أي مستخدم مسجل دخول فيه يلغي أي موعد بالنظام بس يخمن الـ id بالرابط
        $user = $request->user();
        $isOwnerPatient = $appointment->patient_type === User::class && $appointment->patient_id == $user->id;
        $isAssignedDoctor = $appointment->doctor_id == $user->id;

        if (!$isOwnerPatient && !$isAssignedDoctor) {
            return response()->json([
                'message' => 'غير مصرح لك بإلغاء هذا الموعد',
            ], 403);
        }

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

        // المريض هلق بينجلب من الموعد نفسه (appointment->patient) مش من الـ request -
        // appointment_id صار عمود حقيقي بجدول lab_orders ويدعم مريض الاستقبال والحساب الذاتي سوا
        $labOrder = LabOrder::create([
            'appointment_id' => $id,
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

        // كان الكود بس بيحفظ النص جوا الموعد وما بيوصل عالإطلاق لجدول
        // prescriptions يلي شاشة الصيدلية فعلياً بتقرأ منه - الصيدلية ما
        // كانت تشوف ولا وصفة أبداً. updateOrCreate عشان لو الطبيب عدّل
        // الوصفة لنفس الموعد ما يصير عنده سجل مكرر بقائمة الصيدلية
        Prescription::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'diagnosis' => $appointment->diagnosis,
                'notes' => $validated['medications'],
                'status' => 'pending',
            ]
        );

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

        // patient() علاقة polymorphic بترجع User أو Patient حسب الموعد، وPatient
        // (مريض الاستقبال) ما إله علاقة patientProfile أصلاً - عمل eager load
        // لـ 'patient.patientProfile' كان بيكسر الصفحة (500) لأي طبيب عنده موعد
        // واحد بس لمريض استقبال، لهيك بنجيبها بس لما يكون المريض User فعلاً
        $patients = Appointment::where('doctor_id', $doctorId)
            ->with('patient')
            ->get()
            ->pluck('patient')
            ->filter()
            ->unique(fn ($patient) => get_class($patient).':'.$patient->id)
            ->values()
            ->map(function ($patient) {
                // محاولة جلب فصيلة الدم سواء كان المريض User أو Patient
                $bloodType = null;

                if ($patient instanceof \App\Models\User) {
                    $bloodType = $patient->patientProfile?->blood_type;
                } elseif (isset($patient->blood_type)) {
                    $bloodType = $patient->blood_type;
                }

                return [
                    'id' => $patient->id,
                    'full_name' => $patient->full_name ?? $patient->name ?? 'مريض',
                    'phone' => $patient->phone ?? null,
                    'email' => $patient->email ?? null,
                    'blood_type' => $bloodType,
                    'national_id' => $patient->national_id ?? $patient->nationalId ?? null,
                ];
            });

        return response()->json([
            'message' => 'تم استرجاع قائمة المرضى بنجاح',
            'data' => $patients,
        ], 200);
    }

    public function doctorPatientDetail($id)
    {
        $doctorId = auth()->id();

        // جلب جميع مواعيد هذا الطبيب مع مرضاهم - بدون nested eager load لـ
        // patientProfile لأنه Patient (مريض الاستقبال) ما إله هاي العلاقة
        // أصلاً وكانت بتكسر الصفحة (500) لأي طبيب عنده موعد لمريض استقبال
        $appointments = Appointment::where('doctor_id', $doctorId)
            ->with('patient')
            ->get();

        // البحث عن الموعد الذي يطابق المريض المطلوبة (سواء كان ID المريض أو ID الـ User المرتبط)
        $appointment = $appointments->first(function ($apt) use ($id) {
            return $apt->patient && (
                $apt->patient->id == $id || 
                (isset($apt->patient->user_id) && $apt->patient->user_id == $id)
            );
        });

        if (! $appointment || ! $appointment->patient) {
            return response()->json(['message' => 'المريض غير موجود أو ليس لديك صلاحية لعرضه'], 404);
        }

        $patient = $appointment->patient;

        // جلب كل مواعيد هذا المريض المحدد مع هذا الطبيب - لازم patient_type
        // كمان، وإلا ممكن تنجر مواعيد مريض تاني (User أو Patient) تصادف نفس الـ id
        $patientAppointments = Appointment::where('doctor_id', $doctorId)
            ->where('patient_id', $patient->id)
            ->where('patient_type', get_class($patient))
            ->orderBy('scheduled_at', 'desc')
            ->get();

        // استخراج فصيلة الدم بأمان
        $bloodType = null;
        if (method_exists($patient, 'patientProfile') && $patient->patientProfile) {
            $bloodType = $patient->patientProfile->blood_type;
        } elseif (isset($patient->blood_type)) {
            $bloodType = $patient->blood_type;
        }

        return response()->json([
            'message' => 'تم استرجاع تفاصيل المريض بنجاح',
            'data' => [
                'id' => $patient->id,
                'full_name' => $patient->full_name ?? $patient->name ?? 'مريض',
                'phone' => $patient->phone ?? null,
                'email' => $patient->email ?? null,
                'national_id' => $patient->national_id ?? $patient->nationalId ?? null,
                'blood_type' => $bloodType,
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
            'data' => $order,
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

    public function showPatientAppointment($id, Request $request)
    {
        // patient_type لازم كمان كفحص ملكية - وإلا مريض ممكن يشوف تفاصيل موعد
        // مريض استقبال (Patient) لو صدفة نفس الرقم متطابق مع الـ id تبعه
        $appointment = Appointment::where('id', $id)
            ->where('patient_id', $request->user()->id)
            ->where('patient_type', User::class)
            ->with([
                'doctor',
                'doctor.doctorProfile',
                'prescription',
                'medicalRecord',
            ])
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'data' => $appointment,
        ], 200);
    }
}
