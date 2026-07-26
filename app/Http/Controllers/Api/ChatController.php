<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Conversation;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    // فتح أو رجوع المحادثة الخاصة بموعد معين
    public function startOrGetConversation(Request $request, $appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $userId = $request->user()->id;

        dd([
            'logged_in_user_id' => $userId,
            'appointment_doctor_id' => $appointment->doctor_id,
            'appointment_patient_id' => $appointment->patient_id,
        ]);

        // تأكد إن المستخدم الحالي هو فعلاً طرف بهاي الموعد
        if ($appointment->doctor_id !== $userId && $appointment->patient_id !== $userId) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $conversation = Conversation::firstOrCreate(
            ['appointment_id' => $appointment->id],
            ['doctor_id' => $appointment->doctor_id, 'patient_id' => $appointment->patient_id]
        );

        return response()->json(['data' => $conversation->load('appointment')]);
    }

    public function getMessages(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        if (! $conversation->hasParticipant($request->user()->id)) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $messages = $conversation->messages()->orderBy('created_at', 'asc')->get();

        return response()->json(['data' => $messages, 'locked' => $conversation->isLocked()]);
    }

    public function sendMessage(Request $request, $conversationId)
    {
        $conversation = Conversation::with('appointment')->findOrFail($conversationId);
        $userId = $request->user()->id;

        if (! $conversation->hasParticipant($userId)) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        if ($conversation->isLocked()) {
            return response()->json(['message' => 'الموعد منتهي، ما بتقدر تبعت رسائل'], 403);
        }

        $validated = $request->validate(['body' => 'required|string|max:2000']);

        $type = $request->user()->role === 'doctor' ? 'doctor' : 'patient';

        $message = $conversation->messages()->create([
            'sender_id' => $userId,
            'sender_type' => $type,
            'body' => $validated['body'],
        ]);

        return response()->json(['message' => 'تم الإرسال', 'data' => $message], 201);
    }
}
