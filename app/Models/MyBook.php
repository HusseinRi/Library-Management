<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MyBook extends Model
{
    protected $fillable = [
        'user_id',
        'book_id',
        'purchase_date',
        'price',
        'source',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
