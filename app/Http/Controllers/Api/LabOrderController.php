<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LabOrderController extends Controller
{
    // جلب جميع طلبات التحاليل للمختبر
    public function index()
    {
        $orders = LabOrder::with(['patient', 'doctor'])->latest()->get()->map(function ($order) {
            return [
                'id' => $order->id,
                'patient' => $order->patient->name ?? 'مريض غير معروف',
                'patientAge' => $order->patient->birth_date ? Carbon::parse($order->patient->birth_date)->age : null,
                'patientGender' => $order->patient->gender ?? null,
                'doctor' => $order->doctor->name ?? 'طبيب غير معروف',
                'tests' => $order->tests,
                'sampleId' => $order->sample_id,
                'clinicalReason' => $order->clinical_reason,
                'notes' => $order->notes,
                'priority' => $order->priority,
                'status' => $order->status,
                'resultText' => $order->result_text,
                'completedBy' => $order->completed_by,
                'completedAt' => $order->completed_at,
                'createdAt' => $order->created_at,
            ];
        });

        return response()->json(['status' => true, 'data' => $orders], 200);
    }

    // بدء التنفيذ (تغيير الحالة إلى in_progress)
    public function start($id, Request $request)
    {
        $order = LabOrder::findOrFail($id);
        $order->update(['status' => 'in_progress']);

        return response()->json(['message' => 'تم بدء التنفيذ بنجاح', 'data' => $order]);
    }

    // إرسال النتيجة واكتمال الطلب (completed)
    public function complete($id, Request $request)
    {
        $request->validate([
            'result_text' => 'required|string',
            'completed_by' => 'nullable|string',
        ]);

        $order = LabOrder::findOrFail($id);
        $order->update([
            'status' => 'completed',
            'result_text' => $request->result_text,
            'completed_by' => $request->completed_by ?? 'فني مختبر',
            'completed_at' => now(),
        ]);

        return response()->json(['message' => 'تم إرسال النتيجة بنجاح', 'data' => $order]);
    }

    // إعادة العينة أو رفضها (rejected أو redo)
    public function redo($id, Request $request)
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        $order = LabOrder::findOrFail($id);
        $order->update([
            'status' => 'rejected',
            'notes' => $request->reason, // أو حفظ سبب الرفض
        ]);

        return response()->json(['message' => 'تم توثيق رفض/إعادة العينة بنجاح', 'data' => $order]);
    }
}