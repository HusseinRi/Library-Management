<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRatingRequest;
use App\Http\Resources\BookResource;
use App\Models\Rating;
use App\Models\Book;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * UC-020: عرض جميع التقييمات لكتاب محدد
     * GET /api/books/{book_id}/ratings
     */
    public function bookRatings($bookId, Request $request)
    {
        $book = Book::findOrFail($bookId);

        $ratings = $book->ratings()
            ->with('user:id,name,profile_photo')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'average_rating' => round($book->ratings()->avg('stars') ?? 0, 2),
            'total_ratings' => $book->ratings()->count(),
            'data' => $ratings,
        ], 200);
    }

    /**
     * UC-020: إضافة تقييم لكتاب (يجب أن يكون المستخدم قد اشترى الكتاب)
     * POST /api/books/{book_id}/ratings
     * body: { stars: 1-5, comment?: string }
     */
    public function store(StoreRatingRequest $request, $bookId)
    {
        $user = $request->user();

        // التأكد من وجود الكتاب
        $book = Book::findOrFail($bookId);

        // التأكد من أن المستخدم اشترى الكتاب (UC-020 يشترط ذلك)
        $hasPurchased = $user->myBooks()->where('book_id', $bookId)->exists();
        if (!$hasPurchased) {
            return response()->json([
                'success' => false,
                'message' => 'You can only rate books you have purchased.',
            ], 403);
        }

        // منع التكرار: المستخدم لا يستطيع تقييم نفس الكتاب مرتين
        $existingRating = $user->ratings()->where('book_id', $bookId)->first();
        if ($existingRating) {
            // تحديث التقييم الموجود بدل إنشاء جديد
            $existingRating->update([
                'stars' => $request->stars,
                'comment' => $request->comment,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your rating has been updated.',
                'data' => $existingRating->fresh('user', 'book'),
            ], 200);
        }

        // إنشاء تقييم جديد
        $rating = Rating::create([
            'user_id' => $user->id,
            'book_id' => $bookId,
            'stars' => $request->stars,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rating submitted successfully.',
            'data' => $rating->fresh('user', 'book'),
        ], 201);
    }

    /**
     * حذف تقييم (المستخدم يحذف تقييمه الخاص، أو المدير يحذف أي تقييم)
     * DELETE /api/ratings/{id}
     */
    public function destroy(Request $request, $id)
    {
        $rating = Rating::findOrFail($id);
        $user = $request->user();

        // التحقق من الملكية أو صلاحيات الإدارة
        if ($rating->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own ratings.',
            ], 403);
        }

        $rating->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rating deleted successfully.',
        ], 200);
    }
}
