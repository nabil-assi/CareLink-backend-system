<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    // إرجاع قائمة العروض والباقات الطبية
    public function index()
    {
        $offers = [
            [
                'id' => 1,
                'title' => 'باقة الطب العام',
                'description' => 'كشف طبي عام، قياس العلامات الحيوية، وخطة متابعة أولية مع طبيب الأسرة.',
                'price' => '149',
                'oldPrice' => '220',
                'currency' => '₪',
                'badge' => null,
                'image' => '/images/carelink-offer-family.png',
            ],
            [
                'id' => 2,
                'title' => 'الفحص العائلي السنوي',
                'description' => 'تغطية حتى 4 أفراد مع تحاليل أساسية ومتابعة طب عام.',
                'price' => '899',
                'oldPrice' => '1200',
                'currency' => '₪',
                'badge' => null,
                'image' => '/images/carelink-offer-family.png',
            ],
            [
                'id' => 3,
                'title' => 'باقة القلب السليم',
                'description' => 'تخطيط قلب، فحوصات أولية، واستشارة اختصاصي أمراض القلب.',
                'price' => '599',
                'oldPrice' => '850',
                'currency' => '₪',
                'badge' => 'الأكثر طلباً',
                'image' => '/images/carelink-offer-heart.png',
            ],
            [
                'id' => 4,
                'title' => 'باقة التحاليل الشاملة',
                'description' => 'ملف دم كامل، سكر، دهون، وفيتامينات مع تقرير رقمي واضح.',
                'price' => '279',
                'oldPrice' => '420',
                'currency' => '₪',
                'badge' => 'وفر 33%',
                'image' => '/images/carelink-offer-labs.png',
            ],
            [
                'id' => 5,
                'title' => 'متابعة الضغط والسكر',
                'description' => 'قياسات دورية، مراجعة أدوية، ومتابعة الأمراض المزمنة مع طبيب عام.',
                'price' => '199',
                'oldPrice' => '300',
                'currency' => '₪',
                'badge' => null,
                'image' => '/images/carelink-offers-hero.png',
            ],
            [
                'id' => 6,
                'title' => 'فحص القلب المتقدم',
                'description' => 'ECG، تقييم عوامل الخطر القلبية، وتوصيات وقاية مخصصة.',
                'price' => '449',
                'oldPrice' => '650',
                'currency' => '₪',
                'badge' => 'جديد',
                'image' => '/images/carelink-offer-heart.png',
            ],
        ];

        return response()->json([
            'data' => $offers,
        ], 200);
    }

    // استقبال اشتراك النشرة البريدية
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        return response()->json([
            'message' => 'تم الاشتراك بنجاح في النشرة البريدية.',
        ], 200);
    }
}
