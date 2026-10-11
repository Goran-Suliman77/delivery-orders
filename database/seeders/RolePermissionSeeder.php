<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $permissions = [
            'orders.create' => 'إنشاء الطلبات',
            'orders.view_own' => 'مشاهدة الطلبات الخاصة',
            'orders.view_all' => 'مشاهدة جميع الطلبات',
            'orders.view_assigned' => 'مشاهدة الطلبات المسندة للسائق',
            'orders.update_status' => 'تحديث حالات الطلبات',
            'orders.update_any_status' => 'تحديث حالات أي طلب',
            'orders.update_assigned_status' => 'تحديث حالة الطلب المسند للسائق',
            'orders.assign_driver' => 'إسناد سائق إلى طلب',
            'orders.filter_by_customer' => 'فلترة الطلبات حسب الزبون',

            'stores.view_all' => 'مشاهدة جميع المتاجر',
            'stores.manage' => 'إدارة المتاجر',
            'products.manage' => 'إدارة المنتجات',

            'drivers.view_all' => 'مشاهدة السائقين',
            'drivers.manage' => 'إدارة السائقين',

            'reports.view' => 'مشاهدة التقارير',
            'users.manage' => 'إدارة المستخدمين',
        ];

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }

        $rolePermissions = [
            'customer' => [
                'orders.create',
                'orders.view_own',
            ],

            'driver' => [
                'orders.view_assigned',
                'orders.update_assigned_status',
            ],

            'staff' => [
                'orders.view_all',
                'orders.update_status',
                'orders.assign_driver',
                'orders.filter_by_customer',
                'stores.view_all',
                'drivers.view_all',
            ],

            'admin' => array_keys($permissions),
        ];

        $roleDescriptions = [
            'customer' => 'الزبون',
            'driver' => 'السائق',
            'staff' => 'موظف المتابعة',
            'admin' => 'المدير',
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::updateOrCreate(
                ['name' => $roleName],
                [
                    'description' => $roleDescriptions[$roleName],
                ]
            );

            $permissionIds = Permission::query()
                ->whereIn('name', $permissionNames)
                ->pluck('id')
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}
