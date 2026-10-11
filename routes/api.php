<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Responses\ApiResponse;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\StoreController as AdminStoreController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\DriverController as AdminDriverController;

Route::get('/stores/{store}/products', [
    StoreController::class,
    'products',
])->missing(function (Request $request) {
    return ApiResponse::error(
        message: 'المتجر المطلوب غير موجود',
        status: 404
    );
});

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


Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('orders', OrderController::class)
        ->only([
            'index',
            'store',
            'show',
        ])
        ->missing(function (Request $request) {
            return ApiResponse::error(
                message: 'الطلب المطلوب غير موجود',
                status: 404
            );
        });


    Route::patch(
        '/orders/{order}/status',
        [OrderController::class, 'updateStatus']
    )->missing(function (Request $request) {
        return ApiResponse::error(
            message: 'الطلب المطلوب غير موجود',
            status: 404
        );
    });


    Route::patch(
        '/orders/{order}/assign-driver',
        [OrderController::class, 'assignDriver']
    )->missing(function (Request $request) {
        return ApiResponse::error(
            message: 'الطلب المطلوب غير موجود',
            status: 404
        );
    });
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



Route::middleware('auth:sanctum')
    ->prefix('admin')
    ->group(function () {

        // إدارة المتاجر
        Route::get('/stores', [
            AdminStoreController::class,
            'index',
        ]);

        Route::post('/stores', [
            AdminStoreController::class,
            'store',
        ]);

        Route::get('/stores/{storeId}', [
            AdminStoreController::class,
            'show',
        ])->whereNumber('storeId');

        Route::patch('/stores/{storeId}', [
            AdminStoreController::class,
            'update',
        ])->whereNumber('storeId');

        Route::patch('/stores/{storeId}/activate', [
            AdminStoreController::class,
            'activate',
        ])->whereNumber('storeId');

        Route::patch('/stores/{storeId}/deactivate', [
            AdminStoreController::class,
            'deactivate',
        ])->whereNumber('storeId');


        // إدارة منتجات متجر
        Route::get('/stores/{storeId}/products', [
            AdminProductController::class,
            'indexForStore',
        ])->whereNumber('storeId');

        Route::post('/stores/{storeId}/products', [
            AdminProductController::class,
            'store',
        ])->whereNumber('storeId');


        // إدارة المنتجات بصورة منفردة
        Route::get('/products/{productId}', [
            AdminProductController::class,
            'show',
        ])->whereNumber('productId');

        Route::patch('/products/{productId}', [
            AdminProductController::class,
            'update',
        ])->whereNumber('productId');

        Route::patch('/products/{productId}/activate', [
            AdminProductController::class,
            'activate',
        ])->whereNumber('productId');

        Route::patch('/products/{productId}/deactivate', [
            AdminProductController::class,
            'deactivate',
        ])->whereNumber('productId');
    });

// إدارة السائقين
Route::get('/drivers', [
    AdminDriverController::class,
    'index',
]);

Route::get('/drivers/{driverId}', [
    AdminDriverController::class,
    'show',
])->whereNumber('driverId');

Route::patch('/drivers/{driverId}', [
    AdminDriverController::class,
    'update',
])->whereNumber('driverId');


Route::fallback(function (Request $request) {
    return ApiResponse::error(
        message: 'الرابط المطلوب غير موجود',
        status: 404
    );
});
