<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // ✅ Phase 2: إضافة name_ar + books_count لاستخدامها في الـ dashboard
            'name_ar' => $this->name_ar,
            'books_count' => $this->whenCounted('books', $this->resource->books_count ?? null),
            // إذا كنت بدك تعرض الكتب التابعة لهذا القسم في هذا الريسورس:
            'books' => BookResource::collection($this->whenLoaded('books')),
            // حقول الوقت (اختياري)
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d') : null,
        ];
    }
}
