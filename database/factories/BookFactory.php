<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4), // يولد عنوان من 4 كلمات
            'isbn' => $this->faker->unique()->isbn13(), // رقم دولي فريد من 13 خانة
            'description' => $this->faker->paragraph(), // فقرة نصية للوصف
            'image' => 'books/default.jpg', // مسار افتراضي مؤقت للصور
            'price' => $this->faker->numberBetween(1000, 10000), // يولد سعر عشوائي للكتب المدفوعة
            'file_path' => 'books/demo.pdf', // مسار وهمي لملف الكتاب الإلكتروني
            'publish_date' => $this->faker->date(), // 👈 السطر الجديد: لتوليد تاريخ نشر عشوائي وحل مشكلة الداتابيز
        ];
    }
}