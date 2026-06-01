<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. نتأكد أولاً إن المستخدم مسجل دخول، وثانياً إن الـ role تبعه admin
        if ($request->user() && $request->user()->role === 'admin') {
            // إذا تحقق الشرط، بنخليه يكمل على الـ Controller
            return $next($request);
        }

        // 2. إذا ما تحقق الشرط، بنمنعه وبنرجعله رسالة خطأ بصيغة JSON
        return response()->json([
            'message' => 'Unauthorized. Admin access required.'
        ], 403); // 403 يعني Forbidden (ممنوع الدخول)
    }
}