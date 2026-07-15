<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. إنشاء الأدمن
        User::factory()->admin()->create();

        $doctors = User::factory()->doctor()->count(5)->create();
        $patients = User::factory()->count(10)->create();
        // 2. إنشاء الأطباء والمرضى
        $doctors->each(fn ($d) => $d->doctorProfile()->create([
            'status' => 'active',
            'specialty' => 'Cardiology',
            'clinic_address' => 'Gaza, Main Street',
            'date_of_birth' => '1990-01-01', // أضف هذا الحقل المفقود
            'gender' => 'male',   
            // وأضف هذا الحقل أيضاً
        ]));

        $patients->each(fn ($p) => $p->patientProfile()->create([
            'blood_type' => 'O+',
            // أضف أي حقول أخرى إجبارية في جدول patient_profiles هنا
        ]));
        // 3. إنشاء البروفايلات المرتبطة
        $doctors->each(fn ($d) => $d->doctorProfile()->create(['status' => 'active', 'specialty' => 'Cardiology']));
        $patients->each(fn ($p) => $p->patientProfile()->create(['blood_type' => 'O+']));

        // 4. المواعيد والسجلات (استخدم user_id الآن)
        foreach ($patients as $patient) {
            $appointment = Appointment::create([
                'doctor_id' => $doctors->random()->id,
                'patient_id' => $patient->id,
                'scheduled_at' => now()->addDays(rand(1, 10)),
                'status' => 'pending',
            ]);
        }
    }
}
