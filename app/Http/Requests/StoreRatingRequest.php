<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'stars'   => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'stars.required' => 'Rating is required.',
            'stars.integer'  => 'Rating must be a number.',
            'stars.min'      => 'Rating must be at least 1 star.',
            'stars.max'      => 'Rating cannot exceed 5 stars.',
            'comment.max'    => 'Comment cannot exceed 1000 characters.',
        ];
    }
}
