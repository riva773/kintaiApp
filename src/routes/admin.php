<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\ApprovalsController;
use App\Http\Controllers\Admin\StaffAttendanceController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\StaffAttendanceCsvController;


Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', EnsureAdmin::class, 'verified'])
    ->group(function () {
        Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::patch('/attendance/{id}', [AttendanceController::class, 'update'])->name('attendance.update');

        Route::get('/staff/list', [StaffController::class, 'index'])->name('staff.index');
        Route::get('/attendance/staff/{id}', [StaffAttendanceController::class, 'index'])->name('staff.attendance.index');

        Route::get('/attendance/staff/{id}/csv', StaffAttendanceCsvController::class)->name('staff.attendance.csv');
        Route::get('/approvals', [ApprovalsController::class, 'index'])->name('approvals.index');
        Route::get('/approvals/{id}', [ApprovalsController::class, 'show'])->name('approvals.show');
        Route::post('/approvals/{id}/approve', [ApprovalsController::class, 'approve'])->name('approvals.approve');
    });
