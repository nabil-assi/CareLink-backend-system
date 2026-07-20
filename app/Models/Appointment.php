<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\User;
use Illuminate\Database\Eloquent\Model; // أضف هذا السطر

class Appointment extends Model
{
    use HasFactory;

  protected $fillable = [
    'doctor_id', 'patient_id', 'scheduled_at', 'duration_minutes',
    'type', 'status', 'description', 'fees', 'meeting_link', 'cancellation_reason',
    'diagnosis', 'clinical_notes', 'lab_tests', 'lab_status', 'medications', // الحقول الجديدة
];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    // هان ربطت الوصفة الطبية بجدول المواعيد بحيث كل موعد اله وصفه خاصة فيه
    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class);
    }
    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class);
    }
}
