<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminViewController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/login', [AdminViewController::class, 'login'])->name('login');

Route::prefix('admin')->group(function () {
    Route::get('/', function() { return redirect('/admin/dashboard'); });
    // Lower priority login handled by redirect or top-level route
    
    Route::get('/dashboard', [AdminViewController::class, 'dashboard']);
    Route::get('/employees', [AdminViewController::class, 'employees']);
    Route::get('/departments', [AdminViewController::class, 'departments']);
    Route::get('/positions', [AdminViewController::class, 'positions']);
    Route::get('/attendance', [AdminViewController::class, 'attendance']);
    Route::get('/leave', [AdminViewController::class, 'leave']);
    Route::get('/locations', [AdminViewController::class, 'locations']);
    Route::get('/schedules', [AdminViewController::class, 'schedules']);
    Route::get('/holidays', [AdminViewController::class, 'holidays']);
    Route::get('/leave-policies', [AdminViewController::class, 'leavePolicies']);
    Route::get('/approval-workflows', [AdminViewController::class, 'approvalWorkflows']);
    Route::get('/audit-logs', [AdminViewController::class, 'auditLogs']);

});
