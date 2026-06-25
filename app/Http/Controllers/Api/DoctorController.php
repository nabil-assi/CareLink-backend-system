<?php

 namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Doctor; 
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * عرض جميع الأطباء للوحة تحكم الأدمن
     */
    public function index()
    {
        // جلب الأطباء مع الترتيب حسب الأحدث
        $doctors = Doctor::latest()->get();

        return response()->json([
            'status' => true,
            'data' => $doctors
        ], 200);
    }

    

}