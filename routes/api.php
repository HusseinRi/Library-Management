<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StatsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookFileController;
use App\Http\Controllers\BookSearchController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ReadingProgressController;
use App\Http\Middleware\IsAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. مسارات المصادقة العامة (Public Auth Routes)
|--------------------------------------------------------------------------
*/
Route::post('/register',            [AuthController::class, 'register']);
Route::post('/login',               [AuthController::class, 'login']);
Route::post('/verify-email',        [AuthController::class, 'verifyEmail']);
Route::post('/forgot-password',     [AuthController::class, 'forgotPassword']);
Route::post('/verify-reset-otp',    [AuthController::class, 'verifyResetOtp']);
Route::post('/reset-password',      [AuthController::class, 'resetPassword']);

/*
|--------------------------------------------------------------------------
| 2. مسارات العرض العامة (Public Read-Only Routes)
|--------------------------------------------------------------------------
*/
Route::apiResource('books', BookController::class)->only(['index', 'show']);
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('authors', AuthorController::class)->only(['index', 'show']);

// ✅ UC-005 + UC-006: بحث وفلترة عام (لا يحتاج تسجيل دخول)
Route::get('/booksSearch', [BookSearchController::class, 'index'])->name('books.search');

// ✅ UC-015: الكتب الأكثر مبيعاً (عام)
Route::get('/books/best-sellers', [BookController::class, 'bestSellers'])->name('books.best-sellers');

// ✅ UC-018: أحدث الكتب (عام)
Route::get('/books/newest', [BookController::class, 'newest'])->name('books.newest');

/*
|--------------------------------------------------------------------------
| 3. مسارات المستخدمين المسجلين (Protected Routes via Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // ===== بيانات المستخدم الحالي والملف الشخصي =====
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    // ✅ UC-004: إدارة الملف الشخصي
    Route::get('/profile',           [ProfileController::class, 'show']);
    Route::put('/profile',           [ProfileController::class, 'update']);
    Route::patch('/profile/password',[ProfileController::class, 'changePassword']);

    // ===== الطلبات (UC-022) =====
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);

    // ===== المصادقة: تسجيل الخروج =====
    Route::post('/logout', [AuthController::class, 'logout']);

    // ===== مكتبة المستخدم (UC-007) =====
    Route::get('/my-library', [BookController::class, 'myLibrary']);

    // ===== تقدم القراءة (UC-009) =====
    Route::post('/reading-progress',          [ReadingProgressController::class, 'updateProgress']);
    Route::get('/reading-progress/{book_id}', [ReadingProgressController::class, 'getProgress']);

    // ===== بث ملف الكتاب (UC-008 — محمي لأنه يتطلب ملكية) =====
    Route::get('/books/{book_id}/stream', [BookFileController::class, 'streamBook']);

    // ===== المفضلة (UC-016, UC-017) =====
    Route::get('/favorites',                  [FavoriteController::class, 'index']);
    Route::post('/favorites',                 [FavoriteController::class, 'store']);
    Route::delete('/favorites/{book_id}',     [FavoriteController::class, 'destroy']);
    Route::get('/favorites/{book_id}/check',  [FavoriteController::class, 'check']);

    // ===== التقييمات (UC-020) =====
    Route::get('/books/{book_id}/ratings', [RatingController::class, 'bookRatings']);
    Route::post('/books/{book_id}/ratings',[RatingController::class, 'store']);
    Route::delete('/ratings/{id}',         [RatingController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | 4. جدار حماية المشرفين (Admin-Only Routes)
    |--------------------------------------------------------------------------
    */
    Route::middleware(IsAdmin::class)->group(function () {

        // ===== CRUD للكتب والتصنيفات والمؤلفين =====
        Route::apiResource('books',      BookController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('authors',    AuthorController::class)->only(['store', 'update', 'destroy']);

        // ===== إدارة المستخدمين (UC-012, UC-013) =====
        Route::get('/admin/users',                 [UserController::class, 'index']);
        Route::get('/admin/users/{id}',            [UserController::class, 'show']);
        Route::patch('/admin/users/{id}/block',    [UserController::class, 'block']);
        Route::patch('/admin/users/{id}/unblock',  [UserController::class, 'unblock']);
        Route::delete('/admin/users/{id}',         [UserController::class, 'destroy']);

        // ===== لوحة التحكم: الإحصائيات =====
        Route::get('/admin/stats/overview',        [StatsController::class, 'overview']);
        Route::get('/admin/stats/sales-chart',     [StatsController::class, 'salesChart']);
        Route::get('/admin/stats/top-books',       [StatsController::class, 'topBooks']);
        Route::get('/admin/stats/analytics',       [StatsController::class, 'analytics']);
        Route::get('/admin/orders/recent',         [StatsController::class, 'recentOrders']);

        // ===== التقارير المالية (UC-014) =====
        Route::get('/reports/sales', [ReportController::class, 'salesReport']);
    });
});
