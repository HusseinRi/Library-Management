<?php

namespace App\Models;

use App\Filters\AbstractFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'isbn',
        'description',
        'price',
        'file_path',
        'image',         // 👈 أضفناه هنا لتفادي مشاكل الـ POST والـ Mass Assignment
        'publish_date'   // 👈 أضفناه هنا لتفادي مشاكل الـ POST والـ Mass Assignment
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'book_categories');
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'book_authors');
    }

    public function orders()
    {
        return $this->hasMany(OrderItem::class);
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
    public function scopeFilter(Builder $builder, AbstractFilter $filter): Builder
    {
        // نقوم بتمرير الـ Builder الحالي إلى كلاس الفلترة ليقوم بتركيب شروط الـ SQL ديناميكياً
        return $filter->apply($builder);
    }
}