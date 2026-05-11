<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'book_title' => $this->title,
            'isbn_number' => $this->isbn,
            'short_description' => $this->description,
            'category' => $this->category->name, // جلب اسم التصنيف بدلاً من الـ ID
            'author' => $this->author->name,     // جلب اسم المؤلف
        ];
    }
}
