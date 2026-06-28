<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Notification;
use App\Models\Patient;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Notification::latest()->paginate(20)], 200);
    }

    // إرسال إشعار للجميع
    public function sendGeneral(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        // حفظ الإشعار في قاعدة البيانات
        $notification = Notification::create([
            'type' => 'general',
            'title' => $validated['title'],
            'body' => $validated['body'],
        ]);

        // إرسال فعلي (عبر الخدمة الخاصة بك)
        NotificationService::sendToAll($validated['title'], $validated['body']);

        return response()->json(['message' => 'تم إرسال الإشعار للجميع بنجاح', 'data' => $notification], 200);
    }

    // إرسال إشعار خاص لمستخدم معين (طبيب أو مريض)
    public function sendToUser(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'user_type' => 'required|in:doctor,patient',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        // تحديد النموذج بناءً على النوع
        $model = ($validated['user_type'] === 'doctor') ? Doctor::class : Patient::class;

        $notification = Notification::create([
            'type' => 'targeted',
            'title' => $validated['title'],
            'body' => $validated['body'],
            'notifiable_id' => $validated['user_id'],
            'notifiable_type' => $model,
        ]);

        // إرسال فعلي للمستخدم المعين
        NotificationService::sendToUser($validated['user_id'], $model, $validated['title'], $validated['body']);

        return response()->json(['message' => 'تم إرسال الإشعار بنجاح']);
    }
}
