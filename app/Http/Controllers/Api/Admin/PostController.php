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
        return response()->json(['data' => Post::with('admin:id,name')->latest()->get()], 200);
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

        // إضافة الـ admin_id من المستخدم الحالي (الأدمن)
        $post = auth()->user()->posts()->create($validated);

        return response()->json(['message' => 'تم نشر المقال بنجاح', 'data' => $post], 201);
    }

    // حذف منشور
    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        
        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }
        
        $post->delete();

        return response()->json(['message' => 'تم حذف المنشور بنجاح']);
    }
}