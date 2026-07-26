<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id', 'patient_id', 'patient_type', 'scheduled_at', 'duration_minutes',
        'type', 'status', 'description', 'fees', 'meeting_link', 'cancellation_reason',
        'diagnosis', 'clinical_notes', 'lab_tests', 'lab_status', 'medications',
    ];

    public function patient()
    {
        return $this->morphTo();
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class);
    }

    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function ratings()
    {
        return $this->hasMany(DoctorRating::class, 'doctor_id');
    }

    public function getAverageRatingAttribute()
    {
        return $this->ratings()->avg('rating') ? number_format($this->ratings()->avg('rating'), 1) : 0;
    }
}