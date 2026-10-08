<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Responses\ApiResponse;

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
Route::fallback(function (Request $request) {
    return ApiResponse::error(
        message: 'الرابط المطلوب غير موجود',
        status: 404
    );
});
