<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // أضف هذا السطر
class Post extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'content', 'image_path', 'admin_id'];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
