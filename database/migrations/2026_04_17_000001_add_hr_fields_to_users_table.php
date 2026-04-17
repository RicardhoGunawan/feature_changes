<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'employee_type')) {
                $table->enum('employee_type', ['office', 'field'])->default('office')->after('role');
            }
            if (!Schema::hasColumn('users', 'probation_end_date')) {
                $table->date('probation_end_date')->nullable()->after('join_date');
            }
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('probation_end_date');
            }
            if (!Schema::hasColumn('users', 'annual_leave_quota')) {
                $table->integer('annual_leave_quota')->default(12)->after('date_of_birth');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['employee_type', 'join_date', 'probation_end_date', 'date_of_birth', 'annual_leave_quota']);
        });
    }
};
