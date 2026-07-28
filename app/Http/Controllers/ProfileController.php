<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * UC-004: عرض بيانات المستخدم الحالي
     * GET /api/profile
     */
    public function show(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()),
        ], 200);
    }

    /**
     * UC-004: تحديث البيانات الشخصية
     * PUT /api/profile
     * body: { name, phone?, profile_photo?, preferred_language?, preferred_theme? }
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'               => ['sometimes', 'string', 'max:255'],
            'phone'              => ['sometimes', 'nullable', 'string', 'max:20'],
            'profile_photo'      => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'preferred_language' => ['sometimes', 'string', Rule::in(['ar', 'en'])],
            'preferred_theme'    => ['sometimes', 'string', Rule::in(['light', 'dark'])],
        ]);

        // معالجة رفع الصورة الشخصية
        if ($request->hasFile('profile_photo')) {
            // حذف الصورة القديمة
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $validated['profile_photo'] = $request->file('profile_photo')
                ->store('profile_photos', 'public');
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => new UserResource($user->fresh()),
        ], 200);
    }

    /**
     * تغيير كلمة المرور (للمستخدم المسجّل دخول)
     * PATCH /api/profile/password
     * body: { current_password, new_password, new_password_confirmation }
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password'          => ['required', 'string'],
            'new_password'              => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Current password is required.',
            'new_password.required'     => 'New password is required.',
            'new_password.min'          => 'New password must be at least 8 characters.',
            'new_password.confirmed'    => 'Password confirmation does not match.',
        ]);

        // التحقق من كلمة المرور الحالية
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
                'errors'  => ['current_password' => ['The provided password does not match our records.']],
            ], 422);
        }

        // منع استخدام نفس كلمة المرور
        if (Hash::check($validated['new_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'New password must be different from the current one.',
                'errors'  => ['new_password' => ['You cannot use the same password.']],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        // إلغاء جميع الجلسات الأخرى (إجبار إعادة الدخول) — اختياري
        // $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ], 200);
    }
}
