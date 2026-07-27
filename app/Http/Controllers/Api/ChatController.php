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

    $validated = $request->validate([
        'body' => 'nullable|string|max:2000',
        'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi|max:20480', // 20MB
    ]);

    // لازم يكون في نص أو مرفق، مش الاثنين فاضيين
    if (empty($validated['body']) && ! $request->hasFile('attachment')) {
        return response()->json(['message' => 'لازم تكتب رسالة أو ترفق ملف'], 422);
    }

    $attachmentPath = null;
    $attachmentType = null;

    if ($request->hasFile('attachment')) {
        $file = $request->file('attachment');
        $attachmentPath = $file->store('chat-attachments', 'public');
        $attachmentType = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image';
    }

    $type = $request->user()->role === 'doctor' ? 'doctor' : 'patient';

    $message = $conversation->messages()->create([
        'sender_id' => $userId,
        'sender_type' => $type,
        'body' => $validated['body'] ?? null,
        'attachment_path' => $attachmentPath,
        'attachment_type' => $attachmentType,
    ]);

    return response()->json(['message' => 'تم الإرسال', 'data' => $message], 201);
}
}
