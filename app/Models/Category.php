<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'name_ar',
    ];
    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_categories');
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'category_user');
    }
}
