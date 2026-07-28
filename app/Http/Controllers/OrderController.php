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

