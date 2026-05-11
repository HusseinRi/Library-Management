<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// 1. استدعاء المسار الصحيح للـ Trait
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Book extends Model
{
    protected $fillable = [
        'title',
        'isbn',
        'category_id',
        'author_id',
        'description',    // أضف أي حقول أخرى تستخدمها
        'total_copies'
    ];
    use HasFactory;
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function author()
    {
        return $this->belongsTo(Author::class);
    }
}
