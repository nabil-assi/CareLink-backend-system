<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StaffAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
            'role'     => 'required|in:reception,laboratory,pharmacy',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['البريد الإلكتروني أو كلمة المرور غير صحيحة.'],
            ]);
        }

        if ($user->role !== $request->role) {
            throw ValidationException::withMessages([
                'email' => ['ليس لديك صلاحية الدخول من هذه البوابّة.'],
            ]);
        }

        if (! $user->status) {
            return response()->json(['message' => 'هذا الحساب موقوف من قبل الإدارة.'], 403);
        }

        $token = $user->createToken('staff-token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'token'   => $token,
            'user'    => $user,
        ]);
    }
}