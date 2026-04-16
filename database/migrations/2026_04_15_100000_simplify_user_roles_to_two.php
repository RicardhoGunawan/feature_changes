<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Migrate existing data — map old roles to new simplified system
        DB::table('users')->where('role', 'admin')->update(['role' => 'administrator']);
        DB::table('users')->whereIn('role', ['hr', 'supervisor', 'spv'])->update(['role' => 'employee']);

        // Step 2: Alter the ENUM column to only allow 2 roles
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('administrator', 'employee') DEFAULT 'employee'");
    }

    public function down(): void
    {
        // Rollback: restore original 4-role ENUM
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'employee', 'supervisor', 'hr') DEFAULT 'employee'");
        // Rollback data
        DB::table('users')->where('role', 'administrator')->update(['role' => 'admin']);
    }
};
