<?php

namespace Database\Seeders;

use App\Models\{Admin, Doctor, Patient, Appointment, PatientProfile, Conversation, Message, MedicalRecord};
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. الأدمن
        Admin::create(['name' => 'Super Admin', 'email' => 'admin@carelink.com', 'password' => bcrypt('password123')]);

        // 2. الأطباء والمرضى
        $doctors = Doctor::factory()->count(5)->create();
        $patients = Patient::factory()->count(10)->create()->each(function ($patient) {
            PatientProfile::create([
                'patient_id' => $patient->id,
                'blood_type' => fake()->randomElement(['A+', 'O+', 'B+', 'AB+']),
            ]);
        });

        // 3. المواعيد
        foreach (range(1, 15) as $i) {
            $appointment = Appointment::create([
                'doctor_id' => $doctors->random()->id,
                'patient_id' => $patients->random()->id,
                'scheduled_at' => fake()->dateTimeBetween('now', '+1 month'),
                'type' => 'in_person',
                'status' => 'pending',
            ]);

            // 4. السجلات الطبية (بناءً على مواعيد مكتملة عشوائياً)
            if ($i % 2 == 0) {
                MedicalRecord::create([
                    'patient_id' => $appointment->patient_id,
                    'doctor_id' => $appointment->doctor_id,
                    'appointment_id' => $appointment->id,
                    'record_type' => 'diagnosis',
                    'diagnosis' => 'تشخيص مبدئي: حالة مستقرة',
                ]);
            }
        }

        // 5. المحادثات والرسائل
        foreach ($doctors as $doctor) {
            $conversation = Conversation::create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patients->random()->id,
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'patient',
                'body' => 'مرحباً دكتور، هل يمكنني الاستفسار عن التحاليل؟',
            ]);
        }
    }
}