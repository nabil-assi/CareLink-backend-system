<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'full_name',
        'phone',
        'national_id',
        'birth_date',
        'address',
    ];
}
