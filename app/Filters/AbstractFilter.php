<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class AbstractFilter
{
    // لاستقبال بيانات الطلب القادم من العميل
    protected Request $request;

    // لاستقبال باني الاستعلام الخاص بـ Eloquent لتركيب الشروط عليه
    protected Builder $builder;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * تطبيق الفلاتر ديناميكياً على الـ Builder.
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        // نمر على كل المدخلات القادمة في الـ URL (مثل: ?search=clean&rating=4)
        foreach ($this->request->all() as $name => $value) {

            // تحويل اسم المعامل من snake_case إلى camelCase 
            // مثال: price_from تصبح priceFrom لكي تطابق اسم الدالة التي سنكتبها لاحقاً
            $method = Str::camel($name);

            // نتحقق: هل توجد دالة بهذا الاسم داخل كلاس الفلترة الفعلي؟ وهل القيمة المرسلة ليست فارغة؟
            if (method_exists($this, $method) && !raw_value_empty($value)) {
                // استدعاء الدالة ديناميكياً وتمرير القيمة لها لتركيب شرط الـ SQL
                $this->$method($value);
            }
        }

        return $this->builder;
    }
}

/**
 * دالة مساعدة خارج الكلاس للتحقق بدقة من أن القيمة ليست فارغة.
 * تفيدنا لتجنب اعتبار الرقم (0) كقيمة فارغة، لأن empty(0) في الـ PHP تعطي true وهذا يسبب مشاكل بالأسعار أو التقييمات.
 */
if (!function_exists('raw_value_empty')) {
    function raw_value_empty($value): bool
    {
        return $value === '' || $value === null || (is_array($value) && count($value) === 0);
    }
}