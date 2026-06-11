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
     */

    public function index()
    {
        $books = Book::with(['categories', 'authors'])->get();
        return BookResource::collection($books);
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
        // 1. جلب البيانات التي تم فحصها وتمريرها (بدون الملفات بعد)
        $data = $request->validated();

        // 2. معالجة وتخزين صورة الغلاف (تذهب إلى storage/app/public/books/images)
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('books/images', 'public');
        }

        // 3. معالجة وتخزين ملف الكتاب (تذهب إلى storage/app/public/books/files)
        if ($request->hasFile('file_path')) {
            $data['file_path'] = $request->file('file_path')->store('books', 'public');
        }

        // 4. إنشاء الكتاب في قاعدة البيانات بالبيانات المكتملة
        $book = Book::create($data);

        // 5. ربط العلاقات في الجداول الوسيطة
        $book->categories()->sync($request->category_id);
        $book->authors()->sync($request->author_id);

        // 6. إعادة الـ Resource
        return new BookResource($book);
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book) // لاحظ أننا كتبنا اسم الموديل قبل المتغير
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

        // 2. معالجة ملف الكتاب الجديد (إن وجد)
        if ($request->hasFile('file_path')) {
            // حذف الملف القديم
            if ($book->file_path) {
                Storage::disk('public')->delete($book->file_path);
            }
            $data['file_path'] = $request->file('file_path')->store('books/files', 'public');
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
        // 1. حذف صورة الغلاف من مجلد التخزين العام إذا كانت موجودة
        if ($book->image) {
            Storage::disk('public')->delete($book->image);
        }

        // 2. حذف ملف الـ PDF من مجلد التخزين إذا كان موجوداً
        if ($book->file_path) {
            Storage::disk('public')->delete($book->file_path);
        }

        // 3. حذف سجل الكتاب من قاعدة البيانات
        $book->delete();

        // 4. إرجاع استجابة نجاح عملية الحذف
        return response()->json([
            'message' => 'تم حذف الكتاب والملفات المرتبطة به بنجاح.'
        ], 200);
    }
    public function myLibrary(Request $request)
    {

        $myBooks = Book::whereHas('orders', function ($query) {

            $query->whereIn('order_id', function ($subQuery) {
                $subQuery->select('id')
                    ->from('orders')
                    ->where('user_id', auth()->id());
            });
        })->get();

        return response()->json([
            'success' => true,
            'books_count' => $myBooks->count(),
            'data' => $myBooks
        ], 200);
    }
}
