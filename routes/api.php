<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\AuthorController;
use App\Http\Middleware\IsAdmin; // استدعاء الـ Middleware الجديد
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسارات المصادقة (Auth Routes)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// مسارات تتطلب مستخدم مسجل دخول (أي مستخدم)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| مسارات الكتب (Books Routes)
|--------------------------------------------------------------------------
*/

// 1. مسارات العرض (متاحة للجميع بدون تسجيل دخول)
// نستخدم only لنحدد أننا نريد فقط دالتي index (عرض الكل) و show (عرض كتاب واحد)
Route::apiResource('books', BookController::class)->only(['index', 'show']);

// 2. مسارات الإدارة (مقتصرة على الـ Admin فقط)
// نغلفها بتسجيل الدخول أولاً (auth:sanctum) ثم جدار الـ Admin ثانياً (IsAdmin::class)
Route::middleware(['auth:sanctum', IsAdmin::class])->group(function () {

    // نستخدم only لنحدد دوال الإضافة والتعديل والحذف فقط
    Route::apiResource('books', BookController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('authors', AuthorController::class)->only(['store', 'update', 'destroy']);

});
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('authors', AuthorController::class)->only(['index', 'show']);