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
            'title' => 'sometimes|required|string|max:255',
            'isbn' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('books', 'isbn')->ignore($bookId)
            ],
            'category_id' => 'sometimes|required|exists:categories,id',
            'author_id' => 'sometimes|required|exists:authors,id',
            'total_copies' => 'sometimes|integer|min:1',
        ];
    }
}
