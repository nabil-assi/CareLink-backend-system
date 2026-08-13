<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  string ...$roles (يمكنك تمرير دور واحد أو أكثر)
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        // تحقق إذا المستخدم مسجل دخول، حالته نشطة (true أو 1)، ودوره ضمن الأدوار المسموح بها
        if ($user && $user->status && in_array($user->role, $roles)) {
            return $next($request);
        }

        return response()->json(['message' => 'غير مصرح لك بالوصول لهذه الموارد'], 403);
    }
}