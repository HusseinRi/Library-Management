<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@library.com'],
            [
                'name' => 'Library Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // 2. مستخدمون تجريبيون
        $users = collect([
            ['name' => 'محمد أحمد', 'email' => 'mohamed@example.com'],
            ['name' => 'سارة العلي', 'email' => 'sara@example.com'],
            ['name' => 'Ahmed Hassan', 'email' => 'ahmed.h@example.com'],
            ['name' => 'فاطمة الزهراء', 'email' => 'fatima@example.com'],
            ['name' => 'John Smith', 'email' => 'john@example.com'],
        ])->map(fn($u) => User::firstOrCreate(
            ['email' => $u['email']],
            array_merge($u, [
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
            ])
        ));

        // 3. تصنيفات
        $categories = collect([
            ['name' => 'Novel', 'name_ar' => 'رواية'],
            ['name' => 'Fantasy', 'name_ar' => 'فانتازيا'],
            ['name' => 'Fiction', 'name_ar' => 'أدب'],
            ['name' => 'Romantic', 'name_ar' => 'رومانسي'],
            ['name' => 'Crime', 'name_ar' => 'جريمة'],
            ['name' => 'Biography', 'name_ar' => 'سيرة ذاتية'],
        ])->map(fn($c) => Category::firstOrCreate(['name' => $c['name']], $c));

        // 4. مؤلفون
        $authors = collect([
            ['name' => 'نجيب محفوظ', 'bio' => 'أديب مصري حائز على نوبل'],
            ['name' => 'أحلام مستغانمي', 'bio' => 'روائية جزائرية'],
            ['name' => 'Greg Howard', 'bio' => 'American novelist'],
            ['name' => 'كارلوس زافون', 'bio' => 'روائي إسباني'],
            ['name' => 'Paulo Coelho', 'bio' => 'Brazilian novelist'],
        ])->map(fn($a) => Author::firstOrCreate(['name' => $a['name']], $a));

        // 5. كتب
        $books = collect([
            ['title' => 'The Whispers', 'isbn' => '978-0-06-236623-4', 'price' => 33.00, 'language' => 'english', 'category' => 0, 'author' => 2],
            ['title' => 'ذاكرة الجسد', 'isbn' => '978-9953-5-1452-1', 'price' => 28.00, 'language' => 'arabic', 'category' => 0, 'author' => 1],
            ['title' => 'The Book Thief', 'isbn' => '978-0-375-84220-7', 'price' => 44.00, 'language' => 'english', 'category' => 2, 'author' => 2],
            ['title' => 'ظل الريح', 'isbn' => '978-9953-5-1789-3', 'price' => 35.00, 'language' => 'arabic', 'category' => 4, 'author' => 3],
            ['title' => 'The Alchemist', 'isbn' => '978-0-06-112241-5', 'price' => 25.00, 'language' => 'english', 'category' => 2, 'author' => 4],
        ])->map(function ($b) use ($categories, $authors) {
            $book = Book::firstOrCreate(
                ['isbn' => $b['isbn']],
                [
                    'title' => $b['title'],
                    'price' => $b['price'],
                    'language' => $b['language'],
                    'file_type' => 'pdf',
                    'publish_date' => now(),
                    'description' => 'وصف الكتاب',
                ]
            );
            $book->categories()->sync([$categories[$b['category']]->id]);
            $book->authors()->sync([$authors[$b['author']]->id]);
            return $book;
        });

        // 6. طلبات تجريبية
        $users->each(function ($user) use ($books) {
            for ($i = 0; $i < 3; $i++) {
                $book = $books->random();
                $order = Order::create([
                    'user_id' => $user->id,
                    'total_price' => $book->price,
                    'status' => 'paid',
                ]);
                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id' => $book->id,
                    'price' => $book->price,
                ]);
                $user->myBooks()->firstOrCreate(
                    ['book_id' => $book->id],
                    [
                        'purchase_date' => now(),
                        'price' => $book->price,
                        'source' => 'purchase',
                    ]
                );
                Payment::create([
                    'order_id' => $order->id,
                    'amount' => $book->price,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'method' => 'visa',
                ]);
            }
        });

        // 7. تقييمات
        $users->each(function ($user) use ($books) {
            $book = $books->random();
            Rating::firstOrCreate(
                ['user_id' => $user->id, 'book_id' => $book->id],
                [
                    'stars' => rand(3, 5),
                    'comment' => 'كتاب رائع جداً!',
                ]
            );
        });
    }
}
