<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'amount',
        'stripe_payment_intent_id',
        'status',
        'method',
        'paid_at',

    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
