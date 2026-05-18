<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email', // الإيميل مطلوب ولازم يكون موجود بالداتابيز
            'otp_code' => 'required|string|size:6', // كود الـ OTP مطلوب ولازم يكون من 6 خانات
        ];
    }
}
