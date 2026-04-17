<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_tier_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_policy_id')->constrained()->onDelete('cascade');
            $table->integer('min_years_service');
            $table->integer('quota_days');
            $table->timestamps();
            
            $table->unique(['leave_policy_id', 'min_years_service'], 'unique_tier_policy');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_tier_policies');
    }
};
