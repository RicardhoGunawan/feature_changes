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
        if (!Schema::hasTable('leave_requests')) {
            Schema::create('leave_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->enum('type', ['sakit', 'cuti', 'keperluan', 'keluarga', 'duka', 'lain']);
                $table->date('start_date');
                $table->date('end_date');
                $table->integer('work_days');
                $table->text('reason');
                $table->string('attachment')->nullable();
                
                // Snapshot at request time
                $table->integer('remaining_leave_at_req')->nullable();
                $table->integer('sick_leave_at_req')->nullable();
                
                $table->enum('status', [
                    'pending_spv', 
                    'pending_hr', 
                    'approved', 
                    'rejected', 
                    'rejected_spv'
                ])->default('pending_spv');
                
                $table->timestamp('approved_by_spv_at')->nullable();
                $table->timestamp('approved_by_hr_at')->nullable();
                $table->foreignId('approved_by_spv_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('approved_by_hr_id')->nullable()->constrained('users')->onDelete('set null');
                
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
