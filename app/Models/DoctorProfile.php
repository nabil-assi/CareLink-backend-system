<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model {
    protected $fillable = ['user_id', 'specialty', 'clinic_address', 'years_of_experience', 'status'];
    public function user() { return $this->belongsTo(User::class); }
}