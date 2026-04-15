<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Roles
        $roles = ['admin', 'spv', 'hr', 'employee'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);
        }

        // Migrate existing users' roles
        $users = User::all();
        foreach ($users as $user) {
            // Get original role from column (handling if it's already there)
            $oldRole = $user->getAttributes()['role'] ?? null;
            
            if ($oldRole && in_array($oldRole, $roles)) {
                // Assign role via Spatie (supports both web and api guards if configured)
                $user->assignRole($oldRole);
            }
        }
    }
}
