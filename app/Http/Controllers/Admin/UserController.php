<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * UC-012: عرض قائمة المستخدمين (للمدير)
     * GET /api/admin/users?search=&status=&page=&per_page=
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'user');

        // فلترة بالبحث
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // فلترة بالحالة
        $status = $request->query('status', 'all');
        if (in_array($status, ['active', 'blocked'])) {
            $query->where('status', $status);
        }

        // إضافة إحصائيات لكل مستخدم (count + sum)
        $perPage = min($request->query('per_page', 15), 100);

        $users = $query->withCount(['orders' => function ($q) {
                        $q->where('status', 'paid');
                    }])
                    ->withSum(['orders' => function ($q) {
                        $q->where('status', 'paid');
                    }], 'total_price')
                    ->latest()
                    ->paginate($perPage);

        // إعادة تسمية الحقول لتكون أوضح للـ frontend
        $users->getCollection()->transform(function ($user) {
            $user->orders_count = $user->orders_count ?? 0;
            $user->total_spent = $user->orders_sum_total_price ?? 0;
            unset($user->orders_sum_total_price);
            return $user;
        });

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'total' => $users->total(),
            'page' => $users->currentPage(),
            'per_page' => $users->perPage(),
            'last_page' => $users->lastPage(),
        ], 200);
    }

    /**
     * UC-012: عرض مستخدم محدد مع سجل مشترياته
     * GET /api/admin/users/{id}
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        $orders = Order::where('user_id', $id)
            ->with(['items.book', 'payment'])
            ->latest()
            ->get();

        $stats = [
            'orders_count' => $orders->where('status', 'paid')->count(),
            'total_spent' => $orders->where('status', 'paid')->sum('total_price'),
            'favorite_books_count' => $user->favorites()->count(),
            'ratings_count' => $user->ratings()->count(),
            'last_login_at' => $user->last_login_at,
            'last_login_ip' => $user->last_login_ip,
            'joined_at' => $user->created_at,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'stats' => $stats,
                'orders' => $orders,
            ],
        ], 200);
    }

    /**
     * UC-013: حظر مستخدم
     * PATCH /api/admin/users/{id}/block
     */
    public function block($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'You cannot block an admin account.',
            ], 403);
        }

        if ($user->status === 'blocked') {
            return response()->json([
                'success' => false,
                'message' => 'User is already blocked.',
            ], 409);
        }

        // حظر الحساب + إلغاء جميع التوكنات النشطة
        $user->update(['status' => 'blocked']);
        $user->tokens()->delete(); // تسجيل خروج إجباري

        return response()->json([
            'success' => true,
            'message' => 'User has been blocked and all active sessions were revoked.',
            'data' => $user->fresh(),
        ], 200);
    }

    /**
     * UC-013: إلغاء حظر مستخدم
     * PATCH /api/admin/users/{id}/unblock
     */
    public function unblock($id)
    {
        $user = User::findOrFail($id);

        if ($user->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'User is already active.',
            ], 409);
        }

        $user->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'User has been unblocked successfully.',
            'data' => $user->fresh(),
        ], 200);
    }

    /**
     * UC-013: حذف مستخدم (مع الاحتفاظ بسجل المشتريات لأغراض التدقيق)
     * DELETE /api/admin/users/{id}
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete an admin account.',
            ], 403);
        }

        // إلغاء التوكنات + حذف المفضلة والتقييمات + Soft delete
        $user->tokens()->delete();
        $user->favorites()->delete();
        $user->ratings()->delete();

        // الطلبات تبقى مرتبطة بـ user_id لأغراض التدقيق المالي
        // (الـ Soft deletes مفعّل في users migration)

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User has been deleted. Order history preserved for audit purposes.',
        ], 200);
    }
}
