<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminViewController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->group(function () {
    Route::get('/', function() { return redirect('/admin/dashboard'); });
    Route::get('/login', [AdminViewController::class, 'login'])->name('admin.login');
    
    Route::get('/dashboard', [AdminViewController::class, 'dashboard']);
    Route::get('/employees', [AdminViewController::class, 'employees']);
    Route::get('/positions', [AdminViewController::class, 'positions']);
    Route::get('/attendance', [AdminViewController::class, 'attendance']);
    Route::get('/leave', [AdminViewController::class, 'leave']);
    Route::get('/locations', [AdminViewController::class, 'locations']);
    Route::get('/schedules', [AdminViewController::class, 'schedules']);
    Route::get('/holidays', [AdminViewController::class, 'holidays']);
    Route::get('/roles', [AdminViewController::class, 'roles']);
});
