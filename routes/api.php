<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Responses\ApiResponse;
use App\Http\Controllers\Api\AuthController;

Route::get('/stores/{store}/products', [
    StoreController::class,
    'products',
]);

Route::apiResource('stores', StoreController::class)
    ->only([
        'index',
        'show',
    ])
    ->missing(function () {
        return ApiResponse::error(
            message: 'المتجر المطلوب غير موجود',
            status: 404
        );
    });

Route::apiResource('products', ProductController::class)
    ->only([
        'index',
        'show',
    ])
    ->missing(function () {
        return ApiResponse::error(
            message: 'المنتج المطلوب غير موجود',
            status: 404
        );
    });

Route::apiResource('orders', OrderController::class)
    ->only([
        'index',
        'store',
        'show',
    ])
    ->missing(function () {
        return ApiResponse::error(
            message: 'الطلب المطلوب غير موجود',
            status: 404
        );
    });

Route::patch(
    '/orders/{order}/status',
    [OrderController::class, 'updateStatus']
)->missing(function () {
    return ApiResponse::error(
        message: 'الطلب المطلوب غير موجود',
        status: 404
    );
});

Route::patch(
    '/orders/{order}/assign-driver',
    [OrderController::class, 'assignDriver']
)->missing(function () {
    return ApiResponse::error(
        message: 'الطلب المطلوب غير موجود',
        status: 404
    );
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [
        AuthController::class,
        'register',
    ])->middleware('throttle:5,1');

    Route::post('/login', [
        AuthController::class,
        'login',
    ])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [
            AuthController::class,
            'me',
        ]);

        Route::post('/logout', [
            AuthController::class,
            'logout',
        ]);
    });
});

Route::fallback(function (Request $request) {
    return ApiResponse::error(
        message: 'الرابط المطلوب غير موجود',
        status: 404
    );
});
