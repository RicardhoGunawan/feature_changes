<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_requests', 'leave_type_id')) {
                $table->foreignId('leave_type_id')->nullable()->constrained('leave_types')->onDelete('set null')->after('user_id');
            }
            if (!Schema::hasColumn('leave_requests', 'leave_duration_type')) {
                $table->enum('leave_duration_type', ['full_day', 'half_day'])->default('full_day')->after('leave_type_id');
            }
            if (!Schema::hasColumn('leave_requests', 'half_day_session')) {
                $table->enum('half_day_session', ['morning', 'afternoon'])->nullable()->after('leave_duration_type');
            }
            if (!Schema::hasColumn('leave_requests', 'total_days')) {
                $table->decimal('total_days', 4, 1)->default(0)->after('work_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['leave_type_id']);
            $table->dropColumn(['leave_type_id', 'leave_duration_type', 'half_day_session', 'total_days']);
        });
    }
};
