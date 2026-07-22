<?php

namespace App\Http\Controllers\Api\Reception;

use App\Http\Controllers\Controller;
use App\Models\Appointment; // الموديل الجديد للمرضى
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReceptionController extends Controller
{
    // عملت موديل جديد للمرضى اللي بيسجلو من خلال الاستقبال لانو الموديل الخاص بالمستخدمين اللي هيفوتوا عالنظام بيحتوي
    // حقول اساسية زي الباسوررد والاليميل
    // ف مش منطق نقوم نطلب منه هيك وهو يدوب بتنفس
    // ف عملت هاد وبرضو يعني لو هو حابب يفوت ع النظام ممكن نحط في التصميم
    // اذا الك سجل طبي في المستشفى ويطلب منه ادخال رقم الهوية والتاريخ ميلاد او اشي عشان نتاكد وبعدها
    // الايميل والباسوورد ...الخ
    public function registerPatient(Request $request)
    {
        // التحقق من البيانات المطلوبة فقط
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:patients,phone',
            'national_id' => 'required|string|unique:patients,national_id',
            'birth_date' => 'required|date',
            'address' => 'nullable|string',
        ]);

        // إنشاء سجل مريض جديد مباشرة
        $patient = Patient::create($validated);

        return response()->json([
            'message' => 'تم إنشاء ملف المريض بنجاح',
            'patient' => $patient,
        ], 201);
    }

    public function createAppointment(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'appointment_date' => 'required|date|after:now',
            'type' => 'required|in:online,in_person',
            'description' => 'nullable|string',
        ]);

        // إنشاء الموعد
        $appointment = Appointment::create([
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $validated['doctor_id'],
            'scheduled_at' => $validated['appointment_date'],
            'status' => 'confirmed',
        ]);

        return response()->json([
            'message' => 'تم حجز الموعد بنجاح',
            'appointment' => $appointment,
        ], 201);
    }

    // تعديل موعد (مثلاً تغيير التاريخ أو حالة الموعد)
    public function updateAppointment(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'scheduled_at' => 'sometimes|date|after:now',
            'status' => 'sometimes|in:pending,confirmed,completed,cancelled',
            'description' => 'sometimes|string',
        ]);

        $appointment->update($validated);

        return response()->json([
            'message' => 'تم تحديث الموعد بنجاح',
            'appointment' => $appointment,
        ]);
    }

    // حذف/إلغاء موعد
    public function cancelAppointment($id)
    {
        $appointment = Appointment::findOrFail($id);

        // بدلاً من الحذف الفيزيائي، نغير الحالة لـ cancelled (أفضل في الأنظمة الطبية)
        $appointment->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'تم إلغاء الموعد بنجاح',
        ]);
    }

    public function listPatients()
    {
        // جلب المرضى مباشرة بدون أي استعلام خاطئ عن الـ role
        $patients = Patient::with(['guardian', 'dependents'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $patients], 200);
    }

    public function storePatient(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255', // استقباله باسم name
            'phone' => 'nullable|string',
            'email' => 'nullable|email|unique:patients,email',
            'national_id' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'insurance_status' => 'nullable|string',
            'insurance_provider' => 'nullable|string',
            'guardian_id' => 'nullable|exists:patients,id',
        ]);

        $patient = Patient::create([
            'full_name' => $validated['name'], // تخزينه في قاعدة البيانات كـ full_name
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'insurance_status' => $validated['insurance_status'] ?? 'none',
            'insurance_provider' => $validated['insurance_provider'] ?? null,
            'guardian_id' => $validated['guardian_id'] ?? null,
        ]);

        return response()->json([
            'message' => 'تم تسجيل المريض بنجاح',
            'data' => [
                'id' => $patient->id,
                'name' => $patient->full_name,
                'phone' => $patient->phone,
                'nationalId' => $patient->national_id,
                'insuranceStatus' => $patient->insurance_status,
                'insuranceProvider' => $patient->insurance_provider,
                'guardianId' => $patient->guardian_id,
                'created_at' => $patient->created_at,
            ],
        ], 201);
    }

    public function updatePatientMeta(Request $request, $id)
    {
        $patient = Patient::findOrFail($id);

        $validated = $request->validate([
            'insurance_status' => 'nullable|string',
            'insurance_provider' => 'nullable|string',
            'reception_flags' => 'nullable|array',
        ]);

        $patient->update($validated);

        return response()->json(['message' => 'تم التحديث بنجاح', 'data' => $patient], 200);
    }

    public function listDoctors()
    {
        // تحديد الجدول صراحة لحل أي تداخل محتمل في الاستعلام
        $doctors = User::where('users.role', 'doctor')
            ->select('id', 'name', 'specialty') // تأكد من وجود specialty أو جلبها من doctorProfile إذا كانت منفصلة
            ->get();

        return response()->json(['data' => $doctors], 200);
    }

    public function registerAndBook(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:patients,phone',
            'national_id' => 'required|string|unique:patients,national_id',
            'birth_date' => 'required|date',
            'address' => 'nullable|string',
            // بيانات الحجز (اختيارية إذا أراد الحجز مباشرة أو التسجيل فقط)
            'doctor_id' => 'nullable|exists:users,id',
            'scheduled_at' => 'nullable|date',
            'type' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            // 1. إنشاء المريض
            $patient = Patient::create([
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'],
                'national_id' => $validated['national_id'],
                'birth_date' => $validated['birth_date'],
                'address' => $validated['address'] ?? null,
            ]);

            $appointment = null;

            // 2. إذا تم اختيار طبيب ووقت، يتم حجز موعد له فوراً
            if (! empty($validated['doctor_id']) && ! empty($validated['scheduled_at'])) {
                $appointment = Appointment::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $validated['doctor_id'],
                    'scheduled_at' => $validated['scheduled_at'],
                    'type' => $validated['type'] ?? 'in_person',
                    'notes' => $validated['notes'] ?? 'موعد مسجل من الاستقبال',
                    'status' => 'pending',
                ]);
            }

            return response()->json([
                'message' => 'تم تسجيل المريض وحجز الموعد بنجاح',
                'patient' => [
                    'id' => $patient->id,
                    'name' => $patient->full_name,
                    'phone' => $patient->phone,
                    'nationalId' => $patient->national_id,
                    'insuranceStatus' => 'none',
                    'receptionFlags' => [],
                ],
                'appointment' => $appointment,
            ], 201);
        });
    }

 public function getDoctorSchedule(Request $request)
{
    $request->validate([
        'doctor_id' => 'required|exists:users,id',
        'date' => 'required|date',
    ]);

    $appointments = Appointment::where('doctor_id', $request->doctor_id)
        ->whereDate('scheduled_at', $request->date)
        ->whereIn('status', ['pending', 'confirmed', 'scheduled', 'checked_in', 'with_doctor'])
        ->with('patient:id,name,full_name')
        ->get()
        ->map(function ($apt) {
            return [
                'id' => $apt->id,
                'time' => Carbon::parse($apt->scheduled_at)->format('h:i A'),
                'patient_name' => $apt->patient->full_name ?? $apt->patient->name ?? 'مريض',
                'status' => $apt->status,
                'type' => $apt->type,
            ];
        });

    return response()->json(['data' => $appointments], 200);
}

    // 2. تخزين الموعد الجديد
    public function storeAppointment(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:users,id', // بما أن المريض مستخدم بالنظام حسب الموديل
            'doctor_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'time' => 'required|string',
            'type' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        // دمج التاريخ والوقت في حقل scheduled_at المطلوب بالموديل
        $scheduledAt = date('Y-m-d H:i:s', strtotime("{$validated['date']} {$validated['time']}"));

        $appointment = Appointment::create([
        'patient_id' => $validated['patient_id'],
        'doctor_id' => $validated['doctor_id'],
        'scheduled_at' => $scheduledAt,
        'type' => $validated['type'] ?? 'in_person',
        'status' => 'pending', // مطابقة للـ enum الموجود في جدولك
        'description' => $validated['description'] ?? null,
    ]);

        return response()->json([
            'message' => 'تم حجز الموعد بنجاح',
            'data' => $appointment,
        ], 201);
    }

    public function getWaitingQueue(Request $request)
{
    $date = $request->input('date', Carbon::today()->toDateString());

    $appointments = Appointment::whereDate('scheduled_at', $date)
        // أضفنا 'pending' و 'confirmed' هنا لكي يتم جلبها وتظهر في القائمة
        ->whereIn('status', ['pending', 'confirmed', 'scheduled', 'checked_in', 'with_doctor'])
        ->with(['patient', 'doctor']) // تأكد من صحة علاقات الموديل لديك
        ->orderBy('scheduled_at', 'asc')
        ->get()
        ->map(function ($apt, $index) {
            return [
                'id' => $apt->id,
                'queueNumber' => $index + 1,
                'patient_name' => $apt->patient->full_name ?? $apt->patient->name ?? 'مريض',
                'doctor_name' => $apt->doctor->name ?? 'طبيب',
                'time' => Carbon::parse($apt->scheduled_at)->format('h:i A'),
                'status' => $apt->status,
            ];
        });

    return response()->json([
        'data' => $appointments
    ], 200);
}


public function getAllAppointments(Request $request)
{
    // يمكنك إضافة فلترة اختيارية حسب التاريخ أو الطبيب أو الحالة إذا رغبت، أو جلب الكل مباشرة
    $query = Appointment::with(['patient', 'doctor', 'prescription', 'medicalRecord']);

    // فلترة اختياريّة حسب التاريخ إن تم إرساله
    if ($request->has('date')) {
        $query->whereDate('scheduled_at', $request->input('date'));
    }

    // فلترة اختياريّة حسب الطبيب إن تم إرساله
    if ($request->has('doctor_id')) {
        $query->where('doctor_id', $request->input('doctor_id'));
    }

    // فلترة اختياريّة حسب الحالة إن تم إرسالها
    if ($request->has('status')) {
        $query->where('status', $request->input('status'));
    }

    $appointments = $query->orderBy('scheduled_at', 'desc')->get();

    return response()->json([
        'message' => 'تم جلب جميع الحجوزات بنجاح',
        'count' => $appointments->count(),
        'data' => $appointments
    ], 200);
}
}
