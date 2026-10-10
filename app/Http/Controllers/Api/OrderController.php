<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderListResource;
use App\Http\Resources\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Services\OrderService;
use App\Http\Requests\OrderIndexRequest;
use App\Enums\OrderStatus;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Services\OrderStatusService;
use App\Http\Requests\AssignDriverRequest;
use App\Services\DriverAssignmentService;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    /**
     * Display active orders.
     */
    public function index(OrderIndexRequest $request)
    {
        $validated = $request->validated();
        $orders = Order::query()
            ->active()

            ->select([
                'id',
                'customer_id',
                'store_id',
                'driver_id',
                'status',
                'created_at',
            ])

            ->with([
                'customer:id,name',
                'store:id,name',
                'driver:id,user_id',
                'driver.user:id,name',
            ])

            ->withCount('items')

            ->when(
                !empty($validated['status']),
                fn($query) =>
                $query->status(
                    OrderStatus::from($validated['status'])
                )
            )

            ->when(
                !empty($validated['store_id']),
                fn($query) =>
                $query->forStore(
                    (int) $validated['store_id']
                )
            )

            ->when(
                !empty($validated['driver_id']),
                fn($query) =>
                $query->forDriver(
                    (int) $validated['driver_id']
                )
            )

            ->when(
                !empty($validated['customer_id']),
                fn($query) =>
                $query->forCustomer(
                    (int) $validated['customer_id']
                )
            )

            ->when(
                !empty($validated['without_driver']),
                fn($query) =>
                $query->withoutDriver()
            )

            ->when(
                !empty($validated['today']),
                fn($query) =>
                $query->today()
            )

            ->when(
                !empty($validated['older_than_minutes']),
                fn($query) =>
                $query->olderThanMinutes(
                    (int) $validated['older_than_minutes']
                )
            )

            ->latest('id')
            ->paginate(
                (int) ($validated['per_page'] ?? 50)
            );

        $message = 'تم جلب الطلبات بنجاح';

        if ($orders->total() === 0) {
            if (($validated['today'] ?? false) === true) {
                $message = 'لا توجد طلبات اليوم';
            } else {
                $message = 'لا توجد طلبات اليوم';
            }
        } elseif ($orders->isEmpty()) {
            $message = 'لا توجد طلبات في هذه الصفحة. جرّب صفحة أخرى.';
        }

        return ApiResponse::paginated(
            $orders,
            fn($order) => (new OrderListResource($order))
                ->toArray($request),
            $message
        );
    }

    /**
     * Create a new order.
     */
    public function store(CreateOrderRequest $request)
    {
        $validated = $request->validated();

        $order = $this->orderService->create(
            customerId: (int) $validated['customer_id'],
            storeId: (int) $validated['store_id'],
            items: $validated['items']
        );

        return ApiResponse::success(
            message: 'تم إنشاء الطلب بنجاح',
            data: (new OrderResource($order))
                ->toArray($request),
            status: 201
        );
    }

    /**
     * Display a specific order.
     */
    public function show(Order $order)
    {
        $order->load([
            'customer:id,name',
            'store:id,name',
            'driver:id,user_id',
            'driver.user:id,name',
            'items:id,order_id,product_id,quantity,price',
            'items.product:id,name',
        ]);

        return ApiResponse::success(
            message: 'تم جلب الطلب بنجاح',
            data: (new OrderResource($order))
                ->toArray(request())
        );
    }

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order,
        OrderStatusService $statusService
    ) {
        $validated = $request->validated();

        $updatedOrder = $statusService->change(
            (int) $order->id,
            OrderStatus::from($validated['status'])
        );

        return ApiResponse::success(
            message: 'تم تحديث حالة الطلب بنجاح',
            data: [
                'id' => $updatedOrder->id,
                'status' => $updatedOrder->status->value,
                'updated_at' => $updatedOrder->updated_at?->toISOString(),
            ]
        );
    }

    public function assignDriver(
        AssignDriverRequest $request,
        Order $order,
        DriverAssignmentService $assignmentService
    ) {
        $validated = $request->validated();

        $updatedOrder = $assignmentService->assign(
            orderId: (int) $order->id,
            driverId: (int) $validated['driver_id']
        );

        /*
     * نحمّل العلاقات اللازمة لتفاصيل الطلب فقط.
     */
        $updatedOrder->load([
            'customer:id,name',
            'store:id,name',
            'driver:id,user_id',
            'driver.user:id,name',
            'items:id,order_id,product_id,quantity,price',
            'items.product:id,name',
        ]);

        return ApiResponse::success(
            message: 'تم تعيين السائق للطلب بنجاح',
            data: (new OrderResource($updatedOrder))
                ->toArray($request)
        );
    }
}
