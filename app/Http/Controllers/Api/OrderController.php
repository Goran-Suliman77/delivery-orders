<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderListResource;
use App\Http\Resources\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    /**
     * Display active orders.
     */
    public function index(Request $request)
    {
        $perPage = min(
            max($request->integer('per_page', 50), 1),
            100
        );

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

            ->latest('id')
            ->paginate($perPage);

        return ApiResponse::paginated(
            $orders,
            fn($order) => (new OrderListResource($order))
                ->toArray($request),
            'تم جلب الطلبات بنجاح'
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
}
