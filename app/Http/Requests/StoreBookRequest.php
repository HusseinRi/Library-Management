<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'isbn' => 'required|string|unique:books,isbn',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'publish_date' => 'required|date',
            'language' => 'required|in:arabic,english',

            // نوع الملف الرئيسي (PDF, EPUB, MP3, إلخ)
            'file_type' => 'required|in:pdf,epub,mp3,audio',

            // جعل المستند اختیاري في حال كان الكتاب صوتياً فقط
            'file_path' => 'nullable|file|mimes:pdf,epub|max:20480',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',

            // 👈 حقول الكتب الصوتية الجديدة
            'audio_file' => 'nullable|file|mimes:mp3,m4a,wav,aac|max:102400', // حد أقصى 100 ميجابايت
            'audio_sample' => 'nullable|file|mimes:mp3,m4a,wav,aac|max:20480',  // عينة مجانية
            'duration' => 'nullable|string|max:25',                         // مثال: "01:25:40"

            // العلاقات
            'category_id' => 'required|array',
            'category_id.*' => 'integer|exists:categories,id',

            'author_id' => 'required|array',
            'author_id.*' => 'integer|exists:authors,id',
        ];
    }
}