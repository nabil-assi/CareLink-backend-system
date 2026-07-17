<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function store(Request $request)
    {
        // 1. التحقق من البيانات (نستقبل patient_id من الاستقبال)
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'scheduled_at' => 'required|date|after:now',
            'type' => 'required|in:online,in_person',
            'description' => 'nullable|string',
        ]);

        // 2. التحقق أن الطبيب المختار هو طبيب فعلاً
        $doctor = User::where('id', $validated['doctor_id'])
                ->where('role', 'doctor')
                ->first();

        if (!$doctor) {
            return response()->json(['message' => 'المعرف المختار ليس لطبيب'], 422);
        }

        // 3. التحقق من تضارب المواعيد
        $existingAppointment = Appointment::where('doctor_id', $validated['doctor_id'])
            ->where('scheduled_at', $validated['scheduled_at'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($existingAppointment) {
            return response()->json([
                'message' => 'الطبيب لديه موعد آخر في هذا الوقت، يرجى اختيار وقت مختلف.',
            ], 409);
        }

        // 4. إنشاء الموعد مباشرة (الحالة approved لأن موظف الاستقبال هو من يحجز)
        $appointment = Appointment::create([
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $validated['doctor_id'],
            'scheduled_at' => $validated['scheduled_at'],
            'type' => $validated['type'],
            'description' => $validated['description'],
            'status' => 'approved',
        ]);

        return response()->json([
            'message' => 'تم حجز الموعد بنجاح من قبل الاستقبال',
            'data' => $appointment
        ], 201);
    }
}
