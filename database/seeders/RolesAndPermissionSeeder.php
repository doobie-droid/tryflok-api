<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use \Spatie\Permission\Models\Role;
use \Spatie\Permission\Models\Permission;
use App\Constants\Roles;
use App\Constants\Permissions;

class RolesAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissionAddAdmin = Permission::create(['name' => Permissions::CAN_ADD_ADMIN, 'guard_name' => 'api']);
        $permissionAddGroup = Permission::create(['name' => Permissions::CAN_CREATE_GROUP, 'guard_name' => 'api']);
        $permissionAddGroupMembers = Permission::create(['name' => Permissions::CAN_ADD_GROUP_MEMBERS, 'guard_name' => 'api']);
        $permissionGrantSubscription = Permission::create(['name' => Permissions::CAN_GRANT_SUBSCRIPTION, 'guard_name' => 'api']);
        $permissionManageAnyContent = Permission::create(['name' => Permissions::CAN_MANAGE_ANY_CONTENT, 'guard_name' => 'api']);

        $roleSuperAdmin = Role::create(['name' => Roles::SUPER_ADMIN, 'guard_name' => 'api']);
        $roleAdmin = Role::create(['name' => Roles::ADMIN, 'guard_name' => 'api']);
        $roleUser = Role::create(['name' => Roles::USER, 'guard_name' => 'api']);

        $roleSuperAdmin->givePermissionTo($permissionAddAdmin);
        $roleSuperAdmin->givePermissionTo($permissionAddGroup);
        $roleSuperAdmin->givePermissionTo($permissionAddGroupMembers);
        $roleSuperAdmin->givePermissionTo($permissionGrantSubscription);
        $roleSuperAdmin->givePermissionTo($permissionManageAnyContent);

        $roleAdmin->givePermissionTo($permissionAddGroup);
        $roleAdmin->givePermissionTo($permissionAddGroupMembers);
        $roleAdmin->givePermissionTo($permissionGrantSubscription);
        $roleAdmin->givePermissionTo($permissionManageAnyContent);
    }
}
