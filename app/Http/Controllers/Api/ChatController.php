<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function startOrGetConversation(Request $request, $doctorId)
    {
        // التحقق من أن الطبيب المطلوب هو بالفعل مستخدم لديه دور 'doctor'
        $doctorExists = User::where('id', $doctorId)->where('role', 'doctor')->exists();
        if (!$doctorExists) {
            return response()->json(['message' => 'الطبيب غير موجود'], 404);
        }

        $conversation = Conversation::where('patient_id', $request->user()->id)
            ->where('doctor_id', $doctorId)
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'patient_id' => $request->user()->id,
                'doctor_id' => $doctorId,
            ]);
        }

        return response()->json(['data' => $conversation]);
    }

    public function getMessages($conversationId)
    {
        // تأكد من أن المستخدم الحالي هو طرف في المحادثة
        $messages = Message::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['data' => $messages]);
    }

    public function sendMessage(Request $request, $conversationId)
    {
        $validated = $request->validate(['body' => 'required|string']);

        // التحقق من نوع المرسل بناءً على الـ role وليس الموديل
        $type = $request->user()->role === 'doctor' ? 'doctor' : 'patient';

        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => $request->user()->id, // إضافة الـ sender_id لتوثيق المرسل
            'sender_type' => $type,
            'body' => $validated['body'],
        ]);

        return response()->json(['message' => 'تم الإرسال', 'data' => $message], 201);
    }
}