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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->after('name');
            }
            if (!Schema::hasColumn('users', 'employee_code')) {
                $table->string('employee_code')->unique()->after('username');
            }
            if (!Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('position');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('department');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'join_date')) {
                $table->date('join_date')->nullable()->after('address');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'employee', 'supervisor', 'hr'])->default('employee')->after('join_date');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }
            
            if (!Schema::hasColumn('users', 'shift_id')) {
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('set null')->after('is_active');
            }
            if (!Schema::hasColumn('users', 'location_id')) {
                $table->foreignId('location_id')->nullable()->constrained('office_locations')->onDelete('set null')->after('shift_id');
            }
            if (!Schema::hasColumn('users', 'supervisor_id')) {
                $table->foreignId('supervisor_id')->nullable()->constrained('users')->onDelete('set null')->after('location_id');
            }
            
            if (!Schema::hasColumn('users', 'remaining_leave')) {
                $table->integer('remaining_leave')->default(12)->after('location_id');
            }
            if (!Schema::hasColumn('users', 'sick_leave_remaining')) {
                $table->integer('sick_leave_remaining')->default(5)->after('remaining_leave');
            }
            if (!Schema::hasColumn('users', 'leave_reset_year')) {
                $table->integer('leave_reset_year')->default(date('Y'))->after('sick_leave_remaining');
            }
            
            if (!Schema::hasColumn('users', 'last_login')) {
                $table->timestamp('last_login')->nullable()->after('leave_reset_year');
            }
            if (!Schema::hasColumn('users', 'profile_photo')) {
                $table->string('profile_photo')->nullable()->after('last_login');
            }
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropForeign(['location_id']);
            $table->dropColumn([
                'username', 'employee_code', 'position', 'department', 
                'phone', 'address', 'join_date', 'role', 'is_active', 
                'shift_id', 'location_id', 'remaining_leave', 
                'sick_leave_remaining', 'leave_reset_year', 
                'last_login', 'profile_photo'
            ]);
        });
    }
};
