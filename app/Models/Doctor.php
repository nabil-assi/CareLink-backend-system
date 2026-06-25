<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Passwords\CanResetPassword; 
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract; 


class Doctor extends Authenticatable
{
    use HasApiTokens, Notifiable, CanResetPassword;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'date_of_birth',
        'profile_picture',
        'specialty',
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
}