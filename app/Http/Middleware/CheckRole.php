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
        if (! ($user && $user->status && in_array($user->role, $roles))) {
            return response()->json(['message' => 'غير مصرح لك بالوصول لهذه الموارد'], 403);
        }

        // must_change_password كان يتحط true وقت إنشاء حساب الموظف (كلمة مرور
        // مؤقتة من الأدمن)، بس محدا كان يتحقق منه فعلياً - الفرونت بس كان يوجه
        // لصفحة تغيير كلمة المرور بناءً على الاستجابة، بس ما كان في شي يمنع
        // الموظف يستمر يستخدم كل صفحات النظام بكلمة المرور المؤقتة للأبد لو
        // تجاهل التوجيه (change-password/logout مش تحت checkRole أصلاً فمش
        // متأثرين بهاد الشرط)
        if ($user->must_change_password) {
            return response()->json(['message' => 'يجب تغيير كلمة المرور المؤقتة أولاً'], 403);
        }

        return $next($request);
    }
}