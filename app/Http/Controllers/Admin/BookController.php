<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     * يدعم pagination: ?page=&per_page=
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 15), 100);
        $books = Book::with(['categories', 'authors'])->latest()->paginate($perPage);
        return BookResource::collection($books);
    }

    /**
     * UC-015: الكتب الأكثر مبيعاً
     * GET /api/books/best-sellers?limit=10
     */
    public function bestSellers(Request $request)
    {
        $limit = min((int) $request->query('limit', 10), 50);

        $books = Book::with(['categories', 'authors'])
            ->withCount([
                'orderItems' => function ($q) {
                    $q->whereHas('order', fn($o) => $o->where('status', 'paid'));
                }
            ])
            ->having('order_items_count', '>', 0)
            ->orderByDesc('order_items_count')
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => BookResource::collection($books),
        ], 200);
    }

    /**
     * UC-018: أحدث الكتب
     * GET /api/books/newest?limit=10
     */
    public function newest(Request $request)
    {
        $limit = min((int) $request->query('limit', 10), 50);

        $books = Book::with(['categories', 'authors'])
            ->latest()
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => BookResource::collection($books),
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request)
    {
        $data = $request->validated();

        // 2. معالجة وتخزين صورة الغلاف (تذهب إلى storage/app/public/books/images)
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('books/images', 'public');
        }

        // 3. معالجة وتخزين ملف الكتاب (يذهب إلى storage/app/private/books — آمن)
        if ($request->hasFile('file_path')) {
            $data['file_path'] = $request->file('file_path')->store('books', 'local');
        }

        // 4. إنشاء الكتاب في قاعدة البيانات بالبيانات المكتملة
        $book = Book::create($data);

        // 5. ربط العلاقات في الجداول الوسيطة
        if ($request->has('category_id')) {
            $book->categories()->sync($request->category_id);
        }
        if ($request->has('author_id')) {
            $book->authors()->sync($request->author_id);
        }

        // 6. إعادة الـ Resource
        return new BookResource($book);
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        $book->load(['authors', 'categories']);
        return new BookResource($book);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        $data = $request->validated();

        // 1. معالجة صورة الغلاف الجديدة (إن وجدت)
        if ($request->hasFile('image')) {
            // حذف الصورة القديمة لتوفير المساحة
            if ($book->image) {
                Storage::disk('public')->delete($book->image);
            }
            $data['image'] = $request->file('image')->store('books/images', 'public');
        }

        // 2. معالجة ملف الكتاب الجديد (إن وجد) — ✅ تم الإصلاح: حذف من local وليس public
        if ($request->hasFile('file_path')) {
            // حذف الملف القديم من نفس الـ disk الذي حُفظ فيه
            if ($book->file_path) {
                Storage::disk('local')->delete($book->file_path);
            }
            $data['file_path'] = $request->file('file_path')->store('books', 'local');
        }

        // 3. تحديث بيانات الكتاب
        $book->update($data);

        // 4. تحديث العلاقات
        if ($request->has('category_id')) {
            $book->categories()->sync($request->category_id);
        }
        if ($request->has('author_id')) {
            $book->authors()->sync($request->author_id);
        }

        return new BookResource($book);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        // 1. حذف صورة الغلاف من الـ disk العام
        if ($book->image) {
            Storage::disk('public')->delete($book->image);
        }

        // 2. حذف ملف الكتاب من الـ disk المحلي (الخاص) — ✅ تم الإصلاح
        if ($book->file_path) {
            Storage::disk('local')->delete($book->file_path);
        }

        // 3. حذف سجل الكتاب من قاعدة البيانات (الـ soft deletes مفعّل)
        $book->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الكتاب والملفات المرتبطة به بنجاح.'
        ], 200);
    }

    /**
     * UC-007: عرض مكتبة المستخدم (الكتب المشتراة)
     * GET /api/my-library
     */
    public function myLibrary(Request $request)
    {
        $myBooks = Book::with(['categories', 'authors'])
            ->whereHas('myBooks', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'books_count' => $myBooks->count(),
            'data' => $myBooks
        ], 200);
    }
}
