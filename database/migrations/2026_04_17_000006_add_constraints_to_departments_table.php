<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->integer('max_concurrent_leave')->default(0)->after('leave_workflow_id');
            $table->text('leave_policy_notes')->nullable()->after('max_concurrent_leave');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['max_concurrent_leave', 'leave_policy_notes']);
        });
    }
};
