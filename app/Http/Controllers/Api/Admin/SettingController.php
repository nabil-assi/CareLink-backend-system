<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
 class SettingController extends Controller {
    public function index() {
        // نرجع الإعدادات كـ Object واحد سهل للقراءة
        return response()->json(Setting::pluck('value', 'key'));
    }

    public function update(Request $request) {
        // حفظ أو تحديث كل الإعدادات المرسلة
        foreach ($request->all() as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        return response()->json(['message' => 'تم الحفظ']);
    }
}