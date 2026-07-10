<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReadingProgress extends Model
{
    protected $fillable = [
        'user_id',
        'book_id',
        'last_page',
        'total_pages',
        'progress_percent',
        'last_read_at'
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
