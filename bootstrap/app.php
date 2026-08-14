<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ✅ FIX: منع Laravel من محاولة البحث عن route('login') للـ API requests.
        //    الـ Authenticate middleware يستدعي route('login') قبل أن يصل الاستثناء
        //    لمعالج الاستثناءات، مما يُسبب RouteNotFoundException.
        //    الحل: نُعرّف redirectGuestsTo بحيث لا تُستدعى route() للـ API.
        $middleware->redirectGuestsTo(function (Request $request): ?string {
            // للـ API requests: لا نُعيد URL redirect بل null
            // هذا يُرجع AuthenticationException بشكل نظيف لمعالج الاستثناءات
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }
            // للـ web routes فقط (إن وُجدت): نُعيد null أيضاً لأنه لا يوجد web login
            return null;
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ✅ معالج استثناءات موحّد يُرجع JSON دائماً للـ API requests.
        //    هذا يمنع Laravel من إرجاع HTML view (debug page) عند حدوث خطأ،
        //    مما يُربك الـ frontend (React) الذي يتوقع JSON.

        // ✅ FIX (السبب الجذري للـ 500): معالج مخصص لـ AuthenticationException.
        //    بدلاً من محاولة redirect إلى route('login') (التي قد لا تكون موجودة)،
        //    نُرجع JSON 401 مباشرةً للـ API requests.
        //    الـ frontend (http.ts interceptor) سيعالج الـ 401 بإعادة توجيه المستخدم لصفحة الدخول.
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please log in to access this resource.',
                ], 401);
            }
            // للطلبات غير الـ API (نادر في مشروعنا): اترك السلوك الافتراضي
            return null;
        });

        // ✅ معالج عام لكل الأخطاء الأخرى
        $exceptions->report(function (\Throwable $e): void {
            // السجل يُكتب تلقائياً في storage/logs/laravel.log
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            // 1. تجاهل الطلبات غير المتجهة للـ API (متصفح عادي يصل للـ /up مثلاً)
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null; // يُترك لـ Laravel default rendering
            }

            // 2. تحديد status code
            $status = 500;
            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
            } elseif (method_exists($e, 'getStatusCode')) {
                $status = $e->getStatusCode();
            } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                $status = 422;
            } elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
                $status = 401;
            } elseif ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                $status = 403;
            } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                $status = 404;
            }

            // 3. بناء رسالة الخطأ
            $response = [
                'success' => false,
                'message' => $e->getMessage() ?: 'Internal server error.',
            ];

            // 4. إضافة تفاصيل validation errors (لـ 422)
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $response['errors'] = $e->errors();
            }

            // 5. في وضع التطوير (debug mode)، نُرجع تفاصيل إضافية تساعد على التشخيص
            if (config('app.debug')) {
                $response['debug'] = [
                    'exception' => get_class($e),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                    'trace'     => collect($e->getTrace())
                        ->take(10)
                        ->map(fn ($frame) => [
                            'file' => $frame['file'] ?? null,
                            'line' => $frame['line'] ?? null,
                            'function' => $frame['function'] ?? null,
                        ])
                        ->toArray(),
                ];
            }

            // 6. إخفاء الرسائل الحساسة في الإنتاج
            if (! config('app.debug') && $status >= 500) {
                $response['message'] = 'Internal server error. Please try again later.';
            }

            return response()->json($response, $status);
        });
    })->create();
