<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Doctor;
use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function startOrGetConversation(Request $request, $doctorId)
    {
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
         $messages = Message::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['data' => $messages]);
    }

    public function sendMessage(Request $request, $conversationId)
    {
        $validated = $request->validate(['body' => 'required|string']);

        $type = $request->user() instanceof Doctor ? 'doctor' : 'patient';

        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_type' => $type,
            'body' => $validated['body'],
        ]);

        return response()->json(['message' => 'تم الإرسال', 'data' => $message], 201);
    }
}
