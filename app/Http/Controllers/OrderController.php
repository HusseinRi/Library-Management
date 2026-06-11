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


    public function store(StoreOrderRequest $request)
    {
        $bookIds = $request->book_ids;
        $books = Book::whereIn('id', $bookIds)->get();
        $totalPrice = $books->sum('price');


        DB::beginTransaction();

        try {

            $order = Order::create([
                'total_price' => $totalPrice,
                'user_id' => $request->user()->id
            ]);


            foreach ($books as $book) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id' => $book->id,
                    'price' => $book->price
                ]);

                $request->user()->myBooks()->create([
                    'book_id' => $book->id,
                    'purchase_date' => now(),
                    'price' => $book->price
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'The purchase was completed successfully. The books are now available in your library.',
                'order_id' => $order->id,
                'total_price' => $totalPrice
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong during the purchase. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function index(Request $request)
    {

        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items.book'])
            ->latest()
            ->get();


        return response()->json([
            'success' => true,
            'data' => $orders
        ], 200);
    }
    public function show(Request $request, $id)
    {

        $order = Order::where('user_id', $request->user()->id)
            ->with(['items.book'])
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

