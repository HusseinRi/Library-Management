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
            'book'       => $this->whenLoaded('book', function () {
                return [
                    'id'         => $this->book->id,
                    'title'      => $this->book->title,
                    'image_url'  => $this->book->image ? asset('storage/' . $this->book->image) : null,
                ];
            }),
        ];
    }
}
