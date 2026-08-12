<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Faq;
use App\Models\Testimonial;
use App\Models\User;

class LandingController extends Controller
{
    public function getFaqs()
    {
        return response()->json([
            'data' => Faq::where('status', 'published')->latest()->get(),
        ], 200);
    }

    public function getTestimonials()
    {
        return response()->json([
            'data' => Testimonial::where('status', 'published')->latest()->get(),
        ], 200);
    }

    public function getPublishedArticles()
    {
        try {
            $articles = Article::where('status', 'published') // أو حسب حقل النشر لديك
                ->orderBy('created_at', 'desc')
                ->take(6)
                ->get();

            return response()->json([
                'data' => $articles,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'data' => [],
            ], 200);
        }
    }

    public function getDoctors()
    {
        try {
            // كانت orWhereNotNull('specialty') بدون قوس، فبتصير OR على مستوى
            // الاستعلام كله - بترجع أي مستخدم عنده specialty (صيدلي/فني مختبر
            // إلخ) حتى لو مش دوره doctor إطلاقاً
            // كانت average_rating (اللي هي أصلاً بتستعلم مرتين جوا الـ accessor)
            // و ratings()->count() بينفذوا 3 استعلامات منفصلة *لكل طبيب* داخل
            // map() - يعني N×3 رحلة لقاعدة بيانات بعيدة (TiDB Cloud)، وهاد
            // كان يوصل لأكتر من 8-9 ثواني حتى مع طبيبين تلاتة. withAvg/withCount
            // بيحسبوا الاثنين بستعلام واحد مجمّع بدل ما نلف على كل طبيب لحاله
            $doctors = User::where('role', 'doctor')
                // status بجدول users رقم (مفعّل الحساب أو لا) مش نفسه حالة
                // اعتماد الطبيب - الفرونت بيفلتر على status نصي "active"،
                // فلازم ناخده من doctorProfile.status الحقيقي وإلا كل الأطباء
                // بينفلتروا برا الصفحة الرئيسية رغم إنه الـ API شغال
                ->with('doctorProfile:user_id,status')
                ->select('id', 'name', 'email', 'phone', 'specialty', 'profile_picture', 'status')
                // withAvg/withCount لازم يجوا بعد select() مباشرة - قبلها كانوا
                // بيلفوا ("addSelect" داخلياً)، فلو select() جاي بعدهم بيمسح
                // أعمدتهم بالكامل ويرجعوا null
                ->withAvg('ratings', 'rating')
                ->withCount('ratings')
                ->get()
                ->map(function ($doctor) {
                    $doctor->average_rating = $doctor->ratings_avg_rating
                        ? number_format($doctor->ratings_avg_rating, 1)
                        : 0;
                    $doctor->reviews_count = $doctor->ratings_count;
                    unset($doctor->ratings_avg_rating, $doctor->ratings_count);

                    return $doctor;
                });

            return response()->json([
                'data' => $doctors,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'data' => [],
            ], 500);
        }
    }

    public function showArticles($slug)
{
    $article = Article::where('slug', $slug)
        ->orWhere('id', $slug)
        ->first();

    if (!$article) {
        return response()->json([
            'message' => 'المقال غير موجود'
        ], 404);
    }

    return response()->json([
        'data' => $article
    ], 200);
}
}
