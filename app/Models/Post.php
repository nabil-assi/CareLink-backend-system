<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['title', 'content', 'image_path', 'admin_id'];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
