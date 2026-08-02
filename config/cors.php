<?php

/**
 * ✅ Phase 2 — إعدادات CORS
 * ─────────────────────────────────────────────
 * يسمح لتطبيقات الـ Frontend (React dashboard + Mobile app) بالوصول إلى الـ API
 * من origins مختلفة.
 *
 * في الـ development:
 *   - Vite dev server يعمل على http://localhost:3000 (مع proxy /api → :8000)
 *   - الـ proxy يحوّل الطلبات داخلياً، لذا CORS غير ضروري في dev mode.
 *
 * في الـ production:
 *   - ضع VITE_API_URL في الـ frontend لتوجيه الطلبات مباشرةً لـ backend URL.
 *   - أضف domain الـ frontend إلى SANCTUM_STATEFUL_DOMAINS في .env.
 */

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    // ✅ الأorigins المسموح بها — تُقرأ من env مع قيم افتراضية للـ dev
    'allowed_origins' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:5173,http://127.0.0.1:3000,http://127.0.0.1:5173,http://localhost:3001')))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // ✅ مهم: false لأننا نستخدم Bearer tokens وليس cookies
    //    (لو احتجت session-based auth مستقبلاً، غيّرها إلى true)
    'supports_credentials' => false,

];
