<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // أضف هذا السطر
class Conversation extends Model
{    use HasFactory;

   public function messages() {
    return $this->hasMany(Message::class);
}
}
