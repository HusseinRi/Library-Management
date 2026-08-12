<?php

namespace App\Http\Controllers;

use App\Models\Book; // تأكد من استدعاء موديل الكتب الخاص بك هنا
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;


class BookFileController extends Controller
{
    /**
     * بث ملف الـ PDF (خاص للمشترين)
     */
    public function streamBook($bookId)
    {
        $book = Book::find($bookId);
        if (!$book) {
            return response()->json(['success' => false, 'message' => 'الكتاب غير موجود.'], 404);
        }

        $hasOwnership = \DB::table('my_books')
            ->where('user_id', Auth::id())
            ->where('book_id', $bookId)
            ->exists();

        if (!$hasOwnership) {
            return response()->json(['success' => false, 'message' => 'يرجى شراء الكتاب أولاً.'], 403);
        }

        $fileName = $book->file_path;
        if (!$fileName || !Storage::disk('local')->exists($fileName)) {
            return response()->json(['success' => false, 'message' => 'ملف الكتاب غير موجود على السيرفر.'], 404);
        }

        $filePath = Storage::disk('local')->path($fileName);

        $mimeType = mime_content_type($filePath) ?: 'application/pdf';

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $book->title . '.' . pathinfo($filePath, PATHINFO_EXTENSION) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * بث الكتاب الصوتي الكامل (خاص للمشترين فقط)
     */
    public function streamAudio($bookId)
    {
        $book = Book::find($bookId);
        if (!$book) {
            return response()->json(['success' => false, 'message' => 'الكتاب الصوتي غير موجود.'], 404);
        }

        // تحقق الملكية
        $hasOwnership = \DB::table('my_books')
            ->where('user_id', Auth::id())
            ->where('book_id', $bookId)
            ->exists();

        if (!$hasOwnership) {
            return response()->json(['success' => false, 'message' => 'يرجى شراء الكتاب الصوتي للاستماع إليه.'], 403);
        }

        $audioFile = $book->audio_path;
        if (!$audioFile || !Storage::disk('local')->exists($audioFile)) {
            return response()->json(['success' => false, 'message' => 'الملف الصوتي غير موجود على السيرفر.'], 404);
        }

        $filePath = Storage::disk('local')->path($audioFile);
        $mimeType = mime_content_type($filePath) ?: 'audio/mpeg';

        // response()->file تدعم تلقائياً الـ HTTP Range Headers لتمرير مقطع الصوت
        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $book->title . '.' . pathinfo($filePath, PATHINFO_EXTENSION) . '"',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    /**
     * بث العينة الصوتية المجانية (عام للجميع دون شراء)
     */
    public function streamAudioSample($bookId)
    {
        $book = Book::find($bookId);
        if (!$book || !$book->audio_sample_path) {
            return response()->json(['success' => false, 'message' => 'المقطع الصوتي التجريبي غير متوفر.'], 404);
        }

        if (!Storage::disk('public')->exists($book->audio_sample_path)) {
            return response()->json(['success' => false, 'message' => 'الملف التجريبي غير موجود على السيرفر.'], 404);
        }

        $filePath = Storage::disk('public')->path($book->audio_sample_path);
        $mimeType = mime_content_type($filePath) ?: 'audio/mpeg';

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
        ]);
    }
}