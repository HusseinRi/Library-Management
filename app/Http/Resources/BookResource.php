<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'book_title' => $this->title,
            'isbn_number' => $this->isbn,
            'short_description' => $this->description,
            'price' => $this->price,
            'publish_date' => $this->publish_date?->format('Y-m-d'),

            // ✅ Phase 2: إضافة language (كانت مفقودة في Phase 1)
            'language' => $this->language,

            // 1. الصورة: نستخدم asset() مع إضافة /storage/ لتوليد رابط ويب كامل ومباشر
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,

            // 2. ملف الكتاب: رابط آمن للآدمن فقط عبر endpoint مخصص
            //    (الملف محفوظ على local disk، لا يمكن الوصول إليه عبر /storage/ مباشرة)
            'pdf_url' => $this->file_path
                ? url('/api/admin/books/' . $this->id . '/file')
                : null,

            // 3. نوع الملف: مفيد للـ Frontend ليعرف هل يفتح PDF أم EPUB
            'file_type' => $this->file_type,

            // ✅ FIX (Bug 500): استخدام whenLoaded بدل الوصول المباشر للعلاقة.
            //    عند استدعاء BookResource من OrderItemResource، تكون العلاقات غير محمَّلة،
            //    والوصول لـ $this->categories يُسبب استثناء "property not found" أو lazy loading.
            //    whenLoaded تُرجع [] افتراضياً دون أي استثناء.
            'categories' => $this->whenLoaded('categories', function () {
                return $this->categories->map(fn ($category) => [
                    'id'   => $category->id,
                    'name' => $category->name,
                ]);
            }, []),

            'authors' => $this->whenLoaded('authors', function () {
                return $this->authors->map(fn ($author) => [
                    'id'   => $author->id,
                    'name' => $author->name,
                ]);
            }, []),

            //'average_rating' => $this->ratings_avg_rating ? round($this->ratings_avg_rating, 2) : 0.0,
        ];
    }
}
