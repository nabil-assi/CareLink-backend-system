<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    // عرض الكوادر حسب الدور (doctor, reception, laboratory, pharmacy)
   public function index(Request $request)
{
    $role = $request->query('role', 'doctor');
    
    // جلب المستخدم مع البروفايل الخاص به حسب الدور
    $relation = match($role) {
        'doctor' => 'doctorProfile',
        'reception' => 'receptionistProfile',
        'laboratory' => 'labProfile',
        default => 'doctorProfile'
    };

    $staff = User::where('role', $role)->with($relation)->latest()->get();

    return response()->json(['data' => $staff]);
}

    // إضافة عضو جديد
   public function store(Request $request)
{
    $validated = $request->validate([
        'name'        => 'required|string|max:255',
        'email'       => 'required|email|unique:users,email',
        'password'    => 'required|string|min:6',
        'role'        => 'required|string',
        'national_id' => 'required|string|unique:users,national_id',
        'phone'       => 'nullable|string',
        'department'  => 'nullable|string',
        'specialty'   => 'nullable|string',
    ]);

    // 1. إنشاء المستخدم في جدول users
    $user = User::create([
        'name'        => $validated['name'],
        'email'       => $validated['email'],
        'password'    => Hash::make($validated['password']),
        'role'        => $validated['role'],
        'national_id' => $validated['national_id'],
        'phone'       => $validated['phone'] ?? null,
        'status'      => true,
    ]);

    // 2. حفظ القسم والتخصص في جدول البروفايل المناسب حسب الـ role
    if ($validated['role'] === 'doctor') {
        $user->doctorProfile()->create([
            'department' => $validated['department'] ?? null,
            'specialty'  => $validated['specialty'] ?? null,
        ]);
    } elseif ($validated['role'] === 'reception') {
        $user->receptionistProfile()->create([
            'department' => $validated['department'] ?? null,
        ]);
    } 
    // وبنفس الإيقاع لأي دور آخر إذا لزم الأمر

    return response()->json(['message' => 'تمت الإضافة بنجاح', 'data' => $user->load('doctorProfile')], 201);
}
    // تحديث بيانات العضو
  public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    $validated = $request->validate([
        'name'        => 'required|string|max:255',
        'email'       => 'required|email|unique:users,email,' . $user->id,
        'national_id' => 'required|string|unique:users,national_id,' . $user->id,
        'phone'       => 'nullable|string',
        'department'  => 'nullable|string',
        'specialty'   => 'nullable|string',
    ]);

    // 1. تحديث بيانات جدول users
    $user->update([
        'name'        => $validated['name'],
        'email'       => $validated['email'],
        'national_id' => $validated['national_id'],
        'phone'       => $validated['phone'] ?? null,
    ]);

    // 2. تحديث أو إنشـاء البروفايل المرتبط
    if ($user->role === 'doctor') {
        $user->doctorProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'department' => $validated['department'] ?? null,
                'specialty'  => $validated['specialty'] ?? null,
            ]
        );
    } elseif ($user->role === 'reception') {
        $user->receptionistProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'department' => $validated['department'] ?? null,
            ]
        );
    }

    return response()->json(['message' => 'تم التحديث بنجاح', 'data' => $user]);
}
    // تغيير حالة العضو (تفعيل / إيقاف)
    public function updateStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'status' => 'required|boolean', // استقبال قيمّة boolean (true أو false)
        ]);

        $user->update(['status' => $request->status]);

        return response()->json(['message' => 'تم تغيير الحالة بنجاح']);
    }

    // حذف العضو
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'تم الحذف بنجاح']);
    }
}
