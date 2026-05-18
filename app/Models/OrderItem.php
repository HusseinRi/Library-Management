<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'book_id',
        'order_id',
        'price'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
