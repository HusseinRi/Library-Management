<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Book; // افترضت أن اسم موديل الكتب عندك هو Book
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentController extends Controller
{

    public function initiatePayment(Request $request)
    {
        // 1. التحقق من المدخلات (مصفوفة من معرفات الكتب)
        $request->validate([
            'book_ids' => 'required|array',
            'book_ids.*' => 'exists:books,id',
        ]);

        // نستخدم الـ Transaction لضمان سلامة البيانات بالكامل
        DB::beginTransaction();

        try {
            // 2. جلب الكتب وحساب السعر الإجمالي لمنع تلاعب الفرونت إند بالأسعار
            $books = Book::whereIn('id', $request->book_ids)->get();
            $totalPrice = $books->sum('price'); // السعر مخزن كـ integer (سنتات)

            if ($totalPrice <= 0) {
                return response()->json(['error' => 'إجمالي السعر غير صالح.'], 400);
            }

            // 3. إنشاء الطلب (Order)
            $order = Order::create([
                'user_id' => auth()->id(), // جلب المستخدم الحالي المسجل عبر Sanctum/Passport
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);

            // 4. إنشاء عناصر الطلب (Order Items)
            foreach ($books as $book) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id' => $book->id,
                    'price' => $book->price,
                ]);
            }

            // 5. تهيئة Stripe باستخدام المفتاح السري المخرّن في config
            Stripe::setApiKey(config('services.stripe.secret'));

            // 6. إنشاء الـ Payment Intent لدى Stripe
            $paymentIntent = PaymentIntent::create([
                'amount' => $totalPrice, // المبلغ بالسنتات (مثلاً: 1000 تعني 10.00$)
                'currency' => 'usd',     // اختر العملة التي تناسبك
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => auth()->id(),
                ],
            ]);

            // 7. إنشاء سجل الدفع المبدئي (Payment) وربطه بـ Stripe Intent ID
            Payment::create([
                'order_id' => $order->id,
                'amount' => $totalPrice,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'status' => 'pending',
                'paid_at' => null, // لم يتم الدفع بعد
            ]);

            DB::commit();

            // 8. إرجاع الـ client_secret للفرونت إند لإتمام الدفع
            return response()->json([
                'message' => 'تم إنشاء عملية الدفع بنجاح.',
                'client_secret' => $paymentIntent->client_secret,
                'order_id' => $order->id,
                'total_price' => $totalPrice
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'فشلت عملية تهيئة الدفع، يرجى المحاولة لاحقاً.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}