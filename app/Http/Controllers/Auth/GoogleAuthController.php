<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    // بيوجه المستخدم لصفحة تسجيل دخول Google
    public function redirectToGoogle()
    {
        $driver = Socialite::driver('google')->stateless();

        // مؤقتًا فقط - لرؤية الرابط الفعلي
        // dd($driver->redirectUrl); // أو

        return $driver->redirect();
    }

    // Google بترجع هون بعد الموافقة
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect(env('FRONTEND_URL').'/login?error=google_auth_failed');
        }

        // دوّر على المستخدم أو أنشئ وحدة جديدة
        $user = User::where('email', $googleUser->getEmail())->first();

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'password' => bcrypt(Str::random(24)), // كلمة سر عشوائية لأنه بيسجل دخول بـ Google بس
                'email_verified_at' => now(),
            ]);
        } else {
            // حدّث الـ google_id إذا ناقص
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
        }

        // أنشئ توكن (باستخدام Sanctum مثلاً)
        $token = $user->createToken('auth_token')->plainTextToken;

        // رجّع المستخدم للفرونت مع التوكن (كـ query param أو redirect)
        return redirect(env('FRONTEND_URL').'/auth/callback?token='.$token);
    }
}
