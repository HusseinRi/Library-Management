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
        // ✅ Defensive: قراءة books_count بأمان تام (3 مستويات fallback)
        $booksCount = null;
        if ($this->resource) {
            // المستوى 1: attribute من withCount()
            if (isset($this->resource->books_count)) {
                $booksCount = (int) $this->resource->books_count;
            }
            // المستوى 2: من العلاقة المحمَّلة
            elseif ($this->resource->relationLoaded('books')) {
                $booksCount = $this->resource->books->count();
            }
        }

        return [
            'id'   => $this->id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'books_count' => $booksCount,

            // ✅ Defensive: استخدام closure form بدل BookResource::collection(whenLoaded())
            //    لتفادي أي مشكلة محتملة مع MissingValue في بعض إصدارات Laravel
            'books' => $this->whenLoaded('books', fn () => BookResource::collection($this->books)),

            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d') : null,
        ];
    }
}
