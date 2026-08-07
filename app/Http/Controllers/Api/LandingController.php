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
            $doctors = User::where('role', 'doctor')
                ->orWhereNotNull('specialty')
                ->select('id', 'name', 'email', 'phone', 'specialty', 'profile_picture', 'status')
                ->get()
                // average_rating accessor موجود بالموديل بس مش appended تلقائياً
                // بالـ JSON، فبنضيفه يدوياً هون عشان يوصل للفرونت
                ->map(function ($doctor) {
                    $doctor->average_rating = $doctor->average_rating;
                    $doctor->reviews_count = $doctor->ratings()->count();

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
