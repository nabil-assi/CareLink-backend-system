<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Prescription;
use Illuminate\Http\Request;

class PharmacyController extends Controller
{
    public function homeStats()
    {
        // جلب جميع الوصفات مع بيانات الموعد والمريض والطبيب والأدوية
        $prescriptions = Prescription::with(['appointment.patient', 'appointment.doctor', 'medicines'])
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedPrescriptions = $prescriptions->map(function ($rx) {
            $patient = $rx->appointment->patient ?? null;
            $doctor = $rx->appointment->doctor ?? null;

            $medicationsList = $rx->medicines->map(function ($med) {
                return "{$med->medicine_name} - الجرعة: {$med->dosage} - المدة: {$med->duration}";
            })->implode("\n");

            return [
                'id' => $rx->id,
                'patient' => $patient->full_name ?? $patient->name ?? 'مريض',
                'phone' => $patient->phone ?? '',
                'nationalId' => $patient->national_id ?? '',
                'doctor' => $doctor->name ?? 'طبيب',
                'medications' => $medicationsList ?: $rx->notes,
                'status' => $rx->status ?? 'pending',
                'createdAt' => $rx->created_at,
            ];
        });

        // حساب الأصناف التي تجاوزت الحد الأدنى في المخزون
        $lowStock = Inventory::whereColumn('quantity', '<=', 'min_quantity')->get();

        $total = $formattedPrescriptions->count();
        $dispensed = $formattedPrescriptions->where('status', 'dispensed')->count();
        $pending = $total - $dispensed;
        $latestPending = $formattedPrescriptions->where('status', '!=', 'dispensed')->take(5)->values();

        return response()->json([
            'data' => [
                'stats' => [
                    'total' => $total,
                    'pending' => $pending,
                    'dispensed' => $dispensed,
                    'latestPending' => $latestPending,
                ],
                'lowStock' => $lowStock,
            ],
        ], 200);
    }

    public function index()
    {
        $prescriptions = Prescription::with(['appointment.patient', 'appointment.doctor', 'medicines'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($rx) {
                $patient = $rx->appointment->patient ?? null;
                $doctor = $rx->appointment->doctor ?? null;

                $medicationsList = $rx->medicines->map(function ($med) {
                    return "{$med->medicine_name} - الجرعة: {$med->dosage} - المدة: {$med->duration}";
                })->implode("\n");

                return [
                    'id' => $rx->id,
                    'patientId' => $patient->id ?? null,
                    'doctorId' => $doctor->id ?? null,
                    'patient' => $patient->full_name ?? $patient->name ?? 'مريض غير معروف',
                    'doctor' => $doctor->name ?? 'طبيب',
                    'phone' => $patient->phone ?? '',
                    'nationalId' => $patient->national_id ?? '',
                    'medications' => $medicationsList ?: $rx->notes,
                    'status' => $rx->status ?? 'pending', // سيقرأ الحالة الحقيقية من قاعدة البيانات
                    'createdAt' => $rx->created_at,
                    'dispensedAt' => $rx->dispensed_at,
                ];
            });

        return response()->json(['data' => $prescriptions], 200);
    }

    public function markReady($id)
    {
        $rx = Prescription::findOrFail($id);
        $rx->update(['status' => 'ready']); // سيحفظ الحالة بنجاح في قاعدة البيانات

        return response()->json(['message' => 'تم تحديث حالة الوصفة إلى جاهز', 'data' => $rx], 200);
    }

    public function dispense(Request $request, $id)
    {
        $rx = Prescription::findOrFail($id);
        $rx->update([
            'status' => 'dispensed',
            'dispensed_at' => now(), 
        ]);

        return response()->json(['message' => 'تم صرف الدواء بنجاح', 'data' => $rx], 200);
    }

    public function getInventory()
    {
        $items = Inventory::orderBy('name', 'asc')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'minQuantity' => $item->min_quantity,
                'unit' => $item->unit,
                'keywords' => $item->keywords ? explode('،', $item->keywords) : [],
            ];
        });

        return response()->json(['data' => $items], 200);
    }

    public function storeInventory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'minQuantity' => 'required|integer|min:0',
            'unit' => 'required|string',
            'keywords' => 'nullable|string',
        ]);

        $item = Inventory::create([
            'name' => $validated['name'],
            'quantity' => $validated['quantity'],
            'min_quantity' => $validated['minQuantity'],
            'unit' => $validated['unit'],
            'keywords' => $validated['keywords'],
        ]);

        return response()->json(['message' => 'تم الحفظ بنجاح', 'data' => $item], 201);
    }

    public function updateInventory(Request $request, $id)
    {
        $item = Inventory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'minQuantity' => 'required|integer|min:0',
            'unit' => 'required|string',
            'keywords' => 'nullable|string',
        ]);

        $item->update([
            'name' => $validated['name'],
            'quantity' => $validated['quantity'],
            'min_quantity' => $validated['minQuantity'],
            'unit' => $validated['unit'],
            'keywords' => $validated['keywords'],
        ]);

        return response()->json(['message' => 'تم التحديث بنجاح', 'data' => $item], 200);
    }

    public function adjustQuantity(Request $request, $id)
    {
        $item = Inventory::findOrFail($id);
        $delta = $request->input('delta', 0);

        $newQuantity = max(0, $item->quantity + $delta);
        $item->update(['quantity' => $newQuantity]);

        return response()->json(['message' => 'تم تعديل الكمية', 'data' => $item], 200);
    }
}
