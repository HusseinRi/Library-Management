<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReadingProgress;
use Illuminate\Support\Facades\Auth;

class ReadingProgressController extends Controller
{
    // 1. دالة حفظ وتحديث الصفحة الحالية
    public function updateProgress(Request $request)
    {
        // 1. التحقق من الحقول المطلوبة بناءً على قواعد جدولك
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'last_page' => 'required|integer|min:1',
            'total_pages' => 'required|integer|min:1',
        ]);

        // 2. حساب النسبة المئوية للتقدم تلقائياً لتخزينها في حقل progress_percent الإجباري
        $progressPercent = min(100, round(($request->last_page / $request->total_pages) * 100));

        // 3. الحفظ أو التحديث بأمان
        $progress = ReadingProgress::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'book_id' => $request->book_id,
            ],
            [
                'last_page' => $request->last_page,
                'total_pages' => $request->total_pages,
                'progress_percent' => $progressPercent,
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ تقدم القراءة بنجاح.',
            'data' => $progress
        ], 200);
    }
    public function getProgress($bookId)
    {
        // البحث عن سجل القراءة الخاص بالمستخدم الحالي والكتاب المحدد
        $progress = ReadingProgress::where('user_id', Auth::id())
            ->where('book_id', $bookId)
            ->first();


        if (!$progress) {
            return response()->json([
                'success' => true,
                'message' => 'أول مرة يتم فيها فتح الكتاب.',
                'data' => [
                    'book_id' => (int) $bookId,
                    'last_page' => 1,          // القيمة الافتراضية لتبدأ القراءة من الصفحة الأولى
                    'progress_percent' => 0    // نسبة التقدم صفر
                ]
            ], 200);
        }


        return response()->json([
            'success' => true,
            'message' => 'تم استرجاع تقدم القراءة بنجاح.',
            'data' => $progress
        ], 200);
    }
}