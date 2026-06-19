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
            'publish_date' => $this->publish_date,
            "rating"=>$this->rating,
            // 1. الصورة: نستخدم asset() مع إضافة /storage/ لتوليد رابط ويب كامل ومباشر
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,

            // 2. ملف الكتاب: نستخدم asset() أيضاً لتوليد رابط مباشر (للتجربة الحالية)
            'pdf_url' => $this->file_path ? asset('storage/' . $this->file_path) : null,

            'categories' => $this->categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            }),
            'authors' => $this->authors->map(function ($author) {
                return [
                    'id' => $author->id,
                    'name' => $author->name,
                ];
            }),
        ];
    }
}
