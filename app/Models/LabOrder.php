<?php

 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'tests',
        'sample_id',
        'clinical_reason',
        'notes',
        'priority',
        'status',
        'result_text',
        'completed_by',
        'completed_at',
    ];

    // علاقة المريض
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    // علاقة الطبيب
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}