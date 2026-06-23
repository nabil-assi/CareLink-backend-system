<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // ضروري جداً للـ API

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * الحقول التي يُسمح بتعبئتها (Mass Assignment)
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * إخفاء الحقول الحساسة عند إرجاع البيانات للـ API
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
}