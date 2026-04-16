<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\OvertimeController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\EmployeeController as AdminEmployeeController;
use App\Http\Controllers\Api\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Api\Admin\LeaveController as AdminLeaveController;
use App\Http\Controllers\Api\Admin\ScheduleController;
use App\Http\Controllers\Api\Admin\HolidayController;
use App\Http\Controllers\Api\Admin\PositionController;
use App\Http\Controllers\Api\Admin\DepartmentController;


Route::post('/auth/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/employee/profile', [AuthController::class, 'profile']);
    Route::put('/employee/profile', [AuthController::class, 'updateProfile']);
    Route::post('/employee/upload-photo', [EmployeeController::class, 'uploadPhoto']);
    
    // Attendance
    Route::get('/attendance/today', [AttendanceController::class, 'today']);
    Route::post('/attendance/checkin', [AttendanceController::class, 'checkin']);
    Route::post('/attendance/checkout', [AttendanceController::class, 'checkout']);
    Route::get('/attendance/history', [AttendanceController::class, 'history']);
    
    // Leave
    Route::post('/leave/apply', [LeaveController::class, 'store']);
    Route::get('/leave/history', [LeaveController::class, 'history']);
    
    // Overtime
    Route::post('/overtime/apply', [OvertimeController::class, 'store']);
    Route::get('/overtime/history', [OvertimeController::class, 'history']);

    // ── Admin Routes (Administrator only) ──────────────────────────────────────
    // Approval access is NOT determined here. It is handled in LeaveController
    // via Position hierarchy. Any employee with a parent position can approve.
    Route::middleware('role:administrator')->prefix('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Employee Management
        Route::get('/employees', [AdminEmployeeController::class, 'index']);
        Route::post('/employees', [AdminEmployeeController::class, 'store']);
        Route::delete('/employees', [AdminEmployeeController::class, 'destroy']);
        Route::patch('/employees/{user}/status', [AdminEmployeeController::class, 'updateStatus']);

        // Schedule & Location
        Route::get('/schedules', [ScheduleController::class, 'shifts']);
        Route::post('/schedules', [ScheduleController::class, 'storeShift']);
        Route::get('/locations', [ScheduleController::class, 'locations']);
        Route::post('/locations', [ScheduleController::class, 'storeLocation']);

        // Attendance & Reports
        Route::post('/attendance', [AdminAttendanceController::class, 'index']);
        Route::post('/manual-attendance', [AdminAttendanceController::class, 'storeManual']);
        Route::get('/overtime', [AdminAttendanceController::class, 'overtimeRequests']);
        Route::patch('/overtime/{overtime}/approve', [AdminAttendanceController::class, 'approveOvertime']);

        // Leave Management
        Route::get('/leave', [AdminLeaveController::class, 'index']);
        Route::patch('/leave/approve', [AdminLeaveController::class, 'approve']);

        // Holiday Management
        Route::get('/holidays', [HolidayController::class, 'index']);
        Route::post('/holidays', [HolidayController::class, 'store']);
        Route::delete('/holidays', [HolidayController::class, 'destroy']);

        // Positions Management
        Route::get('/positions', [PositionController::class, 'index']);
        Route::post('/positions', [PositionController::class, 'store']);
        Route::delete('/positions', [PositionController::class, 'destroy']);

        // Department Management
        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::post('/departments', [DepartmentController::class, 'store']);
        Route::delete('/departments', [DepartmentController::class, 'destroy']);


    });

    // ── Approval Routes (Any authenticated user with approver position) ─────────
    // These are separated so employees with approver positions can access them
    // without needing full administrator access.
    Route::prefix('admin')->group(function () {
        Route::get('/leave', [AdminLeaveController::class, 'index']);
        Route::patch('/leave/approve', [AdminLeaveController::class, 'approve']);
    });
});

// Admin Export Routes (Manual Token Verification)
Route::prefix('admin')->group(function () {
    Route::get('/attendance/export', [\App\Http\Controllers\Api\ExportController::class, 'attendanceExport']);
    Route::get('/leave/export', [\App\Http\Controllers\Api\ExportController::class, 'leaveExport']);
});




