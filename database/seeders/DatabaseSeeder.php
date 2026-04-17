<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Roles & Permissions (must run first)
            RolePermissionSeeder::class,
            // 2. Master Data
            MasterLeaveSeeder::class,
            ApprovalWorkflowSeeder::class,
            MasterDataSeeder::class,
            DepartmentSeeder::class,
            DepartmentConstraintSeeder::class,
            // 3. Users
            UserSeeder::class,
            // 4. Dummy Data
            AttendanceSeeder::class,
        ]);
    }
}
