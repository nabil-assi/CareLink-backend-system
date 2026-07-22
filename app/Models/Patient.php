<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;  
use Illuminate\Database\Eloquent\Relations\HasMany;
class Patient extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'full_name',
        'phone',
        'national_id',
        'birth_date',
        'address',
    ];

    // العلاقة مع الوصي أو ولي الأمر
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'guardian_id');
    }

    // العلاقة مع التابعين (الأبناء أو التابعين لهذا المريض)
    public function dependents(): HasMany
    {
        return $this->hasMany(Patient::class, 'guardian_id');
    }

}
