<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // أضف هذا السطر

class Patient extends Authenticatable
{
    use CanResetPassword, HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'date_of_birth',
        'profile_picture',
        'national_id',
        'address',
        'status',
        'gender',
    ];

    protected $hidden = [
        'password',
    ];

    // لجعل كلمة السر تُشفّر دائماً عند التخزين
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function profile()
    {
        return $this->hasOne(PatientProfile::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
