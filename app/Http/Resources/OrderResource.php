<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            'total_price'  => (float) $this->total_price,
            'status'       => $this->status,
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),

            // علاقات تُحمَّل اختيارياً عند whenLoaded
            'user' => $this->whenLoaded('user', fn () => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ]),

            'items' => OrderItemResource::collection($this->whenLoaded('items')),

            // ✅ FIX: whenLoaded('payment', ...) يستدعي الـ closure حتى لو كان payment = null
            //    (لأن العلاقة "loaded" كـ null). نضيف guard داخلي للتحقق من وجود payment.
            'payment' => $this->whenLoaded('payment', function () {
                if (! $this->payment) {
                    return null;
                }
                return [
                    'id'                       => $this->payment->id,
                    // ✅ Phase 2: إعادة تسمية amount → amount_cents (Best Practice من Stripe)
                    //    المبلغ بالسنتات (integer) — الـ Frontend يستخدم formatCents() لتحويله لدولار
                    'amount_cents'             => $this->payment->amount,
                    'status'                   => $this->payment->status,
                    'method'                   => $this->payment->method,
                    'stripe_payment_intent_id' => $this->payment->stripe_payment_intent_id,
                    'paid_at'                  => optional($this->payment->paid_at)?->toIso8601String(),
                ];
            }),
        ];
    }
}
