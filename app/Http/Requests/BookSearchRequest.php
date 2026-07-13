<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // نص البحث (UC-005) يجب أن يكون نصاً ولا يتجاوز 255 حرفاً لحماية الأداء
            'search' => ['nullable', 'string', 'max:255'],

            // فلاتر التصفية (UC-006)
            // التصنيف والمؤلف يمكن أن يكونا نصاً (اسم) أو رقماً (ID) بناءً على تصميم الفلتر المشنأ سابقاً
            'category' => ['nullable', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],

            // التقييم يجب أن يكون رقماً بين 0 و 5 حصراً
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],

            // السعر الأدنى يجب ألا يكون سالباً
            'price_from' => ['nullable', 'numeric', 'min:0'],

            // السعر الأقصى يجب أن يكون رقماً، وأكبر من أو يساوي السعر الأدنى الممرر (gte: Greater Than or Equal)
            'price_to' => [
                'nullable',
                'numeric',
                'min:0',
                // يطبق الشرط فقط في حال كان price_from متواجداً بالطلب
                $this->has('price_from') ? 'gte:price_from' : '',
            ],

            // [Enhancement للـ Pagination] للتحكم بحجم الصفحة ديناميكياً من الفرونت إند مع وضع سقف حماية (أقصى شيء 100 عنصر)
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
