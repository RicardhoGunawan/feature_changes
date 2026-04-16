<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For MySQL/MariaDB, we have to use raw query to update ENUM
        DB::statement("ALTER TABLE attendance MODIFY COLUMN status ENUM('present', 'late', 'absent', 'leave', 'holiday', 'incomplete') DEFAULT 'present'");
    }

    public function down(): void
    {
        // Be careful when reversing: maybe some data is already 'incomplete'
        // For safety, we keep it as is or revert to original list (which might fail if data exists)
        DB::statement("ALTER TABLE attendance MODIFY COLUMN status ENUM('present', 'late', 'absent', 'leave', 'holiday') DEFAULT 'present'");
    }
};
