<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'غير مسجل دخول'], 401);
        }

        // لو الحساب معطل (status كاذبة)، نرجع خطأ خاص
        if (!$user->status) {
            return response()->json(['message' => 'حسابك معطل'], 403); // أو تقدر تستخدم كود ثاني مثل 422
        }

        // لو الدور مش من ضمن الأدوار المسموح بها
        if (!in_array($user->role, $roles)) {
            return response()->json(['message' => 'غير مصرح لك بالوصول لهذه الموارد'], 403);
        }

        return $next($request);
    }
}