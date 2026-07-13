<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory; // أضف هذا السطر
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Doctor extends Authenticatable
{
    use CanResetPassword, HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'date_of_birth',
        'profile_picture',
        'specialty',
        'is_available',
        'address',
        'national_id',
        'years_of_experience',
        'credential_document',
        'status',
        'gender',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
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
