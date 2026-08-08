<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\BookResource;

class AuthorResource extends JsonResource
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
            if (isset($this->resource->books_count)) {
                $booksCount = (int) $this->resource->books_count;
            } elseif ($this->resource->relationLoaded('books')) {
                $booksCount = $this->resource->books->count();
            }
        }

        return [
            'id'   => $this->id,
            'name' => $this->name,
            'bio'  => $this->bio,
            'books_count' => $booksCount,

            // ✅ Defensive: closure form
            'books' => $this->whenLoaded('books', fn () => BookResource::collection($this->books)),

            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d') : null,
        ];
    }
}
