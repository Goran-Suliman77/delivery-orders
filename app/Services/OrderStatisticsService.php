<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;

class OrderStatisticsService
{
    /**
     * ملخص الطلبات النشطة حاليًا.
     *
     * يمكن تحديد متجر معين، أو ترك القيمة null
     * للحصول على الإحصائيات لجميع المتاجر.
     */
    public function activeSummary(?int $storeId = null): array
    {
        $query = Order::query()->active();

        if ($storeId !== null) {
            $query->forStore($storeId);
        }

        $byStatus = $query
            ->select('status')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn($total) => (int) $total);

        return [
            'active_total' => (int) $byStatus->sum(),
            'by_status' => $byStatus->all(),
        ];
    }

    /**
     * تقرير الطلبات خلال فترة زمنية محددة.
     */
    public function report(
        ?string $from = null,
        ?string $to = null,
        ?int $storeId = null
    ): array {
        $today = CarbonImmutable::today();

        // افتراضيًا: آخر 30 يومًا، شاملًا اليوم.
        if ($from === null && $to === null) {
            $from = $today->subDays(29)->toDateString();
            $to = $today->toDateString();
        } elseif ($from !== null && $to === null) {
            $to = $today->toDateString();
        } elseif ($from === null && $to !== null) {
            $from = CarbonImmutable::parse($to)
                ->subDays(29)
                ->toDateString();
        }

        $start = CarbonImmutable::parse($from)->startOfDay();

        // نستخدم بداية اليوم التالي كحد غير شامل.
        $endExclusive = CarbonImmutable::parse($to)
            ->addDay()
            ->startOfDay();

        $baseQuery = Order::query()
            ->createdBetween($start, $endExclusive);

        if ($storeId !== null) {
            $baseQuery->forStore($storeId);
        }

        // حساب عدد الطلبات حسب الحالة.
        $statusRows = (clone $baseQuery)
            ->select('status')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // نعيد جميع الحالات المعروفة، حتى لو كان عددها صفرًا.
        $byStatus = [];

        foreach (OrderStatus::cases() as $status) {
            $byStatus[$status->value] = 0;
        }

        foreach ($statusRows as $status => $count) {
            $byStatus[(string) $status] = (int) $count;
        }

        $totalOrders = array_sum($byStatus);

        // الطلبات المنشأة خلال الفترة وما زالت نشطة الآن.
        $activeOrders = (clone $baseQuery)
            ->active()
            ->count();

        $deliveredOrders = $byStatus[OrderStatus::Delivered->value] ?? 0;

        $cancelledOrders = $byStatus[OrderStatus::Cancelled->value] ?? 0;

        // مجموع قيمة الطلبات المسلّمة.
        $deliveredOrderValue = (clone $baseQuery)
            ->status(OrderStatus::Delivered)
            ->sum('total');

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],

            'filters' => [
                'store_id' => $storeId,
            ],

            'summary' => [
                'total_orders' => $totalOrders,
                'active_orders' => $activeOrders,
                'delivered_orders' => $deliveredOrders,
                'cancelled_orders' => $cancelledOrders,

                'delivered_order_value' => number_format(
                    (float) $deliveredOrderValue,
                    2,
                    '.',
                    ''
                ),
            ],

            'by_status' => $byStatus,
        ];
    }
}
