<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Book;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StatsController extends Controller
{
    /**
     * GET /api/admin/stats/overview
     * KPIs رئيسية للـ Dashboard
     */
    public function overview()
    {
        $now = Carbon::now();
        $yesterday = $now->copy()->subDay();

        // إجمالي المبيعات المدفوعة
        $totalSales = Order::where('status', 'paid')->sum('total_price');

        // المبيعات بالأمس (لحساب نسبة النمو)
        $yesterdaySales = Order::where('status', 'paid')
            ->whereDate('created_at', $yesterday)
            ->sum('total_price');

        // الطلبات اليوم والبارحة
        $todayOrders = Order::whereDate('created_at', $now)->count();
        $yesterdayOrders = Order::whereDate('created_at', $yesterday)->count();

        // المستخدمون الجدد اليوم والأسبوع
        $newUsersToday = User::where('role', 'user')->whereDate('created_at', $now)->count();
        $newUsersThisWeek = User::where('role', 'user')->whereBetween('created_at', [
            $now->copy()->startOfWeek(),
            $now->copy()->endOfWeek(),
        ])->count();

        // إجمالي المستخدمين والكتب
        $totalUsers = User::where('role', 'user')->count();
        $totalBooks = Book::count();

        // المستخدمون بالأمس (لحساب النمو)
        $yesterdayUsers = User::where('role', 'user')->whereDate('created_at', $yesterday)->count();

        // حساب نسب النمو (تجنب القسمة على صفر)
        $salesGrowth = $yesterdaySales > 0
            ? round((($totalSales - $yesterdaySales) / $yesterdaySales) * 100, 1)
            : 0;
        $ordersGrowth = $yesterdayOrders > 0
            ? round((($todayOrders - $yesterdayOrders) / $yesterdayOrders) * 100, 1)
            : 0;
        $usersGrowth = $yesterdayUsers > 0
            ? round((($newUsersToday - $yesterdayUsers) / $yesterdayUsers) * 100, 1)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_sales' => (float) $totalSales,
                'total_orders' => Order::count(),
                'total_users' => $totalUsers,
                'total_books' => $totalBooks,
                'new_users_today' => $newUsersToday,
                'new_users_this_week' => $newUsersThisWeek,
                'new_orders_today' => $todayOrders,
                'sales_growth_percent' => $salesGrowth,
                'orders_growth_percent' => $ordersGrowth,
                'users_growth_percent' => $usersGrowth,
            ],
        ], 200);
    }

    /**
     * GET /api/admin/stats/sales-chart?days=30
     * بيانات الرسم البياني للمبيعات خلال N يوم
     */
    public function salesChart(Request $request)
    {
        $days = min((int) $request->query('days', 30), 365);

        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();

        $sales = Order::where('status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders_count, SUM(total_price) as sales')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ملء الأيام الناقصة بصفر (للأيام التي لا توجد فيها مبيعات)
        $chartData = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayData = $sales->firstWhere('date', $date);

            $chartData[] = [
                'date' => $date,
                'label' => Carbon::parse($date)->translatedFormat('j M'),
                'sales' => (float) ($dayData->sales ?? 0),
                'orders' => (int) ($dayData->orders_count ?? 0),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $chartData,
        ], 200);
    }

    /**
     * GET /api/admin/stats/top-books?limit=5
     * الكتب الأكثر مبيعاً
     */
    public function topBooks(Request $request)
    {
        $limit = min((int) $request->query('limit', 5), 50);

        $topBooks = Book::withSum(['orderItems' => function ($q) {
                            $q->whereHas('order', fn($o) => $o->where('status', 'paid'));
                        }], 'price')
                        ->withCount(['orderItems' => function ($q) {
                            $q->whereHas('order', fn($o) => $o->where('status', 'paid'));
                        }])
                        ->having('order_items_count', '>', 0)
                        ->orderByDesc('order_items_count')
                        ->take($limit)
                        ->get();

        $result = $topBooks->map(fn($book) => [
            'id' => $book->id,
            'title' => $book->title,
            'image_url' => $book->image ? asset('storage/' . $book->image) : null,
            'sales_count' => $book->order_items_count,
            'revenue' => (float) $book->order_items_sum_price,
            'ratings_avg' => round($book->ratings()->avg('stars') ?? 0, 2),
            'ratings_count' => $book->ratings()->count(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $result,
        ], 200);
    }

    /**
     * GET /api/admin/orders/recent?limit=5
     * أحدث الطلبات للـ Dashboard
     */
    public function recentOrders(Request $request)
    {
        $limit = min((int) $request->query('limit', 5), 50);

        $orders = Order::with(['user:id,name,email', 'items.book:id,title,image'])
            ->latest()
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ], 200);
    }

    /**
     * GET /api/admin/stats/top-books-analytics
     * إحصائيات إضافية (متوسط التقييم، توزيع الطلبات حسب الحالة)
     */
    public function analytics()
    {
        $ordersByStatus = Order::selectRaw('status, COUNT(*) as count, SUM(total_price) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $avgRating = Rating::avg('stars');

        return response()->json([
            'success' => true,
            'data' => [
                'orders_by_status' => [
                    'pending'  => ['count' => $ordersByStatus['pending']['count'] ?? 0, 'total' => (float) ($ordersByStatus['pending']['total'] ?? 0)],
                    'paid'     => ['count' => $ordersByStatus['paid']['count'] ?? 0, 'total' => (float) ($ordersByStatus['paid']['total'] ?? 0)],
                    'failed'   => ['count' => $ordersByStatus['failed']['count'] ?? 0, 'total' => (float) ($ordersByStatus['failed']['total'] ?? 0)],
                    'refunded' => ['count' => $ordersByStatus['refunded']['count'] ?? 0, 'total' => (float) ($ordersByStatus['refunded']['total'] ?? 0)],
                ],
                'average_rating' => round($avgRating ?? 0, 2),
            ],
        ], 200);
    }
}
