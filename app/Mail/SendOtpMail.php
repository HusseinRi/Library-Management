<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp; // تعريف متغير الكود ليقرأه قالب الإيميل

    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Account Verification Code (OTP)', // عنوان الرسالة
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otpUser', // 👈 تحديد مسار ملف الـ Blade داخل مجلد views
            with: [
                'otp' => $this->otp, // 👈 تمرير المتغير لتتم قراءته داخل الـ Blade بـ {{ $otp }}
            ],
        );
    }
}