<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Leave Types Table
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'Cuti Tahunan', 'Cuti Sakit', 'Cuti Haji'
            $table->string('code')->unique(); // e.g., 'AL', 'SL', 'HL'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Leave Policies Table (The logic engine)
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_type_id')->constrained()->onDelete('cascade');
            $table->text('description')->nullable();
            
            // Accrual & Quota
            $table->enum('accrual_type', ['full_at_start', 'monthly', 'by_service_tier'])->default('full_at_start');
            $table->integer('default_quota')->default(12);
            $table->integer('min_service_months')->default(0); // For probation rules
            
            // Carry Forward Support
            $table->boolean('can_carry_forward')->default(false);
            $table->integer('max_carry_forward_days')->default(0);
            $table->integer('carry_forward_expiry_months')->default(0);
            
            // Application Rules
            $table->boolean('requires_attachment')->default(false);
            $table->boolean('allow_half_day')->default(true);
            $table->boolean('allow_proxy_submission')->default(true); // For Field Employees by HR
            
            // Constraints
            $table->integer('min_staff_requirement')->default(0); // Requirement #9
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
        Schema::dropIfExists('leave_types');
    }
};
