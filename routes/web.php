<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ✅ Fallback route named "login" — لمنع RouteNotFoundException عندما يحاول
// middleware الـ auth عمل redirect لمستخدم غير مسجل دخول ولا يوجد صفحة ويب فعلية.
// هذا المشروع API فقط، فهذا الروت لا يُستخدم إلا كحل احتياطي لتفادي الكراش.
Route::get('/login', function () {
    return response()->json([
        'success' => false,
        'message' => 'Unauthenticated.',
    ], 401);
})->name('login');
