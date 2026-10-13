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
use App\Contracts\OrderQueryServiceInterface;
use Illuminate\Http\JsonResponse;
use App\Contracts\OrderServiceInterface;
use App\Contracts\OrderStatusServiceInterface;
use App\Contracts\DriverAssignmentServiceInterface;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderQueryServiceInterface $orderQueries,
    ) {}

    /**
     * Display active orders.
     */
    public function index(
        OrderIndexRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $this->authorize('viewAny', Order::class);

        // فلترة القائمة حسب الزبون تتطلب صلاحية صريحة.
        if (isset($validated['customer_id'])) {
            $this->authorize('filterByCustomer', Order::class);
        }

        $orders = $this->orderQueries->paginateVisibleTo(
            $request->user(),
            $validated
        );

        // هذه رسائل استجابة فقط، وليست شروط استعلام.
        if ($orders->total() === 0) {
            $message = ($validated['today'] ?? false) === true
                ? 'لا توجد طلبات لليوم تطابق معايير البحث.'
                : 'لا توجد طلبات تطابق معايير البحث.';
        } elseif ($orders->isEmpty()) {
            $message = 'لا توجد طلبات في هذه الصفحة. جرّب صفحة أخرى.';
        } else {
            $message = 'تم جلب الطلبات بنجاح';
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
        $this->authorize('create', Order::class);

        $validated = $request->validated();

        $order = $this->orderService->create(
            customerId: (int) $request->user()->id,
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
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order = $this->orderQueries->loadDetails($order);

        return ApiResponse::success(
            message: 'تم جلب الطلب بنجاح',
            data: (new OrderResource($order))
                ->toArray(request())
        );
    }

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order,
        OrderStatusServiceInterface $statusService
    ) {
        $validated = $request->validated();

        $status = OrderStatus::from(
            $validated['status']
        );

        // التحقق من صلاحية المستخدم على هذا الطلب
        // وعلى الحالة التي يريد الانتقال إليها.
        $this->authorize(
            'updateStatus',
            [$order, $status]
        );

        $updatedOrder = $statusService->change(
            (int) $order->id,
            $status
        );

        return ApiResponse::success(
            message: 'تم تحديث حالة الطلب بنجاح',
            data: [
                'id' => $updatedOrder->id,
                'status' => $updatedOrder->status->value,
                'updated_at' => $updatedOrder->updated_at
                    ?->toISOString(),
            ]
        );
    }

    public function assignDriver(
        AssignDriverRequest $request,
        Order $order,
        DriverAssignmentServiceInterface $assignmentService
    ) {
        $validated = $request->validated();
        $this->authorize('assignDriver', $order);
        $updatedOrder = $assignmentService->assign(
            orderId: (int) $order->id,
            driverId: (int) $validated['driver_id']
        );

        /*
     * نحمّل العلاقات اللازمة لتفاصيل الطلب فقط.
     */
        $updatedOrder = $this->orderQueries->loadDetails(
            $updatedOrder
        );

        return ApiResponse::success(
            message: 'تم تعيين السائق للطلب بنجاح',
            data: (new OrderResource($updatedOrder))
                ->toArray($request)
        );
    }
}
