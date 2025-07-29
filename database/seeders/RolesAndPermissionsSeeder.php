<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //? Merchant

        $merchantPermissions = [
            'create-order',
            'update-order',
            'delete-order',
            'get-order',
            'get-orders',
            'get-delivered',
            'get-cancelled',
            'get-latest',
            'get-summary',
            'create-warehouse',
            'update-warehouse',
            'delete-warehouse',
            'get-warehouse-summary',
            'get-warehouse-latest',
            'get-warehouse-cancelled',
            'get-warehouse-delivered',
        ];
        foreach ($merchantPermissions as $pre) {
            Permission::firstOrCreate(['name' => $pre]);
        }
        $merchant = Role::firstOrCreate(
            ['name' => 'merchant', 'guard_name' => 'api']
        );

        $merchant->givePermissionTo($merchantPermissions);


        //? Customer
        $customerPermissions = [
            'get-orders',
            'track',
            'get-compelete',
            'cancel',
            'create-complaint',
            'update-complaint',
            'delete-complaint',
            'get-customer-complaint',
            'get-customer-complaints',
            'get-reply',
        ];
        foreach ($customerPermissions as $pre) {
            Permission::firstOrCreate([
                'name' => $pre,
                'guard_name' => 'customer',
            ]);
        }
        $customer = Role::firstOrCreate([
            'name' => 'customer',
            'guard_name' => 'customer',
        ]);
        $permissions = Permission::where('guard_name', 'customer')
            ->whereIn('name', $customerPermissions)
            ->get();

        $customer->syncPermissions($permissions);


        //? Driver
        $driverPermissions = [
            'receive-order',
            'get-order',
            'cancel-order',
            'failed-order',
            'out-delivery-order',
            'tracknumber-order',
            'get-delivered-order',
            'get-order',
            'get-for-delivery-order',
            'get-cancel-order',
            'not-available',
            'summary',
            'rating',
            'summary-rating',
        ];
        foreach ($driverPermissions as $pre) {
            Permission::firstOrCreate(['name' => $pre]);
        }
        $driver = Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'api']);
        $driver->givePermissionTo($driverPermissions);


        //? Employee
        $employeePermissions = [
            'get-assign-order',
            'get-orders',
            'get-order',
            'get-stuck',
            'get-summary',
            'receive-order',
            'auto-assign-driver',
            'assign-driver',
            'get-orders',
            'get-orders',
            //driver
            'get-drivers',
            'get-driver',
            'get-available',
            'get-best',
            'get-avg',
            'update-driver',
            'delete-driver',
            'get-driver-summary',
            'get-driver-toggle',
            'get-driver-orders',
            'get-driver',
            //employee 
            'create-employee',
            'update-employee',
            'delete-employee',
            'get-employee',
            'get-employees',
            'get-employee-byName',
        ];


        //? admin


        $user1Permissions = [
            'get-orders',
            'get-governorate-orders',
            'get-merchant-orders',
            'get-warehouse-orders',
            'assign-order',
            'get-assign-orders',
            'get-merchant-assign-orders',
        ];
        foreach ($user1Permissions as $per) {
            Permission::firstOrCreate(['name' => $per]);
        }
        $user1 = Role::firstOrCreate(['name' => 'admin_order', 'guard_name' => 'api']);
        $user1->givePermissionTo($user1Permissions);



        $user2Permissions = [
            'get-late-orders',
            'get-falied-orders',
            'get-cancelled-orders',
            'assign-cancelled-orders-agian',
            'update-orders',
            'get-admin-complaint',
            'reply-complaint',
            'mark-complaint-closed',
            'get-compalaint-filter',
            'get-assign-orders',
            'get-merchant-assign-orders',
        ];
        foreach ($user2Permissions as $per) {
            Permission::firstOrCreate(['name' => $per]);
        }
        $user2 = Role::firstOrCreate(['name' => 'admin_support', 'guard_name' => 'api']);
        $user2->givePermissionTo($user2Permissions);





        $user3Permissions = [
            'get-delivered-orders',
            'get-logs-orders',
            'get-logs-order',
            'get-merchant-logs',
        ];
        foreach ($user3Permissions as $per) {
            Permission::firstOrCreate(['name' => $per]);
        }
        $user3 = Role::firstOrCreate(['name' => 'admin_check', 'guard_name' => 'api']);
        $user3->givePermissionTo($user3Permissions);




        $user4Permissions = [
            'add-delivery-company',
            'update-delivery-company',
            'delete-delivery-company',
            'get-delivery-company',
            'get-delivery-companies',
            'get-status-delivery-companies',
            'get-governorate-delivery-companies',
            'get-summary-delivery-companies',
        ];
        foreach ($user4Permissions as $per) {
            Permission::firstOrCreate(['name' => $per]);
        }
        $user4 = Role::firstOrCreate(['name' => 'admin_manager', 'guard_name' => 'api']);
        $user4->givePermissionTo($user4Permissions);


        $superAdmin = Role::firstOrCreate(['name' => 'super admin', 'guard_name' => 'api']);
        $permissions = Permission::where('guard_name', 'api')->get();

        $superAdmin->syncPermissions($permissions);
    }
}
