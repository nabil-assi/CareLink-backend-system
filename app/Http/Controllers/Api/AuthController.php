<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. التحقق من صحة البيانات المرسلة
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. البحث عن المستخدم
        $user = User::where('email', $request->email)->first();

        // 3. التحقق من كلمة السر
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }

        // 4. إنشاء الـ Token 
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }
}