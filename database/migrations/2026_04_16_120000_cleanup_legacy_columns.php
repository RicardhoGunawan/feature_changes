<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cleanup Users table
        Schema::table('users', function (Blueprint $table) {
            // Check if columns exist before dropping to avoid errors
            if (Schema::hasColumn('users', 'department')) {
                $table->dropColumn('department');
            }
            if (Schema::hasColumn('users', 'position')) {
                $table->dropColumn('position');
            }
            if (Schema::hasColumn('users', 'supervisor_id')) {
                $table->dropConstrainedForeignId('supervisor_id');
            }
        });

        // Cleanup Positions table
        Schema::table('positions', function (Blueprint $table) {
            if (Schema::hasColumn('positions', 'department')) {
                $table->dropColumn('department');
            }
        });
    }

    public function down(): void
    {
        // To reverse, we would need to add them back, 
        // but usually we don't want to restore redundant data.
        Schema::table('users', function (Blueprint $table) {
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->foreignId('supervisor_id')->nullable();
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->string('department')->nullable();
        });
    }
};
