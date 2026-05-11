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
            'total_copies' => $this->faker->numberBetween(5, 20), // عدد نسخ بين 5 و 20
            'available_copies' => $this->faker->numberBetween(1, 5), // نسخ متاحة أقل من الإجمالي

            // ربط العلاقات (المنطق الأهم)
            'category_id' => Category::factory(), // سينشئ تصنيفاً جديداً لكل كتاب
            'author_id' => Author::factory(),   // سينشئ مؤلفاً جديداً لكل كتاب
        ];
    }
}
