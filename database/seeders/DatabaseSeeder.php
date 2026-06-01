<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. إنشاء مستخدم تجريبي مسبق
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // 2. إنشاء 5 أقسام و 5 مؤلفين في قاعدة البيانات أولاً
        $categories = \App\Models\Category::factory(5)->create();
        $authors = \App\Models\Author::factory(5)->create();

        // 3. إنشاء 10 كتب وربطها عشوائياً بالأقسام والمؤلفين عبر الجداول الوسيطة (Pivot Tables)
        \App\Models\Book::factory(10)->create()->each(function ($book) use ($categories, $authors) {
            // ربط الكتاب بقسم أو قسمين عشوائيين من الأقسام التي أنشأناها
            $book->categories()->attach($categories->random(rand(1, 2))->pluck('id')->toArray());

            // ربط الكتاب بمؤلف عشوائي واحد
            $book->authors()->attach($authors->random(1)->pluck('id')->toArray());
        });
        $this->call(AdminSeeder::class);

        // إذا أردت إنشاء بيانات إضافية يمكنك إلغاء التعليق عن السطرين التاليين:
        // \App\Models\Book::factory(10)->create();
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
