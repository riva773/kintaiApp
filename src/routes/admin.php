<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffAttendanceController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', EnsureAdmin::class])
    ->group(function () {
        Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::get('/staff/list', [StaffController::class, 'index'])->name('staff.index');
        Route::get('/attendance/staff/{id}', [StaffAttendanceController::class, 'index'])->name('staff.attendance.index');
        Route::patch('/attendance/{id}', [AttendanceController::class, 'update'])->name('attendance.update');
    });
