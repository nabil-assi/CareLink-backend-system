<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    protected $fillable = ['appointment_id', 'diagnosis', 'notes', 'status',
        'dispensed_at'];

    // الوصفة تتبع موعد واحد
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    // الوصفة الواحدة تحتوي على عدة أدوية
    public function medicines(): HasMany
    {
        return $this->hasMany(PrescriptionMedicine::class);
    }
}
