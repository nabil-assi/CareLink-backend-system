<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification; // افترض وجود الموديل

class NotificationController extends Controller
{
    // إرسال إشعار عام
    public function sendGeneralNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        // هنا ستستخدم الـ NotificationService الذي تملكه
        // NotificationService::sendToAll(...);

        return response()->json(['message' => 'تم إرسال الإشعار بنجاح']);
    }
}