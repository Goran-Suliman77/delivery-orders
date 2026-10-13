<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Http\Responses\ApiResponse;
use App\Contracts\ProductQueryServiceInterface;
use App\Http\Requests\ProductIndexRequest;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{

    public function __construct(
        private readonly ProductQueryServiceInterface $productQueries,
    ) {}
    public function index(
        ProductIndexRequest $request
    ): JsonResponse {
        $products = $this->productQueries->paginatePublic(
            $request->validated()
        );
        $message = $products->total() > 0
            ? 'تم جلب المنتجات بنجاح'
            : 'لا توجد منتجات متاحة تطابق معايير البحث';

        return ApiResponse::paginated(
            $products,
            fn($product) => (new ProductResource($product))
                ->toArray($request),
            $message
        );
    }

    public function show(
        Request $request,
        int $product
    ): JsonResponse {
        $productModel = $this->productQueries
            ->findPublicById($product);

        if (! $productModel) {
            return ApiResponse::error(
                message: 'المنتج المطلوب غير موجود أو غير متاح',
                status: 404
            );
        }
        return ApiResponse::success(
            message: 'تم جلب بيانات المنتج بنجاح',
            data: (new ProductResource($productModel))
                ->toArray($request)
        );
    }
}
