<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFavoriteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'book_id' => 'required|integer|exists:books,id',
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => 'Book ID is required.',
            'book_id.exists' => 'The selected book does not exist.',
        ];
    }
}
