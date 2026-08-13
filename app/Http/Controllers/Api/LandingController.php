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
           
            // status هون (users.status) هو علم الإيقاف من الإدارة - منفصل عن
            // doctorProfile.status (حالة الموافقة). الفرونت كان بيفلتر بس على
            // doctorProfile.status، فطبيب موقوف (status=false) بس معتمد أصلاً
            // كان يضل ظاهر وقابل للحجز بالصفحة الرئيسية العامة حتى بعد إيقافه
            $doctors = User::where('role', 'doctor')
                ->where('status', true)
                ->with('doctorProfile:user_id,status')
                ->select('id', 'name', 'email', 'phone', 'specialty', 'profile_picture', 'status')
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
