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
        // نتحقق من وجود مستخدم وأن دوره ضمن الأدوار المسموح بها
        if ($request->user() && in_array($request->user()->role, $roles)) {
            return $next($request);
        }

        return response()->json(['message' => 'غير مصرح لك بالوصول لهذه الموارد'], 403);
    }
}