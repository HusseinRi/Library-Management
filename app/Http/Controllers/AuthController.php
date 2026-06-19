<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail; // 👈 استدعاء كلاس المراسلة الأساسي
use App\Mail\SendOtpMail;             // 👈 استدعاء كلاس الإيميل الذي أنشأناه

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        // 1. معالجة وتخزين الصورة الشخصية
        $profilePhoto = $request->hasFile('profile_photo')
            ? $request->file('profile_photo')->store('profile_photos', 'public')
            : null;

        // 2. توليد رمز OTP عشوائي من 6 أرقام
        $otp = rand(100000, 999999);

        // 3. إنشاء المستخدم وحفظ حقول الـ OTP مباشرة
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user',
            'profile_photo' => $profilePhoto,
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        // 🔥 4. إرسال الإيميل الحقيقي باستخدام صفحة الـ Blade المنسقة
        Mail::to($user->email)->send(new SendOtpMail($otp));

        // 5. إرجاع الرد للفرونت إيند بدون توكن دخول (لحين تفعيل الحساب)
        return response()->json([
            'message' => 'Registration successful. Please check your email for the OTP verification code.',
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        // 1. البحث عن المستخدم بواسطة الإيميل
        $user = User::where('email', $request->email)->first();

        // 2. التحقق من وجود المستخدم وصحة كلمة المرور
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // 🔥 3. جدار الحماية: منع الدخول إذا كان البريد الإلكتروني غير مؤكد بعد
        if ($user->email_verified_at === null) {
            return response()->json([
                'message' => 'Your email address is not verified. Please verify your account first.'
            ], 403);
        }

        // 4. إذا كان الحساب مؤكداً، نولد التوكن ونسمح بالدخول
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => new UserResource($user)
        ], 200);
    }

    /**
     * 🔥 تابع التحقق من كود الـ OTP وتفعيل الحساب
     */
    public function verifyEmail(VerifyOtpRequest $request)
    {
        // 1. جلب المستخدم المطلوب
        $user = User::where('email', $request->email)->first();

        // 2. الفحص الأول: مطابقة الكود (التحويل لـ string ضروري لتفادي مشكلة SQLite)
        if ((string)$user->otp_code !== (string)$request->otp_code) {
            return response()->json(['message' => 'The provided OTP code is incorrect.'], 422);
        }

        // 3. الفحص الثاني: صلاحية الوقت
        if (now()->isAfter($user->otp_expires_at)) {
            return response()->json(['message' => 'This OTP code has expired. Please request a new one.'], 422);
        }

        // 🔥 4. التحديث المباشر لتخطي الـ Mass Assignment Protection
        $user->email_verified_at = now();
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save(); // 👈 حفظ إجباري مباشر في قاعدة البيانات

        // 5. توليد توكن الدخول
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Email verified successfully. You are now logged in.',
            'token' => $token,
            'user' => new UserResource($user),
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    /**
     * 1. إرسال رمز استعادة كلمة المرور
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // توليد OTP جديد وصلاحية جديدة
        $otp = rand(100000, 999999);

        // 👈 استخدام الحفظ المباشر لتفادي حظر الـ fillable
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        // إعادة استخدام نفس كلاس الإيميل وصفحة الـ Blade
        Mail::to($user->email)->send(new SendOtpMail($otp));

        return response()->json([
            'message' => 'A password reset code has been sent to your email.'
        ], 200);
    }

    /**
     * 2. الفحص المبدئي للـ OTP
     */
    public function verifyResetOtp(VerifyOtpRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // مطابقة الكود (التحويل لـ string لتفادي مشكلة SQLite)
        if ((string)$user->otp_code !== (string)$request->otp_code) {
            return response()->json(['message' => 'The provided OTP code is incorrect.'], 422);
        }

        // فحص الوقت
        if (now()->isAfter($user->otp_expires_at)) {
            return response()->json(['message' => 'This OTP code has expired.'], 422);
        }

        return response()->json([
            'message' => 'OTP code is valid. You can now reset your password.'
        ], 200);
    }

    /**
     * 3. حفظ كلمة المرور الجديدة وتصفير الرموز
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // فحص أمان أخير للتأكد من الكود والوقت (التحويل لـ string لتفادي مشكلة SQLite)
        if ((string)$user->otp_code !== (string)$request->otp_code || now()->isAfter($user->otp_expires_at)) {
            return response()->json(['message' => 'Invalid or expired OTP code.'], 422);
        }

        // 👈 تحديث مباشر آمن وحفظ إجباري، مع تفعيل الإيميل تلقائياً إن لم يكن مفعلاً
        $user->password = Hash::make($request->password);
        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        return response()->json([
            'message' => 'Password has been reset successfully. You can now login with your new password.'
        ], 200);
    }
}
