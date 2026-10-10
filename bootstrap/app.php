<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Exceptions\OrderStatusTransitionException;
use App\Exceptions\DriverAssignmentException;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (
            ValidationException $e,
            Request $request
        ) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: 'البيانات المرسلة غير صحيحة',
                errors: $e->errors(),
                status: 422
            );
        });

        //الانتقالات الممنوعة لحالة الطلب
        $exceptions->render(function (
            OrderStatusTransitionException $e,
            Request $request
        ) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: $e->getMessage(),
                errors: [
                    'status' => [$e->getMessage()],
                ],
                status: 409
            );
        });

        //اخطاء تعيين السائق للطلب
        $exceptions->render(function (
            DriverAssignmentException $e,
            Request $request
        ) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: $e->getMessage(),
                errors: [
                    'assignment' => [$e->getMessage()],
                ],
                status: 409
            );
        });


        $exceptions->render(function (
            AuthenticationException $e,
            Request $request
        ) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: 'يجب تسجيل الدخول أولاً',
                status: 401,
            );
        });
    })->create();
