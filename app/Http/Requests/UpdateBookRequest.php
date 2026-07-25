<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // 1. استخراج المتغير سواء كان البارامتر book أو id
        $book = $this->route('book') ?? $this->route('id');

        // 2. استخراج الـ ID سواء كان الممرر كائن (Model) أو مجرد رقم
        $bookId = is_object($book) ? $book->id : $book;

        // 3. كخيار احتياطي أخير (Fallback)، نأخذ الرقم مباشرة من الرابط (api/books/2)
        if (!$bookId) {
            $bookId = $this->segment(3);
        }

        return [
            'title'        => 'sometimes|required|string|max:255',
            'isbn'         => ['sometimes', 'required', 'string', Rule::unique('books', 'isbn')->ignore($bookId)],
            'description'  => 'sometimes|nullable|string',
            'price'        => 'sometimes|required|numeric|min:0',
            'publish_date' => 'sometimes|required|date',

            // ✅ حقول جديدة
            'language'     => 'sometimes|required|in:arabic,english',
            'file_type'    => 'sometimes|required|in:pdf,epub',

            'file_path'    => 'sometimes|file|mimes:pdf,epub|max:10240',
            'image'        => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:2048',

            'category_id'    => 'sometimes|required|array',
            'category_id.*'  => 'integer|exists:categories,id',
            'author_id'    => 'sometimes|required|array',
            'author_id.*'  => 'integer|exists:authors,id',
        ];
    }
}
