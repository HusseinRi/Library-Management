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
        'description',
        'total_copies'
    ];
    use HasFactory;
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
    public function orders()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function authors()
    {
        return $this->belongsToMany(Author::class);
    }
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }
    public function myBooks()
    {
        return $this->hasMany(MyBook::class);
    }
    public function readingProgresses()
    {
        return $this->hasMany(ReadingProgress::class);
    }
}
