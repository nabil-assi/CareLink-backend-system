<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    // عرض جميع المنشورات
    public function index()
    {
        // تأكد من أن العلاقة في موديل Post اسمها user وليس admin
        return response()->json([
            'status' => true, 
            'data' => Post::with('user:id,name')->latest()->get()
        ], 200);
    }

    // إضافة منشور جديد
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('posts', 'public');
        }

        // استخدام العلاقة الموحدة (يجب أن يكون لديك علاقة posts في موديل User)
        $post = $request->user()->posts()->create($validated);

        return response()->json([
            'status' => true,
            'message' => 'تم نشر المقال بنجاح', 
            'data' => $post
        ], 201);
    }

    // حذف منشور
    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        
        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }
        
        $post->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف المنشور بنجاح'
        ]);
    }
}