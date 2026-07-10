<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookFileController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReadingProgressController;
use App\Http\Middleware\IsAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. مسارات المصادقة العامة (Public Auth Routes)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

/*
|--------------------------------------------------------------------------
| 2. مسارات العرض العامة (Public Read-Only Routes)
|--------------------------------------------------------------------------
*/
Route::apiResource('books', BookController::class)->only(['index', 'show']);
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('authors', AuthorController::class)->only(['index', 'show']);

/*
|--------------------------------------------------------------------------
| 3. مسارات المستخدمين المسجلين (Protected Routes via Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // جلب بيانات المستخدم الحالي وسجل طلباته
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('my-library', [BookController::class, 'myLibrary']);
    Route::post('/reading-progress', [ReadingProgressController::class, 'updateProgress']);
    Route::get('/reading-progress/{book_id}', [ReadingProgressController::class, 'getProgress']);
    Route::get('/books/{book_id}/stream', [BookFileController::class, 'streamBook']);
    /*
    |--------------------------------------------------------------------------
    | 4. جدار حماية المشرفين (Admin-Only Routes)
    |--------------------------------------------------------------------------
    */
    Route::middleware(IsAdmin::class)->group(function () {
        Route::apiResource('books', BookController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('authors', AuthorController::class)->only(['store', 'update', 'destroy']);
        Route::get('/reports/sales', [ReportController::class, 'salesReport']);
    });
});