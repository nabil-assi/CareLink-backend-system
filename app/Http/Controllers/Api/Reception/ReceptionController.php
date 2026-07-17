<?php

namespace App\Http\Controllers\Api\Reception;

use App\Http\Controllers\Controller;
use App\Models\Patient; // الموديل الجديد للمرضى
use Illuminate\Http\Request;

class ReceptionController extends Controller
{

 // عملت موديل جديد للمرضى اللي بيسجلو من خلال الاستقبال لانو الموديل الخاص بالمستخدمين اللي هيفوتوا عالنظام بيحتوي
 // حقول اساسية زي الباسوررد والاليميل
 // ف مش منطق نقوم نطلب منه هيك وهو يدوب بتنفس
 // ف عملت هاد وبرضو يعني لو هو حابب يفوت ع النظام ممكن نحط في التصميم
 // اذا الك سجل طبي في المستشفى ويطلب منه ادخال رقم الهوية والتاريخ ميلاد او اشي عشان نتاكد وبعدها 
 //الايميل والباسوورد ...الخ
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
}
