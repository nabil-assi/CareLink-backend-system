<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PostController extends Controller
{
    // داخل AdController
public function store(Request $request)
{
    $data = $request->validate([
        'title' => 'required|string',
        'image' => 'required|image', // تأكد من إعداد الـ storage
        'link' => 'nullable|url',
    ]);

    // رفع الصورة وحفظ الإعلان
    $path = $request->file('image')->store('ads', 'public');
    $data['image_path'] = $path;
    
    \App\Models\Ad::create($data);

    return response()->json(['message' => 'تم إضافة الإعلان بنجاح'], 201);
}
}
