<?php

namespace App\Http\Controllers;

use App\Filters\BookFilter;
use App\Http\Requests\BookSearchRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookSearchController extends Controller
{
    /**
     * جلب وتنقية الكتب بناءً على مدخلات البحث والتصفية.
     */
    public function index(BookSearchRequest $request, BookFilter $filter): AnonymousResourceCollection
    {
        // 1. تحديد عدد الكتب في الصفحة الواحدة ديناميكياً (الافتراضي 15 كتاب)
        $perPage = $request->input('per_page', 15);

        // 2. بناء الاستعلام الاحترافي فائق الأداء
        $books = Book::query()
            // منع مشكلة N+1 عبر تحميل العلاقات مسبقاً (طابقنا الأسماء مع دالاتك بالجمع categories و authors)
            ->with(['categories', 'authors'])

            // تحسين أداء: حساب متوسط التقييم لجدول التقييمات عبر الـ Database مباشرة بدلاً من الـ PHP
            //  ->withAvg('ratings', 'rating')

            // استدعاء الـ Local Scope وتمرير كلاس الفلترة الذكي له
            ->filter($filter)

            // تطبيق التبويب (Pagination)
            ->paginate($perPage)

            // الحفاظ على الفلاتر في روابط التنقل (التالي والسابق) داخل الـ Frontend
            ->withQueryString();

        // 3. إرسال النتائج المفلترة إلى الـ BookResource الخاص بك لتنسيقها وإرجاعها
        return BookResource::collection($books);
    }
}