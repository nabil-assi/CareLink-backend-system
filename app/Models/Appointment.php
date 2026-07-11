<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // أضف هذا السطر
class Appointment extends Model
{    use HasFactory;

    protected $fillable = [
        'doctor_id', 'patient_id', 'scheduled_at', 'duration_minutes',
        'type', 'status', 'description', 'fees', 'meeting_link', 'cancellation_reason',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
