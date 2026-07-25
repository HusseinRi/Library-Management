<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // تأكد أنها true دائماً لتسمح بمرور الطلب
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'        => 'required|string|max:255',
            'isbn'         => 'required|string|unique:books,isbn',
            'description'  => 'nullable|string',
            'price'        => 'required|numeric|min:0',
            'publish_date' => 'required|date',

            // ✅ حقول جديدة مطلوبة (تطابق الـ migration)
            'language'     => 'required|in:arabic,english',
            'file_type'    => 'required|in:pdf,epub',

            // ملف الكتاب: ندعم PDF و EPUB الآن
            'file_path'    => 'required|file|mimes:pdf,epub|max:10240',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',

            // 1. التحقق من الأقسام
            'category_id'    => 'required|array',
            'category_id.*'  => 'integer|exists:categories,id',

            // 2. التحقق من المؤلفين
            'author_id'    => 'required|array',
            'author_id.*'  => 'integer|exists:authors,id',
        ];
    }
}