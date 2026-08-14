<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /**
     * GET /api/reports/sales?period=daily|weekly|monthly|all
     *
     * ✅ تم الإصلاح: استخدام OrderResource بدل إرجاع نماذج Eloquent خامًا،
     *    للحصول على شكل JSON موحّد وآمن يطابق /api/admin/orders/recent.
     */
    public function salesReport(Request $request)
    {
        $period = $request->query('period', 'monthly');

        $query = Order::query();

        switch ($period) {
            case 'daily':
                $query->whereDate('created_at', Carbon::today());
                break;

            case 'weekly':
                $query->whereBetween('created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ]);
                break;

            case 'monthly':
                $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
                break;

            case 'all':
                // جلب كل المبيعات منذ إطلاق النظام (بدون فلتر زمني)
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid period specified. Use daily, weekly, monthly, or all.'
                ], 400);
        }

        // 1. نجلب القائمة مع العلاقات (user, payment, items.book)
        $ordersList = $query->with(['user:id,name,email', 'payment', 'items.book:id,title,image'])
            ->latest()
            ->get();

        // 2. حساب الإحصائيات (من الكولكشن المُحمَّل مسبقاً لتفادي استعلامات إضافية)
        $paidOrders = $ordersList->where('status', 'paid');
        $totalSales = $paidOrders->sum('total_price');
        $totalOrdersCount = $ordersList->count();
        $paidOrdersCount = $paidOrders->count();
        $refundedOrdersCount = $ordersList->where('status', 'refunded')->count();
        $avgOrderValue = $paidOrdersCount > 0 ? $totalSales / $paidOrdersCount : 0;

        return response()->json([
            'success' => true,
            'report_period' => $period,
            'summary' => [
                'total_sales'             => (float) $totalSales,
                'total_orders_count'      => $totalOrdersCount,
                'paid_orders_count'       => $paidOrdersCount,
                'refunded_orders_count'   => $refundedOrdersCount,
                'avg_order_value'         => round($avgOrderValue, 2),
            ],
            'data' => OrderResource::collection($ordersList),
        ], 200);
    }
}
