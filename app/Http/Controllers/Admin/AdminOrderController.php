<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * ✅ Phase 2 — AdminOrderController
 * ───────────────────────────────────────────────────────────
 * متحكم منفصل لإدارة الطلبات من لوحة الآدمن.
 * يختلف عن OrderController (الموجود في الجذر) في:
 *   - يعرض كل الطلبات (ليس طلبات المستخدم الحالي فقط)
 *   - يدعم pagination + filters (search, status, period)
 *   - يستخدم OrderResource لشكل JSON موحّد وآمن
 *
 * المسارات المسجَّلة في routes/api.php:
 *   GET    /api/admin/orders          → index()
 *   GET    /api/admin/orders/{id}     → show()
 */
class AdminOrderController extends Controller
{
    /**
     * GET /api/admin/orders?search=&status=&period=&page=&per_page=
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse  شكل Laravel paginator الافتراضي:
     *   { data: OrderResource[], links, meta: { current_page, last_page, per_page, total } }
     */
    public function index(Request $request)
    {
        $query = Order::with(['user:id,name,email', 'items.book:id,title,image', 'payment']);

        // ===== فلترة البحث =====
        // يبحث في: رقم الطلب، اسم المستخدم، إيميل المستخدم
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        }

        // ===== فلترة الحالة =====
        $status = $request->query('status', 'all');
        if (in_array($status, ['pending', 'paid', 'failed', 'refunded', 'completed'])) {
            $query->where('status', $status);
        }

        // ===== فلترة الفترة الزمنية =====
        $period = $request->query('period', 'all');
        $this->applyPeriodFilter($query, $period);

        // ===== الترقيم =====
        $perPage = min((int) $request->query('per_page', 10), 100);

        $orders = $query->latest()->paginate($perPage);

        // OrderResource::collection ستتعامل مع pagination تلقائياً وتُرجع:
        // { data: [...], links: {...}, meta: {...} }
        return OrderResource::collection($orders);
    }

    /**
     * GET /api/admin/orders/{id}
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse  شكل: { success, data: OrderResource }
     */
    public function show($id)
    {
        $order = Order::with(['user:id,name,email', 'items.book:id,title,image', 'payment'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new OrderResource($order),
        ], 200);
    }

    /**
     * تطبيق فلتر الفترة الزمنية على الـ query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $period  daily | weekly | monthly | all
     */
    private function applyPeriodFilter($query, string $period): void
    {
        switch ($period) {
            case 'daily':
                $query->whereDate('created_at', Carbon::today());
                break;

            case 'weekly':
                $query->whereBetween('created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ]);
                break;

            case 'monthly':
                $query->whereMonth('created_at', Carbon::now()->month)
                      ->whereYear('created_at', Carbon::now()->year);
                break;

            case 'all':
                // بدون فلتر زمني
                break;

            default:
                // قيمة غير صالحة → تجاهل (لا نُرجع خطأ للحفاظ على المتانة)
                break;
        }
    }
}
