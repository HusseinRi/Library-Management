<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'book_id'    => $this->book_id,
            'price'      => (float) $this->price,

            // ✅ FIX: guard داخلي لتفادي "Attempt to read property on null"
            //    عند حذف الكتاب لكن بقاء الـ OrderItem (مع soft deletes قد يحدث).
            'book'       => $this->whenLoaded('book', function () {
                if (! $this->book) {
                    return null;
                }
                return [
                    'id'         => $this->book->id,
                    'title'      => $this->book->title,
                    'image_url'  => $this->book->image ? asset('storage/' . $this->book->image) : null,
                ];
            }),
        ];
    }
}
