<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->enum('leave_duration_type', ['full_day', 'half_day'])->default('full_day')->after('type');
            $table->enum('half_day_session', ['morning', 'afternoon'])->nullable()->after('leave_duration_type');
            $table->decimal('total_days', 4, 1)->default(1.0)->after('work_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['leave_duration_type', 'half_day_session', 'total_days']);
        });
    }
};
