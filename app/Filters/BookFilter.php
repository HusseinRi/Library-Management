<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class BookFilter extends AbstractFilter
{

    public function search(string $value): Builder
    {
        // نستخدم مجموعة شروط مغلقة داخل where لضمان عدم تداخل شروط الـ OR مع الفلاتر الأخرى
        return $this->builder->where(function (Builder $query) use ($value) {
            $query->where('title', 'LIKE', "%{$value}%")
                ->orWhere('isbn', 'LIKE', "%{$value}%")
                // البحث في أسماء المؤلفين عبر العلاقة Many-to-Many (جدول book_authors في مشروعك)
                ->orWhereHas('authors', function (Builder $authorQuery) use ($value) {
                    $authorQuery->where('name', 'LIKE', "%{$value}%");
                });
        });
    }

    /**
     * متطلب التصفية (UC-006): الفلترة حسب التصنيف (Category).
     * يدعم الفلترة سواء أرسل الـ Frontend معرف التصنيف (ID) أو اسمه (Name).
     */
    public function category(string $value): Builder
    {

        return $this->builder->whereHas('categories', function (Builder $query) use ($value) {
            if (is_numeric($value)) {
                $query->where('categories.id', $value);
            } else {
                $query->where('name', 'LIKE', "%{$value}%");
            }
        });
    }

    /**
     * متطلب التصفية (UC-006): الفلترة حسب المؤلف (Author).
     * يدعم الفلترة بمعرف المؤلف أو اسمه مباشرة.
     */
    public function author(string $value): Builder
    {
        return $this->builder->whereHas('authors', function (Builder $query) use ($value) {
            if (is_numeric($value)) {
                $query->where('authors.id', $value);
            } else {
                $query->where('name', 'LIKE', "%{$value}%");
            }
        });
    }

    /**
     * متطلب التصفية (UC-006): الحد الأدنى للسعر.
     * تم تحويل الاسم ديناميكياً من price_from في الرابط إلى priceFrom هنا.
     */
    public function priceFrom(mixed $value): Builder
    {
        return $this->builder->where('price', '>=', (float) $value);
    }

    /**
     * متطلب التصفية (UC-006): الحد الأقصى للسعر.
     * تم تحويل الاسم ديناميكياً من price_to في الرابط إلى priceTo هنا.
     */
    public function priceTo(mixed $value): Builder
    {
        return $this->builder->where('price', '<=', (float) $value);
    }

    /**
     * متطلب التصفية (UC-006): الفلترة حسب التقييم (Rating).
     * يجلب الكتب التي يبلغ متوسط تقييماتها أكبر من أو يساوي الرقم المطلوب بكفاءة عالية.
     */
    public function rating(mixed $value): Builder
    {
        return $this->builder->whereHas('ratings', function (Builder $query) use ($value) {
            $query->select('book_id')
                ->groupBy('book_id')
                ->havingRaw('AVG(rating) >= ?', [(float) $value]);
        });
    }
}