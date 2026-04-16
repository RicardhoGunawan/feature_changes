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
            // Rename existing columns to match Controller/Model logic
            if (Schema::hasColumn('leave_requests', 'approved_by_spv_at')) {
                $table->renameColumn('approved_by_spv_at', 'spv_reviewed_at');
            }
            if (Schema::hasColumn('leave_requests', 'approved_by_spv_id')) {
                $table->renameColumn('approved_by_spv_id', 'spv_reviewed_by');
            }
            if (Schema::hasColumn('leave_requests', 'approved_by_hr_at')) {
                $table->renameColumn('approved_by_hr_at', 'reviewed_at');
            }
            if (Schema::hasColumn('leave_requests', 'approved_by_hr_id')) {
                $table->renameColumn('approved_by_hr_id', 'reviewed_by');
            }

            // Add missing note columns
            if (!Schema::hasColumn('leave_requests', 'spv_review_note')) {
                $table->text('spv_review_note')->nullable()->after('status');
            }
            if (!Schema::hasColumn('leave_requests', 'review_note')) {
                $table->text('review_note')->nullable()->after('spv_review_note');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->renameColumn('spv_reviewed_at', 'approved_by_spv_at');
            $table->renameColumn('spv_reviewed_by', 'approved_by_spv_id');
            $table->renameColumn('reviewed_at', 'approved_by_hr_at');
            $table->renameColumn('reviewed_by', 'approved_by_hr_id');
            $table->dropColumn(['spv_review_note', 'review_note']);
        });
    }
};
