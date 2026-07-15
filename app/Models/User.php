<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory; // 1. تأكد من هذا الـ use
class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $fillable = ['name', 'email', 'password', 'role', 'phone'];

    protected $hidden = ['password', 'remember_token'];

    // العلاقات
    public function doctorProfile()
    {
        return $this->hasOne(DoctorProfile::class);
    }

    public function patientProfile()
    {
        return $this->hasOne(PatientProfile::class);
    }

    public function receptionistProfile()
    {
        return $this->hasOne(ReceptionistProfile::class);
    }

    public function labProfile()
    {
        return $this->hasOne(LabProfile::class);
    }
    public function posts() {
    return $this->hasMany(Post::class);
}
}
