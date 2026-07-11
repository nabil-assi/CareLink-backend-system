<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // أضف هذا السطر
class PatientProfile extends Model
{    use HasFactory;

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    protected $fillable = [
        'patient_id', 'blood_type', 'weight_kg', 'height_cm',
        'is_diabetic', 'is_hypertensive', 'is_smoker', 'allergies',
        'chronic_diseases', 'current_medications',
        'emergency_contact_name', 'emergency_contact_phone',
    ];
}
 