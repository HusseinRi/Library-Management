<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * ✅ محدّث: منع الشراء المزدوج + حفظ الـ order بـ status=pending
     * (الدفع الفعلي عبر Stripe سيُضاف لاحقاً)
     */
    public function store(StoreOrderRequest $request)
    {
        $bookIds = $request->book_ids;
        $user = $request->user();

        // 1. جلب الكتب المطلوبة
        $books = Book::whereIn('id', $bookIds)->get();

        if ($books->count() !== count($bookIds)) {
            return response()->json([
                'success' => false,
                'message' => 'One or more books were not found.',
            ], 404);
        }

        // 2. ⚠️ فحص الشراء المزدوج: هل المستخدم يملك أي من هذه الكتب أصلاً؟
        $alreadyOwned = $user->myBooks()
            ->whereIn('book_id', $bookIds)
            ->pluck('book_id')
            ->toArray();

        if (!empty($alreadyOwned)) {
            $ownedTitles = Book::whereIn('id', $alreadyOwned)->pluck('title')->implode('، ');
            return response()->json([
                'success' => false,
                'message' => 'You already own some of these books.',
                'already_owned_books' => $alreadyOwned,
                'already_owned_titles' => $ownedTitles,
            ], 409); // 409 Conflict
        }

        // 3. تصفية الكتب المجانية (إن وجدت) - تُضاف مباشرة بدون Payment
        $freeBooks = $books->filter(fn($b) => $b->price == 0);
        $paidBooks = $books->filter(fn($b) => $b->price > 0);

        $totalPrice = $paidBooks->sum('price');

        DB::beginTransaction();
        try {
            // 4. إنشاء الطلب بـ status=pending (يصبح paid بعد تأكيد الدفع)
            $order = Order::create([
                'total_price' => $totalPrice,
                'user_id' => $user->id,
                'status' => $totalPrice > 0 ? 'pending' : 'paid', // مجاني = paid مباشرة
            ]);

            // 5. إنشاء OrderItem لكل كتاب (مدفوع ومجاني)
            foreach ($books as $book) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id' => $book->id,
                    'price' => $book->price,
                ]);
            }

            // 6. الكتب المجانية تُضاف لمكتبة المستخدم فوراً
            foreach ($freeBooks as $book) {
                $user->myBooks()->create([
                    'book_id' => $book->id,
                    'purchase_date' => now(),
                    'price' => $book->price,
                    'source' => 'free',
                ]);
            }

            // 7. الكتب المدفوعة تُضاف فقط بعد الدفع الناجح (في PaymentController)
            //    لكن مؤقتاً للاختبار نضيفها الآن (سيُعدّل عند دمج Stripe)
            if ($totalPrice > 0) {
                // ⚠️ مؤقت: نضيفها مباشرة حتى يُدمج Stripe
                // عند دمج Stripe: احذف هذا الـ block وضعه في PaymentController@confirm
                foreach ($paidBooks as $book) {
                    $user->myBooks()->create([
                        'book_id' => $book->id,
                        'purchase_date' => now(),
                        'price' => $book->price,
                        'source' => 'purchase',
                    ]);
                }
                $order->update(['status' => 'paid']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'The purchase was completed successfully. The books are now available in your library.',
                'order_id' => $order->id,
                'total_price' => $totalPrice,
                'status' => $order->status,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            // التقاط خطأ unique constraint (إذا حصل تسريب race condition)
            if (str_contains($e->getMessage(), 'my_books_user_id_book_id_unique')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already own one or more of these books.',
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong during the purchase. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض طلبات المستخدم الحالي
     */
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items.book', 'payment'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ], 200);
    }

    /**
     * عرض تفاصيل طلب محدد
     */
    public function show(Request $request, $id)
    {
        $order = Order::where('user_id', $request->user()->id)
            ->with(['items.book', 'payment'])
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or you do not have permission to view it.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ], 200);
    }
}
