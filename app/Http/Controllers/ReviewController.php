<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Http\Requests\ReviewRequest;
use App\Models\Book;
use App\Models\Rating;
use App\Http\Requests\RateRequest;
use App\Models\MyBook;

class ReviewController extends Controller
{
    public function addreview(ReviewRequest $request)
    {
        $validated = $request->validated();
        $userId = auth()->id();
        $ispurches = MyBook::where('user_id', $userId)
                                ->where('book_id', $validated['book_id'])
                                ->first();
        if(!$ispurches){
             return response()->json([
                'status' => 'error',
                'message' => 'You have not been purches this book.'
            ], 400);
        }
        // 1. Double check if the user already reviewed this book
        $existingReview = Review::where('user_id', $userId)
                                ->where('book_id', $validated['book_id'])
                                ->first();

        if ($existingReview) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already submitted a review for this book.'
            ], 409);
        }

          $review = Review::create([
                'user_id' => $userId,
                'book_id' => $validated['book_id'],
                'comment' => $validated['comment'] ?? null,
            ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Review added successfully!',
            'data'    => $review
        ], 201);
    }

    public function rateBook(RateRequest $request){
                $validated = $request->validated();
        $userId = auth()->id();
        $ispurches = MyBook::where('user_id', $userId)
                                ->where('book_id', $validated['book_id'])
                                ->first();
        if(!$ispurches){
             return response()->json([
                'status' => 'error',
                'message' => 'You have not been purches this book.'
            ], 400);
        }
        $existingRate = Rating::where('user_id', $userId)
                                ->where('book_id', $validated['book_id'])
                                ->first();
      if ($existingRate) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already submitted a rate for this book.'
            ], 409);
        }
        $rate = Rating::create([
                'user_id' => $userId,
                'book_id' => $validated['book_id'],
                'stars' => $validated['rate'] ?? null,
            ]);
        $book = Book::findOrFail($validated['book_id']);
        $averageRating = $book->ratings()->avg('stars');
        $book->update([
                'rating' => round($averageRating, 1)
        ]);
        return response()->json([
            'status'  => 'success',
            'message' => 'rate added successfully!',
            'data'    => $rate
        ], 201);
    }

    public function getBookReviews($bookId)
    {
        $book = Book::findOrFail($bookId);

        $reviews = $book->reviews()
                        ->with('user:id,name') // Only pulls user ID and name string for safety
                        ->latest()
                        ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $reviews->items(),
            'meta'   => [
                'total_reviews' => $reviews->total(),
            ]
        ], 200);
    }


}
