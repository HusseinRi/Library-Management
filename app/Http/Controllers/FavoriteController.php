<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFavoriteRequest;
use App\Http\Resources\BookResource;
use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * UC-017: عرض قائمة المفضلة للمستخدم الحالي
     * GET /api/favorites
     */
    public function index(Request $request)
    {
        $favorites = $request->user()
            ->favorites()
            ->with('book.categories', 'book.authors')
            ->latest()
            ->get();

        $books = $favorites->pluck('book');

        return response()->json([
            'success' => true,
            'count' => $books->count(),
            'data' => BookResource::collection($books),
        ], 200);
    }

    /**
     * UC-016: إضافة كتاب للمفضلة
     * POST /api/favorites
     * body: { book_id: number }
     */
    public function store(StoreFavoriteRequest $request)
    {
        $userId = $request->user()->id;
        $bookId = $request->book_id;

        // منع التكرار (firstOrCreate)
        $favorite = Favorite::firstOrCreate(
            ['user_id' => $userId, 'book_id' => $bookId],
            ['user_id' => $userId, 'book_id' => $bookId]
        );

        $wasRecentlyCreated = $favorite->wasRecentlyCreated;

        return response()->json([
            'success' => true,
            'message' => $wasRecentlyCreated
                ? 'Book added to favorites successfully.'
                : 'Book is already in your favorites.',
            'data' => $favorite,
        ], $wasRecentlyCreated ? 201 : 200);
    }

    /**
     * UC-017: حذف كتاب من المفضلة
     * DELETE /api/favorites/{book_id}   (يمرّر book_id وليس favorite id لسهولة الاستخدام)
     */
    public function destroy(Request $request, $bookId)
    {
        $deleted = $request->user()
            ->favorites()
            ->where('book_id', $bookId)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'This book is not in your favorites.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Book removed from favorites.',
        ], 200);
    }

    /**
     * فحص سريع: هل الكتاب في مفضلة المستخدم؟
     * GET /api/favorites/{book_id}/check
     */
    public function check(Request $request, $bookId)
    {
        $isFavorite = $request->user()
            ->favorites()
            ->where('book_id', $bookId)
            ->exists();

        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite,
        ], 200);
    }
}
