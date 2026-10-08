<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse
{
    public static function success(
        string $message,
        mixed $data = null,
        int $status = 200,
        array $extra = []
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            ...$extra,
        ], $status);
    }

    public static function error(
        string $message,
        mixed $errors = null,
        int $status = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    public static function paginated(
        LengthAwarePaginator $paginator,
        callable $transform,
        string $message = 'تم جلب البيانات بنجاح'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,

            'data' => collect($paginator->items())
                ->map($transform)
                ->values(),

            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
