<?php

namespace App\Http\Controllers\Api\Reception;

use App\Http\Controllers\Controller;
use App\Models\Appointment; // الموديل الجديد للمرضى
use App\Models\Patient;
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
            'appointment' => $appointment
        ]);
    }

    // حذف/إلغاء موعد
    public function cancelAppointment($id)
    {
        $appointment = Appointment::findOrFail($id);
        
        // بدلاً من الحذف الفيزيائي، نغير الحالة لـ cancelled (أفضل في الأنظمة الطبية)
        $appointment->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'تم إلغاء الموعد بنجاح'
        ]);
    }
}
