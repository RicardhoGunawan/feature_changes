<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Create 2 New Simplified Roles ────────────────────────────────────────
        $roles = ['administrator', 'employee'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);
        }

        // ── Permissions (unchanged, still granular) ───────────────────────────────
        // Only administrators get these. They are checked in BaseAdminController.
        $permissions = [
            'view_dashboard',
            'view_attendance',
            'manage_attendance',
            'view_leave',
            'approve_leave',         // Generic approval - now position-based in logic
            'view_employee',
            'manage_employee',
            'manage_location',
            'manage_schedule',
            'view_roles',
            'manage_roles',
            'view_positions',
            'manage_positions',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'api']);
        }

        // ── Assign all permissions to administrator ───────────────────────────────
        $adminRole = Role::where('name', 'administrator')->where('guard_name', 'api')->first();
        if ($adminRole) {
            $adminRole->syncPermissions(Permission::where('guard_name', 'api')->get());
        }

        // ── Migrate existing users to new roles ───────────────────────────────────
        $users = User::all();
        foreach ($users as $user) {
            $rawRole = $user->getAttributes()['role'] ?? 'employee';

            // Map old roles → new roles
            if (in_array($rawRole, ['admin', 'administrator'])) {
                $newRole = 'administrator';
            } else {
                $newRole = 'employee';
            }

            $user->syncRoles([$newRole]);
        }
    }
}
