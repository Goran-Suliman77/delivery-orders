<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderReportRequest;
use App\Http\Responses\ApiResponse;
use App\Services\OrderStatisticsService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function orders(
        OrderReportRequest $request,
        OrderStatisticsService $statistics
    ): JsonResponse {
        // لا يكفي تسجيل الدخول؛ نتحقق من صلاحية التقارير.
        $this->authorize('viewReports');

        $filters = $request->validated();

        $report = $statistics->report(
            from: $filters['from'] ?? null,
            to: $filters['to'] ?? null,
            storeId: isset($filters['store_id'])
                ? (int) $filters['store_id']
                : null,
        );

        return ApiResponse::success(
            message: 'تم جلب تقرير الطلبات بنجاح',
            data: $report
        );
    }
}
