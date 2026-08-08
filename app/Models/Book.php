<?php

namespace App\Models;

use App\Filters\AbstractFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'isbn',
        'description',
        'price',
        'file_path',
        'image',
        'publish_date',
        'language',      // ✅ أضيف لتفادي مشكلة Mass Assignment
        'file_type',     // ✅ أضيف لتفادي مشكلة Mass Assignment
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'book_categories');
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'book_authors');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'book_id');
    }

    /** @deprecated Use orderItems() instead */
    public function orders()
    {
        return $this->orderItems();
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

    /**
     * ✅ FIX: تحويل publish_date تلقائياً إلى Carbon date object.
     * بدون هذا الـ cast، تُرجع القيمة كـ string عادي من قاعدة البيانات،
     * مما يُسبب خطأ "Call to a member function format() on string" في BookResource.
     */
    protected function casts(): array
    {
        return [
            'publish_date' => 'date',
        ];
    }
}