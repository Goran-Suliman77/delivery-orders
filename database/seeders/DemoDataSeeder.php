<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\Scopes\ActiveStoreScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (
            ! app()->environment('local')
            && ! app()->environment('testing')
        ) {
            throw new RuntimeException(
                'DemoDataSeeder يعمل فقط في بيئتي local وtesting.'
            );
        }

        $roles = Role::query()
            ->whereIn('name', [
                'admin',
                'staff',
                'customer',
                'driver',
            ])
            ->get()
            ->keyBy('name');

        foreach (['admin', 'staff', 'customer', 'driver'] as $name) {
            if (! $roles->has($name)) {
                throw new RuntimeException(
                    "الدور {$name} غير موجود. شغّل RolePermissionSeeder أولًا."
                );
            }
        }

        DB::transaction(function () use ($roles): void {
            /*
             * 1. إنشاء حسابات تجريبية.
             */

            $this->createUserWithRole(
                'admin@tawseela.test',
                'مدير تجريبي',
                $roles->get('admin')
            );

            $this->createUserWithRole(
                'staff@tawseela.test',
                'موظف متابعة تجريبي',
                $roles->get('staff')
            );

            $customerIds = [];

            for ($i = 1; $i <= 60; $i++) {
                $user = $this->createUserWithRole(
                    sprintf('demo.customer.%02d@example.test', $i),
                    sprintf('زبون تجريبي %02d', $i),
                    $roles->get('customer')
                );

                $customerIds[] = $user->id;
            }

            /*
             * 2. إنشاء 60 سائقًا تجريبيًا.
             * نعطي كل سائق رقم مركبة ثابتًا لتجنب التكرار.
             */

            $driverIds = [];

            for ($i = 1; $i <= 60; $i++) {
                $user = $this->createUserWithRole(
                    sprintf('demo.driver.%02d@example.test', $i),
                    sprintf('سائق تجريبي %02d', $i),
                    $roles->get('driver')
                );

                $driver = Driver::query()->firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'vehicle_type' => $i % 2 === 0
                            ? 'motorcycle'
                            : 'car',
                        'vehicle_number' => sprintf(
                            'DEMO-MTR-%03d',
                            $i
                        ),
                        'is_available' => true,
                    ]
                );

                $driverIds[] = $driver->id;
            }

            /*
             * 3. إنشاء عشرة متاجر تجريبية.
             * المتجر العاشر موقوف لاختبار نطاق المتاجر العامة.
             */

            $stores = [];

            for ($i = 1; $i <= 10; $i++) {
                if ($i <= 4) {
                    $type = 'restaurant';
                    $label = 'مطعم تجريبي';
                } elseif ($i <= 7) {
                    $type = 'mini_market';
                    $label = 'سوبر ماركت تجريبي';
                } else {
                    $type = 'pharmacy';
                    $label = 'صيدلية تجريبية';
                }

                $store = Store::withoutGlobalScope(
                    ActiveStoreScope::class
                )->firstOrCreate(
                    [
                        'name' => sprintf(
                            '[DEMO] %s %02d',
                            $label,
                            $i
                        ),
                    ],
                    [
                        'type' => $type,
                        'is_active' => $i !== 10,
                    ]
                );

                $stores[] = $store;
            }

            $allStoreIds = collect($stores)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();

            $activeStores = collect($stores)
                ->filter(fn(Store $store) => (bool) $store->is_active)
                ->values();

            /*
             * 4. إنشاء 12 منتجًا لكل متجر.
             * بعض المنتجات موقوفة لاختبار الفلاتر العامة.
             */

            foreach ($stores as $storeIndex => $store) {
                for ($productIndex = 1; $productIndex <= 12; $productIndex++) {
                    $priceCents = 250 + (
                        (
                            ($storeIndex + 1) * 431
                            + $productIndex * 273
                        ) % 9750
                    );

                    Product::query()->firstOrCreate(
                        [
                            'store_id' => $store->id,
                            'name' => sprintf(
                                '[DEMO] منتج %02d',
                                $productIndex
                            ),
                        ],
                        [
                            'price' => $this->money($priceCents),
                            'is_active' => $productIndex % 10 !== 0,
                        ]
                    );
                }
            }

            /*
             * نجلب المنتجات المتاحة للمتاجر النشطة فقط.
             * لا نريد إنشاء طلبات جديدة لمتجر موقوف.
             */

            $productsByStore = [];

            foreach ($activeStores as $store) {
                $products = Product::query()
                    ->available()
                    ->forStore((int) $store->id)
                    ->get();

                if ($products->isEmpty()) {
                    throw new RuntimeException(
                        "المتجر {$store->id} لا يحتوي على منتجات متاحة."
                    );
                }

                $productsByStore[$store->id] = $products;
            }

            /*
             * 5. منع تكرار إنشاء ألف طلب عند إعادة التشغيل.
             */

            $existingOrders = Order::query()
                ->forStores($allStoreIds)
                ->count();

            if ($existingOrders >= 1000) {
                if ($this->command) {
                    $this->command->info(
                        "توجد بالفعل {$existingOrders} طلبات تجريبية. لم تتم إضافة طلبات جديدة."
                    );
                }

                return;
            }

            /*
             * توزيع الحالات المستهدفة.
             * مجموعها 1000 طلب عند التشغيل الأول.
             */

            $targetCounts = [
                OrderStatus::Pending->value => 140,
                OrderStatus::Confirmed->value => 180,
                OrderStatus::Preparing->value => 180,
                OrderStatus::Ready->value => 180,
                OrderStatus::Assigned->value => 20,
                OrderStatus::PickedUp->value => 20,
                OrderStatus::Delivered->value => 180,
                OrderStatus::Cancelled->value => 100,
            ];

            $existingCounts = Order::query()
                ->forStores($allStoreIds)
                ->select('status')
                ->selectRaw('COUNT(*) AS aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            $statuses = [];

            foreach ($targetCounts as $status => $targetCount) {
                $existingCount = (int) (
                    $existingCounts[$status] ?? 0
                );

                $missing = max(
                    0,
                    $targetCount - $existingCount
                );

                for ($i = 0; $i < $missing; $i++) {
                    $statuses[] = $status;
                }
            }

            // نضمن الوصول إلى 1000 طلب على الأقل حتى لو
            // كانت البيانات القديمة غير مطابقة لتوزيع الحالات المستهدف.
            $minimumMissing = max(
                0,
                1000 - $existingOrders - count($statuses)
            );

            for ($i = 0; $i < $minimumMissing; $i++) {
                $statuses[] = OrderStatus::Delivered->value;
            }

            shuffle($statuses);

            /*
             * لا نستخدم سائقًا لديه طلب نشط حاليًا.
             */

            $busyDriverIds = Order::query()
                ->active()
                ->forDrivers($driverIds)
                ->withDriver()
                ->pluck('driver_id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values();

            $freeDriverIds = collect($driverIds)
                ->diff($busyDriverIds)
                ->values();

            $deliveryFeeCents = (int) round(
                (float) config(
                    'tawseela.delivery.base_fee',
                    '4.00'
                ) * 100
            );

            /*
             * 6. إنشاء الطلبات والعناصر المرتبطة بها.
             */

            foreach ($statuses as $status) {
                $store = $activeStores->random();
                $customerId = $customerIds[array_rand($customerIds)];

                $products = $productsByStore[$store->id];
                $itemCount = random_int(
                    1,
                    min(4, $products->count())
                );

                $selectedProducts = $products
                    ->shuffle()
                    ->take($itemCount);

                $items = [];
                $subtotalCents = 0;

                $daysAgo = in_array(
                    $status,
                    [
                        OrderStatus::Delivered->value,
                        OrderStatus::Cancelled->value,
                    ],
                    true
                )
                    ? random_int(15, 365)
                    : random_int(0, 14);

                $createdAt = now()
                    ->subDays($daysAgo)
                    ->subMinutes(random_int(0, 1439));

                /*
                 * الطلبات assigned وpicked_up فقط تحتاج
                 * إلى سائق مشغول الآن.
                 */

                $driverId = null;

                if (in_array(
                    $status,
                    [
                        OrderStatus::Assigned->value,
                        OrderStatus::PickedUp->value,
                    ],
                    true
                )) {
                    if ($freeDriverIds->isEmpty()) {
                        throw new RuntimeException(
                            'لا يوجد عدد كافٍ من السائقين المتاحين لإنشاء بيانات الاختبار.'
                        );
                    }

                    $driverId = (int) $freeDriverIds->shift();
                } elseif (
                    $status === OrderStatus::Delivered->value
                ) {
                    // تاريخ التوصيل: يمكن أن يكون للسائق عدة
                    // طلبات مكتملة دون أن يكون مشغولًا حاليًا.
                    $driverId = $driverIds[array_rand($driverIds)];
                } elseif (
                    $status === OrderStatus::Cancelled->value
                    && random_int(0, 1) === 1
                ) {
                    $driverId = $driverIds[array_rand($driverIds)];
                }

                foreach ($selectedProducts as $product) {
                    $quantity = random_int(1, 4);

                    $priceCents = (int) round(
                        (float) $product->price * 100
                    );

                    $subtotalCents += $priceCents * $quantity;

                    $items[] = [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $this->money($priceCents),
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];
                }

                $order = Order::query()->create([
                    'customer_id' => $customerId,
                    'store_id' => $store->id,
                    'driver_id' => $driverId,
                    'status' => $status,
                    'subtotal' => $this->money($subtotalCents),
                    'delivery_fee' => $this->money($deliveryFeeCents),
                    'total' => $this->money(
                        $subtotalCents + $deliveryFeeCents
                    ),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $order->items()->createMany($items);
            }

            /*
             * 7. مزامنة توفر السائقين التجريبيين مع الطلبات النشطة.
             */

            $currentlyBusyIds = Order::query()
                ->active()
                ->forDrivers($driverIds)
                ->withDriver()
                ->pluck('driver_id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values();

            Driver::query()
                ->whereIn('id', $driverIds)
                ->update(['is_available' => true]);

            if ($currentlyBusyIds->isNotEmpty()) {
                Driver::query()
                    ->whereIn('id', $currentlyBusyIds->all())
                    ->update(['is_available' => false]);
            }
        });

        if ($this->command) {
            $this->command->info(
                'اكتملت تهيئة بيانات توصيلـة التجريبية.'
            );
        }
    }

    private function createUserWithRole(
        string $email,
        string $name,
        Role $role
    ): User {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'TawseelaDemo123!',
            ]
        );

        $user->roles()->syncWithoutDetaching([
            $role->id,
        ]);

        return $user;
    }

    private function money(int $cents): string
    {
        return number_format(
            $cents / 100,
            2,
            '.',
            ''
        );
    }
}
