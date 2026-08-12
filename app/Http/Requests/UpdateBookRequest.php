<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'isbn' => 'sometimes|string|unique:books,isbn,' . $this->book?->id,
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'publish_date' => 'sometimes|date',
            'language' => 'sometimes|in:arabic,english',
            'file_type' => 'sometimes|in:pdf,epub,mp3,audio',
            'file_path' => 'nullable|file|mimes:pdf,epub|max:20480',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'audio_file' => 'nullable|file|mimes:mp3,m4a,wav,aac|max:102400',
            'audio_sample' => 'nullable|file|mimes:mp3,m4a,wav,aac|max:20480',
            'duration' => 'nullable|string|max:25',
            'category_id' => 'sometimes|array',
            'category_id.*' => 'integer|exists:categories,id',
            'author_id' => 'sometimes|array',
            'author_id.*' => 'integer|exists:authors,id',
        ];
    }
}