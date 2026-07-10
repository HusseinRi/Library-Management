<?php

namespace App\Http\Controllers;

use App\Models\Book; // تأكد من استدعاء موديل الكتب الخاص بك هنا
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class BookFileController extends Controller
{
    public function streamBook($bookId)
    {
        // 1. جلب بيانات الكتاب أولاً للتأكد من وجوده في النظام
        $book = Book::find($bookId);
        if (!$book) {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، هذا الكتاب غير موجود في النظام.'
            ], 404);
        }


        $hasOwnership = \DB::table('my_books')
            ->where('user_id', Auth::id())
            ->where('book_id', $bookId)
            ->exists();

        if (!$hasOwnership) {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، لا تملك صلاحية الوصول لقراءة هذا الكتاب. يرجى شرائه أولاً.'
            ], 403);
        }

        // 3. جلب مسار الملف المخزن ديناميكياً بعد قفل الحماية بنجاح
        $fileName = $book->file_path;

        // 4. التحقق من وجود الملف الفعلي على الهارد ديسك
        if (!$fileName || !Storage::disk('local')->exists($fileName)) {
            return response()->json([
                'success' => false,
                'message' => 'ملف الكتاب غير موجود على السيرفر.'
            ], 404);
        }

        // 5. إرسال الملف كـ Stream آمن
        $filePath = Storage::disk('local')->path($fileName);

        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $book->title . '.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ];

        return response()->file($filePath, $headers);
    }
}