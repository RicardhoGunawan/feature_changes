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
        if (!Schema::hasTable('overtime_requests')) {
            Schema::create('overtime_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->date('date');
                $table->datetime('start_time');
                $table->datetime('end_time');
                $table->integer('duration_minutes');
                
                $table->enum('type', ['biasa', 'libur', 'darurat'])->default('biasa');
                $table->enum('location', ['kantor', 'rumah', 'lapangan', 'lain'])->default('kantor');
                $table->text('description');
                $table->string('attachment')->nullable();
                
                $table->decimal('compensation_rate', 3, 1)->default(1.5);
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('set null');
                
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};
