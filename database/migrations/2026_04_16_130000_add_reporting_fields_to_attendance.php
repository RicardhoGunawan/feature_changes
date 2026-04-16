<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->integer('late_minutes')->default(0)->after('status');
            $table->integer('duration_minutes')->default(0)->after('late_minutes');
            $table->foreignId('check_in_location_id')->nullable()->after('check_in_longitude')->constrained('office_locations')->nullOnDelete();
            $table->foreignId('check_out_location_id')->nullable()->after('check_out_longitude')->constrained('office_locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropConstrainedForeignId('check_in_location_id');
            $table->dropConstrainedForeignId('check_out_location_id');
            $table->dropColumn(['late_minutes', 'duration_minutes']);
        });
    }
};
