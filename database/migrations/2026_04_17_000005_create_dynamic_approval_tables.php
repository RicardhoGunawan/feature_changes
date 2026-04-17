<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Approval Workflows (The Header)
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'Office Workflow', 'Field Workflow'
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Approval Steps (The Chain)
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->onDelete('cascade');
            $table->integer('step_number')->default(1);
            $table->enum('approver_type', ['manager', 'director', 'hr', 'specific_user']);
            $table->foreignId('specific_user_id')->nullable()->constrained('users');
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });

        // 3. Link Workflow to Category/Department
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('leave_workflow_id')->nullable()->constrained('approval_workflows')->onDelete('set null');
        });

        // 4. Track Current Step in Leave Request
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->integer('current_step')->default(1)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['leave_workflow_id']);
            $table->dropColumn('leave_workflow_id');
        });
        Schema::dropIfExists('approval_steps');
        Schema::dropIfExists('approval_workflows');
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('current_step');
        });
    }
};
