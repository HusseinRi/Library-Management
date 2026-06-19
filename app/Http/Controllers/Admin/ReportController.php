<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
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
        // 1. نجلب القائمة أولاً
        $ordersList = $query->with('user:id,name')->get();

        // 2. نحسب مباشرة من المصفوفة الراجعة في الذاكرة (سريع جداً وموفر لجهد السيرفر)
        $totalSales = $ordersList->sum('total_price');
        $totalOrdersCount = $ordersList->count();

        return response()->json([
            'success' => true,
            'report_period' => $period,
            'summary' => [
                'total_sales' => $totalSales,
                'total_orders_count' => $totalOrdersCount,
            ],
            'data' => $ordersList
        ], 200);
    }
}
